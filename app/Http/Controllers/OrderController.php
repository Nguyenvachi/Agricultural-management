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
        $user = auth()->user();
        $query = Order::query()->with(['agency', 'toAgency', 'orderType', 'status']);

        if (! $user->isAdmin()) {
            if ($user->isAgency()) {
                $query->where('agency_id', $user->agency_id);
            } else {
                $query->where('created_by', $user->id);
            }
        }

        $orders = $query->orderByDesc('id')->paginate(15);

        return view('order.index', compact('orders'));
    }

    public function create()
    {
        $user = auth()->user();

        $agencies = Agency::query()->orderBy('name')->get();
        $items = Item::query()->orderBy('name')->get();
        $users = $user->isAdmin()
            ? User::query()->orderBy('id')->get()
            : User::query()->whereKey($user->id)->get();

        $allOrderTypes = LookupHelper::getValuesByTypeCode(LookupCode::TYPE_ORDER_TYPE);

        if ($user->isAdmin()) {
            $orderTypes = $allOrderTypes;
        } elseif ($user->isAgency()) {
            $orderTypes = $allOrderTypes->reject(fn ($t) => $t->code === LookupCode::ORDER_ADJUSTMENT);
        } else {
            $orderTypes = $allOrderTypes->filter(fn ($t) => $t->code === LookupCode::ORDER_SALES);
        }

        $completedStatusId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_STATUS, LookupCode::ORDER_COMPLETED);
        $salesOrderTypeId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_TYPE, LookupCode::ORDER_SALES);

        $completedOrdersQuery = Order::query()
            ->where('status_id', $completedStatusId)
            ->where('order_type_id', $salesOrderTypeId)
            ->with(['agency', 'orderType'])
            ->orderByDesc('id');

        if ($user->isAgency()) {
            $completedOrdersQuery->where('agency_id', $user->agency_id);
        }

        $completedOrders = $completedOrdersQuery->get();
        $defaultAgencyId = $user->isAgency() ? $user->agency_id : null;

        return view('order.create', compact(
            'agencies',
            'items',
            'orderTypes',
            'users',
            'completedOrders',
            'defaultAgencyId'
        ));
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
            return back()->withInput()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Tao don hang thanh cong va dang cho xu ly.');
    }

    public function show(Order $order)
    {
        $user = auth()->user();

        if (! $user->isAdmin()) {
            if ($user->isAgency()) {
                abort_if($order->agency_id !== (int) $user->agency_id, 403, 'Ban khong co quyen xem don nay.');
            } else {
                abort_if($order->created_by !== $user->id, 403, 'Ban khong co quyen xem don nay.');
            }
        }

        $order->load(['agency', 'toAgency', 'orderType', 'status', 'details.item', 'referenceOrder']);

        return view('order.show', compact('order'));
    }

    public function process(Order $order, OrderService $orderService)
    {
        $user = auth()->user();
        $this->ensureAgencyOwnership($order, $user);

        try {
            $orderService->markProcessing($order);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Don hang da duoc chuyen sang trang thai PROCESSING.');
    }

    public function complete(Order $order, OrderService $orderService)
    {
        $user = auth()->user();
        $this->ensureAgencyOwnership($order, $user);

        try {
            $orderService->completeOrder($order);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Don hang da duoc COMPLETED va kho da cap nhat thanh cong.');
    }

    public function cancel(Order $order, OrderService $orderService)
    {
        $user = auth()->user();
        $this->ensureAgencyOwnership($order, $user);

        try {
            $orderService->cancelOrder(order: $order, cancelledBy: $user->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Da huy don hang thanh cong.');
    }

    private function ensureAgencyOwnership(Order $order, User $user): void
    {
        if (! $user->isAdmin()) {
            abort_if($order->agency_id !== (int) $user->agency_id, 403, 'Ban khong co quyen thao tac don nay.');
        }
    }
}
