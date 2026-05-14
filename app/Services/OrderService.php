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
        return $this->createOrderWithDetails($orderData, [$detailData]);
    }

    /**
     * @param array<int, array{item_id:int, quantity:float, unit_price:float}> $detailsData
     */
    public function createOrderWithDetails(array $orderData, array $detailsData): Order
    {
        return DB::transaction(function () use ($orderData, $detailsData) {
            $pendingStatusId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_STATUS, LookupCode::ORDER_PENDING);
            $orderTypeCode = $this->resolveOrderTypeCode((int) $orderData['order_type_id']);
            $normalizedDetails = $this->normalizeDetailsData($detailsData);

            $this->validateOrderBusinessRules($orderData, $normalizedDetails, $orderTypeCode);

            $totalAmount = 0.0;
            foreach ($normalizedDetails as $detailData) {
                $totalAmount += $detailData['quantity'] * $detailData['unit_price'];
            }

            $order = Order::query()->create([
                'order_code' => $this->generateOrderCode(),
                'agency_id' => $orderData['agency_id'],
                'to_agency_id' => $orderData['to_agency_id'] ?? null,
                'reference_order_id' => $orderData['reference_order_id'] ?? null,
                'user_id' => $orderData['user_id'],
                'order_type_id' => $orderData['order_type_id'],
                'status_id' => $pendingStatusId,
                'total_amount' => $totalAmount,
                'note' => $orderData['note'] ?? null,
                'order_date' => $orderData['order_date'],
                'created_by' => $orderData['created_by'],
            ]);

            foreach ($normalizedDetails as $detailData) {
                $totalPrice = $detailData['quantity'] * $detailData['unit_price'];

                OrderDetail::query()->create([
                    'order_id' => $order->id,
                    'item_id' => $detailData['item_id'],
                    'quantity' => $detailData['quantity'],
                    'unit_price' => $detailData['unit_price'],
                    'total_price' => $totalPrice,
                ]);
            }

            return $order->load(['agency', 'toAgency', 'orderType', 'status', 'details.item']);
        });
    }

    public function markProcessing(Order $order): void
    {
        $order->loadMissing('status');
        $statusCode = (string) optional($order->status)->code;

        if ($statusCode !== LookupCode::ORDER_PENDING) {
            throw new InvalidArgumentException('Chi duoc chuyen sang PROCESSING khi don dang o trang thai PENDING.');
        }

        $processingStatusId = LookupHelper::getValueId(
            LookupCode::TYPE_ORDER_STATUS,
            LookupCode::ORDER_PROCESSING
        );

        $order->update([
            'status_id' => $processingStatusId,
        ]);
    }

    public function completeOrder(Order $order): void
    {
        $order->loadMissing(['status', 'details', 'orderType']);
        $statusCode = (string) optional($order->status)->code;

        if ($statusCode !== LookupCode::ORDER_PROCESSING) {
            throw new InvalidArgumentException('Chi duoc hoan thanh don dang o trang thai PROCESSING.');
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing(['details', 'orderType']);

            $orderTypeCode = (string) optional($order->orderType)->code;
            if ($orderTypeCode === '') {
                throw new InvalidArgumentException('Khong xac dinh duoc loai don hang.');
            }

            foreach ($order->details as $detail) {
                $this->applyInventoryForCompletedOrder(
                    orderTypeCode: $orderTypeCode,
                    orderId: (int) $order->id,
                    agencyId: (int) $order->agency_id,
                    toAgencyId: $order->to_agency_id ? (int) $order->to_agency_id : null,
                    itemId: (int) $detail->item_id,
                    quantity: (float) $detail->quantity,
                    createdBy: (int) $order->created_by,
                    note: $order->note,
                    adjustmentDirection: $this->extractAdjustmentDirection($order->note)
                );
            }

            $completedStatusId = LookupHelper::getValueId(
                LookupCode::TYPE_ORDER_STATUS,
                LookupCode::ORDER_COMPLETED
            );

            $order->update([
                'status_id' => $completedStatusId,
            ]);
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
        ?string $note,
        ?string $adjustmentDirection = null
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
            if (! $toAgencyId) {
                throw new InvalidArgumentException('Thieu dai ly nhan (to_agency_id).');
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

        if ($orderTypeCode === LookupCode::ORDER_ADJUSTMENT) {
            if ($adjustmentDirection === LookupCode::TRANSACTION_EXPORT) {
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

            if ($adjustmentDirection === LookupCode::TRANSACTION_IMPORT) {
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

            throw new InvalidArgumentException('Huong dieu chinh kho khong hop le.');
        }

        throw new InvalidArgumentException('Order type chua duoc ho tro.');
    }

    public function cancelOrder(Order $order, int $cancelledBy): void
    {
        $order->loadMissing('status');
        $statusCode = (string) optional($order->status)->code;

        if ($statusCode === LookupCode::ORDER_CANCELLED) {
            throw new InvalidArgumentException('Don hang da bi huy truoc do.');
        }

        DB::transaction(function () use ($order, $cancelledBy, $statusCode) {
            $cancelledStatusId = LookupHelper::getValueId(
                LookupCode::TYPE_ORDER_STATUS,
                LookupCode::ORDER_CANCELLED
            );

            if ($statusCode === LookupCode::ORDER_COMPLETED) {
                $this->inventoryService->rollbackByOrderId(
                    orderId: $order->id,
                    createdBy: $cancelledBy,
                    note: 'Huy don ' . $order->order_code
                );
            }

            $order->update(['status_id' => $cancelledStatusId]);
        });
    }

    /**
     * @param array<int, array{item_id:int, quantity:float, unit_price:float}> $detailsData
     * @return array<int, array{item_id:int, quantity:float, unit_price:float}>
     */
    private function normalizeDetailsData(array $detailsData): array
    {
        if (count($detailsData) === 0) {
            throw new InvalidArgumentException('Chi tiet don hang khong duoc rong.');
        }

        $normalized = [];
        $itemIds = [];

        foreach ($detailsData as $detailData) {
            $quantity = (float) ($detailData['quantity'] ?? 0);
            $unitPrice = (float) ($detailData['unit_price'] ?? 0);
            $itemId = (int) ($detailData['item_id'] ?? 0);

            if ($itemId <= 0) {
                throw new InvalidArgumentException('Mat hang khong hop le.');
            }

            if ($quantity <= 0) {
                throw new InvalidArgumentException('So luong phai > 0.');
            }

            if ($unitPrice < 0) {
                throw new InvalidArgumentException('Don gia khong hop le.');
            }

            if (in_array($itemId, $itemIds, true)) {
                throw new InvalidArgumentException('Khong duoc lap mat hang trong cung mot don.');
            }

            $itemIds[] = $itemId;
            $normalized[] = [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ];
        }

        return $normalized;
    }

    /**
     * @param array{agency_id:int,to_agency_id:?int,reference_order_id:?int,user_id:int,created_by:int,order_type_id:int,order_date:mixed,note:?string,adjustment_direction:?string} $orderData
     * @param array<int, array{item_id:int, quantity:float, unit_price:float}> $detailsData
     */
    private function validateOrderBusinessRules(array $orderData, array $detailsData, string $orderTypeCode): void
    {
        if ($orderTypeCode === LookupCode::ORDER_RETURN) {
            $this->validateReturnOrder($orderData, $detailsData);
            return;
        }

        if ($orderTypeCode === LookupCode::ORDER_ADJUSTMENT) {
            if (! in_array($orderData['adjustment_direction'] ?? null, [
                LookupCode::TRANSACTION_IMPORT,
                LookupCode::TRANSACTION_EXPORT,
            ], true)) {
                throw new InvalidArgumentException('ADJUSTMENT_ORDER can huong dieu chinh hop le.');
            }
        }
    }

    /**
     * @param array{agency_id:int,to_agency_id:?int,reference_order_id:?int,user_id:int,created_by:int,order_type_id:int,order_date:mixed,note:?string,adjustment_direction:?string} $orderData
     * @param array<int, array{item_id:int, quantity:float, unit_price:float}> $detailsData
     */
    private function validateReturnOrder(array $orderData, array $detailsData): void
    {
        $referenceOrderId = (int) ($orderData['reference_order_id'] ?? 0);
        if ($referenceOrderId <= 0) {
            throw new InvalidArgumentException('RETURN_ORDER can chon don goc.');
        }

        $salesTypeId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, LookupCode::ORDER_SALES);
        $cancelledStatusId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_STATUS, LookupCode::ORDER_CANCELLED);
        $completedStatusId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_STATUS, LookupCode::ORDER_COMPLETED);

        $referenceOrder = Order::query()
            ->with(['details'])
            ->whereKey($referenceOrderId)
            ->first();

        if (! $referenceOrder) {
            throw new InvalidArgumentException('Khong tim thay don goc de tra hang.');
        }

        if ((int) $referenceOrder->agency_id !== (int) $orderData['agency_id']) {
            throw new InvalidArgumentException('Don tra hang phai thuoc cung dai ly voi don goc.');
        }

        if ((int) $referenceOrder->order_type_id !== $salesTypeId) {
            throw new InvalidArgumentException('RETURN_ORDER hien chi ho tro tra hang cho SALES_ORDER da ban.');
        }

        if ((int) $referenceOrder->status_id !== $completedStatusId) {
            throw new InvalidArgumentException('Chi duoc tao RETURN_ORDER tu don SALES_ORDER da COMPLETED.');
        }

        if ((int) $referenceOrder->status_id === $cancelledStatusId) {
            throw new InvalidArgumentException('Khong the tra hang cho don goc da bi huy.');
        }

        $originalQuantities = [];
        foreach ($referenceOrder->details as $detail) {
            $originalQuantities[(int) $detail->item_id] = (float) $detail->quantity;
        }

        $returnOrderTypeId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, LookupCode::ORDER_RETURN);

        foreach ($detailsData as $detailData) {
            $itemId = $detailData['item_id'];
            $requestedQty = $detailData['quantity'];
            $originalQty = $originalQuantities[$itemId] ?? null;

            if ($originalQty === null) {
                throw new InvalidArgumentException('Mat hang tra khong ton tai trong don goc.');
            }

            $returnedQty = (float) OrderDetail::query()
                ->selectRaw('COALESCE(SUM(order_details.quantity), 0) AS aggregate_qty')
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->where('orders.reference_order_id', $referenceOrderId)
                ->where('orders.order_type_id', $returnOrderTypeId)
                ->where('orders.status_id', '!=', $cancelledStatusId)
                ->where('order_details.item_id', $itemId)
                ->value('aggregate_qty');

            $remainingQty = $originalQty - $returnedQty;
            if ($requestedQty > $remainingQty) {
                throw new InvalidArgumentException('So luong tra vuot qua so luong con co the tra cua don goc.');
            }
        }
    }

    private function resolveOrderTypeCode(int $orderTypeId): string
    {
        return (string) SysLookupValue::query()
            ->where('id', $orderTypeId)
            ->value('code');
    }

    private function generateOrderCode(): string
    {
        return 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
    }

    private function extractAdjustmentDirection(?string $note): ?string
    {
        if (! is_string($note) || $note === '') {
            return null;
        }

        if (str_contains($note, '[ADJUSTMENT:EXPORT]')) {
            return LookupCode::TRANSACTION_EXPORT;
        }

        if (str_contains($note, '[ADJUSTMENT:IMPORT]')) {
            return LookupCode::TRANSACTION_IMPORT;
        }

        return null;
    }
}
