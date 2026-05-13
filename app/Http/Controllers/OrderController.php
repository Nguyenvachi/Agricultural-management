<?php

namespace App\Http\Controllers;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\Agency;
use App\Models\Item;
use App\Models\Order;
use App\Models\SysLookupValue;
use App\Models\User;
use App\Services\OrderService;
use InvalidArgumentException;

class OrderController extends Controller
{
    // ── Index: ownership filtering theo role ─────────────────────────
    public function index()
    {
        $user  = auth()->user();
        $query = Order::query()->with(['agency', 'toAgency', 'orderType', 'status']);

        if (! $user->isAdmin()) {
            if ($user->isAgency()) {
                // AGENCY: chỉ thấy đơn của agency mình
                $query->where('agency_id', $user->agency_id);
            } else {
                // FARMER / CUSTOMER: chỉ thấy đơn do mình tạo
                $query->where('created_by', $user->id);
            }
        }

        $orders = $query->orderByDesc('id')->paginate(15);

        return view('order.index', compact('orders'));
    }

    // ── Create: filter order types theo role ─────────────────────────
    public function create()
    {
        $user = auth()->user();

        $agencies = Agency::query()->orderBy('name')->get();
        $items    = Item::query()->orderBy('name')->get();
        $users    = User::query()->orderBy('id')->get();

        // Filter order types theo role
        $allOrderTypes = LookupHelper::getValuesByTypeCode(LookupCode::TYPE_ORDER_TYPE);

        if ($user->isAdmin()) {
            $orderTypes = $allOrderTypes; // full
        } elseif ($user->isAgency()) {
            // AGENCY: không tạo ADJUSTMENT
            $orderTypes = $allOrderTypes->reject(fn($t) => $t->code === LookupCode::ORDER_ADJUSTMENT);
        } else {
            // FARMER: chỉ SALES_ORDER
            $orderTypes = $allOrderTypes->filter(fn($t) => $t->code === LookupCode::ORDER_SALES);
        }

        // Đơn COMPLETED để chọn reference_order_id (RETURN_ORDER)
        $completedStatusId = LookupHelper::getValueId(LookupCode::TYPE_ORDER_STATUS, LookupCode::ORDER_COMPLETED);
        $completedOrders   = Order::query()
            ->where('status_id', $completedStatusId)
            ->with(['agency', 'orderType'])
            ->orderByDesc('id')
            ->get();

        // AGENCY tự điền agency_id của mình
        $defaultAgencyId = $user->isAgency() ? $user->agency_id : null;

        return view('order.create', compact(
            'agencies', 'items', 'orderTypes', 'users',
            'completedOrders', 'defaultAgencyId'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        try {
            $orderData   = $request->validatedOrderData();
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
            ->with('success', 'Tạo đơn hàng thành công (đã cập nhật kho).');
    }

    // ── Show: ownership check ─────────────────────────────────────────
    public function show(Order $order)
    {
        $user = auth()->user();

        if (! $user->isAdmin()) {
            if ($user->isAgency()) {
                // AGENCY chỉ xem đơn của agency mình
                abort_if($order->agency_id !== (int) $user->agency_id, 403, 'Bạn không có quyền xem đơn này.');
            } else {
                // FARMER / CUSTOMER: chỉ xem đơn do mình tạo
                abort_if($order->created_by !== $user->id, 403, 'Bạn không có quyền xem đơn này.');
            }
        }

        $order->load(['agency', 'toAgency', 'orderType', 'status', 'details.item', 'referenceOrder']);

        return view('order.show', compact('order'));
    }

    // ── Cancel: ownership check (chỉ ADMIN hoặc AGENCY đơn của mình) ─
    public function cancel(Order $order, OrderService $orderService)
    {
        $user = auth()->user();

        if (! $user->isAdmin()) {
            // AGENCY chỉ hủy đơn của agency mình
            abort_if($order->agency_id !== (int) $user->agency_id, 403, 'Bạn không có quyền hủy đơn này.');
        }

        try {
            $orderService->cancelOrder(order: $order, cancelledBy: $user->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Đã hủy đơn hàng và rollback tồn kho thành công.');
    }
}
