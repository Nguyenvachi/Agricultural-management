<?php

namespace App\Services;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function importStock(
        int $agencyId,
        int $itemId,
        float $quantity,
        int $orderId,
        int $createdBy,
        ?string $note = null,
        ?string $referenceType = null
    ): InventoryTransaction {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Số lượng nhập phải > 0.');
        }

        $transactionTypeId = LookupHelper::getValueId(LookupCode::TYPE_TRANSACTION_TYPE, LookupCode::TRANSACTION_IMPORT);

        return $this->applyStockChange(
            agencyId: $agencyId,
            itemId: $itemId,
            orderId: $orderId,
            createdBy: $createdBy,
            transactionTypeId: $transactionTypeId,
            quantityChange: $quantity,
            note: $note,
            referenceType: $referenceType
        );
    }

    public function exportStock(
        int $agencyId,
        int $itemId,
        float $quantity,
        int $orderId,
        int $createdBy,
        ?string $note = null,
        ?string $referenceType = null
    ): InventoryTransaction {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Số lượng xuất phải > 0.');
        }

        $transactionTypeId = LookupHelper::getValueId(LookupCode::TYPE_TRANSACTION_TYPE, LookupCode::TRANSACTION_EXPORT);

        return $this->applyStockChange(
            agencyId: $agencyId,
            itemId: $itemId,
            orderId: $orderId,
            createdBy: $createdBy,
            transactionTypeId: $transactionTypeId,
            quantityChange: -$quantity,
            note: $note,
            referenceType: $referenceType
        );
    }

    public function transferStock(
        int $fromAgencyId,
        int $toAgencyId,
        int $itemId,
        float $quantity,
        int $orderId,
        int $createdBy,
        ?string $note = null
    ): array {
        return DB::transaction(function () use ($fromAgencyId, $toAgencyId, $itemId, $quantity, $orderId, $createdBy, $note) {
            $exportTx = $this->exportStock(
                agencyId: $fromAgencyId,
                itemId: $itemId,
                quantity: $quantity,
                orderId: $orderId,
                createdBy: $createdBy,
                note: $note,
                referenceType: LookupCode::ORDER_INTERNAL_TRANSFER
            );

            $importTx = $this->importStock(
                agencyId: $toAgencyId,
                itemId: $itemId,
                quantity: $quantity,
                orderId: $orderId,
                createdBy: $createdBy,
                note: $note,
                referenceType: LookupCode::ORDER_INTERNAL_TRANSFER
            );

            return [$exportTx, $importTx];
        });
    }

    /**
     * Rollback tồn kho theo order_id bằng cách tạo transaction bù trừ.
     * Lưu ý: hàm này không tự kiểm tra trạng thái Order; OrderService phải đảm bảo chỉ gọi khi hợp lệ.
     */
    public function rollbackByOrderId(int $orderId, int $createdBy, ?string $note = null): void
    {
        DB::transaction(function () use ($orderId, $createdBy, $note) {
            $transactions = InventoryTransaction::query()
                ->where('order_id', $orderId)
                ->orderBy('agency_id')
                ->orderBy('item_id')
                ->orderBy('id')
                ->get();

            if ($transactions->isEmpty()) {
                return;
            }

            $importTypeId = LookupHelper::getValueId(LookupCode::TYPE_TRANSACTION_TYPE, LookupCode::TRANSACTION_IMPORT);
            $exportTypeId = LookupHelper::getValueId(LookupCode::TYPE_TRANSACTION_TYPE, LookupCode::TRANSACTION_EXPORT);

            foreach ($transactions as $tx) {
                $quantityChange = (float) $tx->quantity_change;
                if ($quantityChange == 0.0) {
                    continue;
                }

                $reversalChange = -$quantityChange;
                $transactionTypeId = $reversalChange > 0 ? $importTypeId : $exportTypeId;

                $inventory = Inventory::query()
                    ->where('agency_id', $tx->agency_id)
                    ->where('item_id', $tx->item_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    $inventory = Inventory::query()->create([
                        'agency_id' => $tx->agency_id,
                        'item_id' => $tx->item_id,
                        'quantity' => 0,
                    ]);
                }

                $before = (float) $inventory->quantity;
                $after = $before + $reversalChange;

                if ($after < 0) {
                    throw new InvalidArgumentException('Không thể rollback vì sẽ gây âm kho (tồn kho hiện tại không đủ để hủy đơn).');
                }

                $inventory->update([
                    'quantity' => $after,
                ]);

                InventoryTransaction::query()->create([
                    'agency_id' => $tx->agency_id,
                    'item_id' => $tx->item_id,
                    'order_id' => $orderId,
                    'transaction_type_id' => $transactionTypeId,
                    'quantity_change' => $reversalChange,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'reference_type' => LookupCode::ORDER_CANCELLED,
                    'note' => $note,
                    'created_by' => $createdBy,
                ]);
            }
        });
    }

    private function applyStockChange(
        int $agencyId,
        int $itemId,
        int $orderId,
        int $createdBy,
        int $transactionTypeId,
        float $quantityChange,
        ?string $note,
        ?string $referenceType
    ): InventoryTransaction {
        return DB::transaction(function () use ($agencyId, $itemId, $orderId, $createdBy, $transactionTypeId, $quantityChange, $note, $referenceType) {
            $inventory = Inventory::query()
                ->where('agency_id', $agencyId)
                ->where('item_id', $itemId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                $inventory = Inventory::query()->create([
                    'agency_id' => $agencyId,
                    'item_id' => $itemId,
                    'quantity' => 0,
                ]);
            }

            $before = (float) $inventory->quantity;
            $after = $before + $quantityChange;

            if ($after < 0) {
                throw new InvalidArgumentException('Không cho phép xuất kho âm.');
            }

            $inventory->update([
                'quantity' => $after,
            ]);

            return InventoryTransaction::query()->create([
                'agency_id' => $agencyId,
                'item_id' => $itemId,
                'order_id' => $orderId,
                'transaction_type_id' => $transactionTypeId,
                'quantity_change' => $quantityChange,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $referenceType,
                'note' => $note,
                'created_by' => $createdBy,
            ]);
        });
    }
}
