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
            throw new InvalidArgumentException('Chỉ được chuyển sang PROCESSING khi đơn đang ở trạng thái PENDING.');
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
            throw new InvalidArgumentException('Chỉ được hoàn thành đơn đang ở trạng thái PROCESSING.');
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing(['details', 'orderType']);

            $orderTypeCode = (string) optional($order->orderType)->code;
            if ($orderTypeCode === '') {
                throw new InvalidArgumentException('Không xác định được loại đơn hàng.');
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

            throw new InvalidArgumentException('Hướng điều chỉnh kho không hợp lệ.');
        }

        throw new InvalidArgumentException('Loại đơn hàng chưa được hỗ trợ.');
    }

    public function cancelOrder(Order $order, int $cancelledBy): void
    {
        $order->loadMissing('status');
        $statusCode = (string) optional($order->status)->code;

        if ($statusCode === LookupCode::ORDER_CANCELLED) {
            throw new InvalidArgumentException('Đơn hàng đã bị hủy trước đó.');
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
                    note: 'Hủy đơn ' . $order->order_code
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
            throw new InvalidArgumentException('Chi tiết đơn hàng không được rỗng.');
        }

        $normalized = [];
        $itemIds = [];

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

            if (in_array($itemId, $itemIds, true)) {
                throw new InvalidArgumentException('Không được lặp mặt hàng trong cùng một đơn.');
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
                throw new InvalidArgumentException('ADJUSTMENT_ORDER cần hướng điều chỉnh hợp lệ.');
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
            throw new InvalidArgumentException('RETURN_ORDER cần chọn đơn gốc.');
        }

        $salesTypeId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, LookupCode::ORDER_SALES);
        $cancelledStatusId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_STATUS, LookupCode::ORDER_CANCELLED);
        $completedStatusId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_STATUS, LookupCode::ORDER_COMPLETED);

        $referenceOrder = Order::query()
            ->with(['details'])
            ->whereKey($referenceOrderId)
            ->first();

        if (! $referenceOrder) {
            throw new InvalidArgumentException('Không tìm thấy đơn gốc để trả hàng.');
        }

        if ((int) $referenceOrder->agency_id !== (int) $orderData['agency_id']) {
            throw new InvalidArgumentException('Đơn trả hàng phải thuộc cùng đại lý với đơn gốc.');
        }

        if ((int) $referenceOrder->order_type_id !== $salesTypeId) {
            throw new InvalidArgumentException('RETURN_ORDER hiện nay chỉ hỗ trợ trả hàng cho SALES_ORDER đã bán.');
        }

        if ((int) $referenceOrder->status_id !== $completedStatusId) {
            throw new InvalidArgumentException('Chỉ được tạo RETURN_ORDER từ đơn SALES_ORDER đã COMPLETED.');
        }

        if ((int) $referenceOrder->status_id === $cancelledStatusId) {
            throw new InvalidArgumentException('Không thể trả hàng cho đơn gốc đã bị hủy.');
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
                throw new InvalidArgumentException('Mặt hàng trả không tồn tại trong đơn gốc.');
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
                throw new InvalidArgumentException('Số lượng trả vượt quá số lượng còn có thể trả của đơn gốc.');
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
