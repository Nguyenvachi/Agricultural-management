<?php

namespace App\Services;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\SysLookupValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    private InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function createOrderWithOneDetail(array $orderData, array $detailData): Order
    {
        return DB::transaction(function () use ($orderData, $detailData) {
            $completedStatusId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_STATUS, LookupCode::ORDER_COMPLETED);

            $order = Order::query()->create([
                'order_code' => $this->generateOrderCode(),
                'agency_id' => $orderData['agency_id'],
                'to_agency_id' => $orderData['to_agency_id'] ?? null,
                'reference_order_id' => $orderData['reference_order_id'] ?? null,
                'user_id' => $orderData['user_id'],
                'order_type_id' => $orderData['order_type_id'],
                'status_id' => $completedStatusId,
                'total_amount' => 0,
                'note' => $orderData['note'] ?? null,
                'order_date' => $orderData['order_date'],
                'created_by' => $orderData['created_by'],
            ]);

            $quantity = (float) $detailData['quantity'];
            $unitPrice = (float) $detailData['unit_price'];
            if ($quantity <= 0) {
                throw new InvalidArgumentException('Số lượng phải > 0.');
            }
            if ($unitPrice < 0) {
                throw new InvalidArgumentException('Đơn giá không hợp lệ.');
            }

            $totalPrice = $quantity * $unitPrice;

            OrderDetail::query()->create([
                'order_id' => $order->id,
                'item_id' => $detailData['item_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]);

            $order->update([
                'total_amount' => $totalPrice,
            ]);

            $orderTypeCode = (string) SysLookupValue::query()
                ->where('id', $order->order_type_id)
                ->value('code');

            $this->applyInventoryForCompletedOrder(
                orderTypeCode: $orderTypeCode,
                orderId: $order->id,
                agencyId: (int) $order->agency_id,
                toAgencyId: $order->to_agency_id ? (int) $order->to_agency_id : null,
                itemId: (int) $detailData['item_id'],
                quantity: $quantity,
                createdBy: (int) $order->created_by,
                note: $order->note
            );

            return $order->load(['agency', 'toAgency', 'orderType', 'status', 'details.item']);
        });
    }

    /**
     * @param array<int, array{item_id:int, quantity:float, unit_price:float}> $detailsData
     */
    public function createOrderWithDetails(array $orderData, array $detailsData): Order
    {
        return DB::transaction(function () use ($orderData, $detailsData) {
            $completedStatusId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_STATUS, LookupCode::ORDER_COMPLETED);

            $order = Order::query()->create([
                'order_code' => $this->generateOrderCode(),
                'agency_id' => $orderData['agency_id'],
                'to_agency_id' => $orderData['to_agency_id'] ?? null,
                'reference_order_id' => $orderData['reference_order_id'] ?? null,
                'user_id' => $orderData['user_id'],
                'order_type_id' => $orderData['order_type_id'],
                'status_id' => $completedStatusId,
                'total_amount' => 0,
                'note' => $orderData['note'] ?? null,
                'order_date' => $orderData['order_date'],
                'created_by' => $orderData['created_by'],
            ]);

            if (count($detailsData) === 0) {
                throw new InvalidArgumentException('Chi tiết đơn hàng không được rỗng.');
            }

            $orderTypeCode = (string) SysLookupValue::query()
                ->where('id', $order->order_type_id)
                ->value('code');

            $totalAmount = 0.0;

            foreach ($detailsData as $detailData) {
                $quantity = (float) ($detailData['quantity'] ?? 0);
                $unitPrice = (float) ($detailData['unit_price'] ?? 0);
                $itemId = (int) ($detailData['item_id'] ?? 0);

                if ($itemId <= 0) {
                    throw new InvalidArgumentException('Mặt hàng không hợp lệ.');
                }
                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Số lượng phải > 0.');
                }
                if ($unitPrice < 0) {
                    throw new InvalidArgumentException('Đơn giá không hợp lệ.');
                }

                $totalPrice = $quantity * $unitPrice;
                $totalAmount += $totalPrice;

                OrderDetail::query()->create([
                    'order_id' => $order->id,
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);

                $this->applyInventoryForCompletedOrder(
                    orderTypeCode: $orderTypeCode,
                    orderId: $order->id,
                    agencyId: (int) $order->agency_id,
                    toAgencyId: $order->to_agency_id ? (int) $order->to_agency_id : null,
                    itemId: $itemId,
                    quantity: $quantity,
                    createdBy: (int) $order->created_by,
                    note: $order->note
                );
            }

            $order->update([
                'total_amount' => $totalAmount,
            ]);

            return $order->load(['agency', 'toAgency', 'orderType', 'status', 'details.item']);
        });
    }

    private function applyInventoryForCompletedOrder(
        string $orderTypeCode,
        int $orderId,
        int $agencyId,
        ?int $toAgencyId,
        int $itemId,
        float $quantity,
        int $createdBy,
        ?string $note
    ): void {
        if ($orderTypeCode === LookupCode::ORDER_PURCHASE) {
            $this->inventoryService->importStock(
                agencyId: $agencyId,
                itemId: $itemId,
                quantity: $quantity,
                orderId: $orderId,
                createdBy: $createdBy,
                note: $note,
                referenceType: $orderTypeCode
            );
            return;
        }

        if ($orderTypeCode === LookupCode::ORDER_SALES) {
            $this->inventoryService->exportStock(
                agencyId: $agencyId,
                itemId: $itemId,
                quantity: $quantity,
                orderId: $orderId,
                createdBy: $createdBy,
                note: $note,
                referenceType: $orderTypeCode
            );
            return;
        }

        if ($orderTypeCode === LookupCode::ORDER_INTERNAL_TRANSFER) {
            if (!$toAgencyId) {
                throw new InvalidArgumentException('Thiếu đại lý nhận (to_agency_id).');
            }
            $this->inventoryService->transferStock(
                fromAgencyId: $agencyId,
                toAgencyId: $toAgencyId,
                itemId: $itemId,
                quantity: $quantity,
                orderId: $orderId,
                createdBy: $createdBy,
                note: $note
            );
            return;
        }

        // Phase 4: RETURN_ORDER — hàng trả về → nhập kho
        if ($orderTypeCode === LookupCode::ORDER_RETURN) {
            $this->inventoryService->importStock(
                agencyId: $agencyId,
                itemId: $itemId,
                quantity: $quantity,
                orderId: $orderId,
                createdBy: $createdBy,
                note: $note,
                referenceType: $orderTypeCode
            );
            return;
        }

        // Phase 4: ADJUSTMENT_ORDER — điều chỉnh tồn kho → nhập thêm vào kho
        if ($orderTypeCode === LookupCode::ORDER_ADJUSTMENT) {
            $this->inventoryService->importStock(
                agencyId: $agencyId,
                itemId: $itemId,
                quantity: $quantity,
                orderId: $orderId,
                createdBy: $createdBy,
                note: $note,
                referenceType: $orderTypeCode
            );
            return;
        }

        throw new InvalidArgumentException('Order type chưa được hỗ trợ.');
    }

    /**
     * Hủy đơn hàng: rollback tồn kho + cập nhật status → CANCELLED.
     * Chỉ cho hủy nếu đơn chưa ở trạng thái CANCELLED.
     */
    public function cancelOrder(Order $order, int $cancelledBy): void
    {
        $order->loadMissing('status');
        $statusCode = optional($order->status)->code;

        if ($statusCode === LookupCode::ORDER_CANCELLED) {
            throw new InvalidArgumentException('Đơn hàng đã bị hủy trước đó.');
        }

        DB::transaction(function () use ($order, $cancelledBy) {
            $cancelledStatusId = LookupHelper::getValueId(
                LookupCode::TYPE_ORDER_STATUS,
                LookupCode::ORDER_CANCELLED
            );

            $this->inventoryService->rollbackByOrderId(
                orderId: $order->id,
                createdBy: $cancelledBy,
                note: 'Hủy đơn ' . $order->order_code
            );

            $order->update(['status_id' => $cancelledStatusId]);
        });
    }

    private function generateOrderCode(): string
    {
        return 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
    }
}
