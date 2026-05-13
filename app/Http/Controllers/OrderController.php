<?php

namespace App\Http\Controllers;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\Agency;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::query()
            ->with(['agency', 'toAgency', 'orderType', 'status'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('order.index', compact('orders'));
    }

    public function create()
    {
        $agencies = Agency::query()->orderBy('name')->get();
        $items = Item::query()->orderBy('name')->get();
        $orderTypes = LookupHelper::getValuesByTypeCode(LookupCode::TYPE_ORDER_TYPE);
        $users = User::query()->orderBy('id')->get();

        return view('order.create', compact('agencies', 'items', 'orderTypes', 'users'));
    }

    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        try {
            $orderData = $request->validatedOrderData();
            $firstDetail = $request->validatedDetailData();
            $extraDetails = $request->validatedExtraDetailsData();

            if (count($extraDetails) > 0) {
                $order = $orderService->createOrderWithDetails(
                    orderData: $orderData,
                    detailsData: array_merge([$firstDetail], $extraDetails),
                );
            } else {
                $order = $orderService->createOrderWithOneDetail(
                    orderData: $orderData,
                    detailData: $firstDetail,
                );
            }
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Tạo đơn hàng thành công (đã cập nhật kho).');
    }

    public function show(Order $order)
    {
        $order->load(['agency', 'toAgency', 'orderType', 'status', 'details.item']);

        return view('order.show', compact('order'));
    }

    /**
     * Hủy đơn hàng: rollback tồn kho + đổi status → CANCELLED.
     */
    public function cancel(Order $order, OrderService $orderService)
    {
        try {
            // TODO: thay bằng auth()->id() khi có module auth
            $cancelledBy = $order->user_id ?? 1;
            $orderService->cancelOrder(order: $order, cancelledBy: (int) $cancelledBy);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Đã hủy đơn hàng và rollback tồn kho thành công.');
    }
}
