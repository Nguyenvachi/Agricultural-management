<?php

namespace App\Http\Controllers;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Models\Agency;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = auth()->user();
        $isAgency = $user->isAgency();
        $agencyId = $user->agency_id;

        // FIX 1: Authorization - FARMER/CUSTOMER không được vào dashboard
        if ($user->isFarmer() || $user->isCustomer()) {
            return redirect()->route('orders.index')
                ->with('warning', 'Bạn không có quyền truy cập dashboard.');
        }

        // KPI 1: Tổng đơn hàng
        $totalOrdersQuery = Order::query();
        if ($isAgency) {
            $totalOrdersQuery->where('agency_id', $agencyId);
        } elseif (!$user->isAdmin()) {
            $totalOrdersQuery->where('created_by', $user->id);
        }
        $totalOrders = $totalOrdersQuery->count();

        // KPI 2: Tổng mặt hàng
        $totalItems = Item::query()->count();

        // KPI 3: Tổng đại lý
        $totalAgencies = Agency::query()->where('is_active', true)->count();

        // KPI 4: Tổng tồn kho (SUM quantity across all inventories)
        $totalStockQuery = Inventory::query();
        if ($isAgency) {
            $totalStockQuery->where('agency_id', $agencyId);
        }
        $totalStock = $totalStockQuery
            ->selectRaw('SUM(quantity) as total_quantity')
            ->first()
            ->total_quantity ?? 0;

        // KPI 5: Tổng doanh thu SALES_ORDER (chỉ COMPLETED orders)
        $salesOrderTypeId = LookupHelper::getValueId(
            LookupCode::TYPE_ORDER_TYPE,
            LookupCode::ORDER_SALES
        );

        $completedStatusId = LookupHelper::getValueId(
            LookupCode::TYPE_ORDER_STATUS,
            LookupCode::ORDER_COMPLETED
        );

        $revenueQuery = Order::query()
            ->where('order_type_id', $salesOrderTypeId)
            ->where('status_id', $completedStatusId)
            ->when($isAgency, function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();
        $totalRevenue = $revenueQuery?->total_revenue ?? 0;

        // Danh sách 5 đơn hàng gần đây
        $recentOrdersQuery = Order::query()
            ->with(['agency', 'toAgency', 'orderType', 'status', 'details']);

        if ($isAgency) {
            $recentOrdersQuery->where('agency_id', $agencyId);
        } elseif (!$user->isAdmin()) {
            $recentOrdersQuery->where('created_by', $user->id);
        }

        $recentOrders = $recentOrdersQuery
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Cảnh báo tồn kho thấp (quantity < 10)
        $lowStockItemsQuery = Inventory::query()
            ->with(['item', 'agency'])
            ->where('quantity', '<', 10)
            ->orderBy('quantity')
            ->limit(10);

        if ($isAgency) {
            $lowStockItemsQuery->where('agency_id', $agencyId);
        }

        $lowStockItems = $lowStockItemsQuery->get();

        // Dữ liệu cho biểu đồ: số đơn theo loại order_type (FIX 3: eager loading)
        $orderTypeCountsQuery = Order::query()
            ->selectRaw('order_type_id, COUNT(*) as count')
            ->groupBy('order_type_id')
            ->with('orderType');

        if ($isAgency) {
            $orderTypeCountsQuery->where('agency_id', $agencyId);
        }

        $orderTypeCounts = $orderTypeCountsQuery->get();

        // Chuyển dữ liệu để vẽ biểu đồ (FIX 4: safe rendering)
        $chartLabels = [];
        $chartData = [];
        $chartColors = ['#198754', '#0d6efd', '#fd7e14', '#dc3545', '#6f42c1'];

        foreach ($orderTypeCounts as $index => $record) {
            $chartLabels[] = optional($record->orderType)->display_name ?? '-';
            $chartData[] = $record->count;
        }

        return view('dashboard.index', [
            'totalOrders'    => $totalOrders,
            'totalItems'     => $totalItems,
            'totalAgencies'  => $totalAgencies,
            'totalStock'     => $totalStock,
            'totalRevenue'   => $totalRevenue,
            'recentOrders'   => $recentOrders,
            'lowStockItems'  => $lowStockItems,
            'chartLabels'    => json_encode($chartLabels),
            'chartData'      => json_encode($chartData),
            'chartColors'    => json_encode($chartColors),
        ]);
    }
}
