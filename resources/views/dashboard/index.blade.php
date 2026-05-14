@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

    <div class="page-header">
        <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
    </div>

    {{-- ── KPI CARDS ── --}}
    <div class="row mb-4">
        {{-- Total Orders --}}
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #0d6efd;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <p class="text-muted small mb-1">Tổng Đơn Hàng</p>
                            <h3 class="mb-0" style="color:#0d6efd;">{{ number_format($totalOrders) }}</h3>
                        </div>
                        <div style="font-size:2.5rem; color:#0d6efd; opacity:0.15; flex-shrink:0;">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Items --}}
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #198754;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <p class="text-muted small mb-1">Tổng Mặt Hàng</p>
                            <h3 class="mb-0" style="color:#198754;">{{ number_format($totalItems) }}</h3>
                        </div>
                        <div style="font-size:2.5rem; color:#198754; opacity:0.15; flex-shrink:0;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Agencies --}}
        @if (auth()->user()->isAdmin())
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #fd7e14;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="flex-grow-1">
                                <p class="text-muted small mb-1">Tổng Đại Lý</p>
                                <h3 class="mb-0" style="color:#fd7e14;">{{ number_format($totalAgencies) }}</h3>
                            </div>
                            <div style="font-size:2.5rem; color:#fd7e14; opacity:0.15; flex-shrink:0;">
                                <i class="bi bi-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Total Stock --}}
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #6f42c1;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <p class="text-muted small mb-1">Tổng Tồn Kho</p>
                            <h3 class="mb-0" style="color:#6f42c1;">{{ number_format($totalStock, 2) }}</h3>
                        </div>
                        <div style="font-size:2.5rem; color:#6f42c1; opacity:0.15; flex-shrink:0;">
                            <i class="bi bi-graph-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Revenue --}}
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100 border-0 shadow-sm" style="border-top: 4px solid #dc3545;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="flex-grow-1">
                            <p class="text-muted small mb-1">Tổng Doanh Thu</p>
                            <h3 class="mb-0" style="color:#dc3545;">{{ number_format($totalRevenue, 0, '.', '.') }} ₫</h3>
                        </div>
                        <div style="font-size:2.5rem; color:#dc3545; opacity:0.15; flex-shrink:0;">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── MAIN CONTENT GRID ── --}}
    <div class="row">

        {{-- LEFT: CHART + RECENT ORDERS --}}
        <div class="col-lg-8 mb-4">

            {{-- Chart: Orders by Type --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Thống Kê Đơn Hàng Theo Loại</h5>
                </div>
                <div class="card-body">
                    @if (count(json_decode($chartLabels, true)) > 0)
                        <div style="height: 320px; max-height: 320px; display: flex; align-items: center;">
                            <canvas id="orderChart"></canvas>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i> Chưa có dữ liệu đơn hàng
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent Orders --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>5 Đơn Hàng Gần Đây</h5>
                </div>
                <div class="card-body p-0">
                    @if ($recentOrders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã Đơn</th>
                                        <th>Loại Đơn</th>
                                        <th>Đại Lý</th>
                                        <th>Trạng Thái</th>
                                        <th>Ngày</th>
                                        <th>Thành Tiền</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentOrders as $order)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="{{ route('orders.show', $order) }}" class="text-decoration-none">
                                                    {{ $order->order_code }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color:#6c757d;">
                                                    {{ optional($order->orderType)->display_name ?? '-' }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ optional($order->agency)->name ?? '-' }}
                                            </td>
                                            <td>
                                                @php
                                                    $statusCode = optional($order->status)->code;
                                                    $statusClass = match ($statusCode) {
                                                        'ORDER_PENDING' => 'warning',
                                                        'ORDER_PROCESSING' => 'info',
                                                        'ORDER_COMPLETED' => 'success',
                                                        'ORDER_CANCELLED' => 'danger',
                                                        default => 'secondary',
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">
                                                    {{ optional($order->status)->display_name ?? '-' }}
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $order->order_date->format('d/m/Y') }}</small>
                                            </td>
                                            <td class="fw-semibold">
                                                {{ number_format($order->total_amount, 0, '.', '.') }} ₫
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('orders.show', $order) }}"
                                                    class="btn btn-sm btn-outline-secondary" title="Xem chi tiết">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0 m-3">
                            <i class="bi bi-info-circle me-2"></i> Chưa có đơn hàng
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- RIGHT: LOW STOCK ALERTS --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm" style="height: 100%;">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2" style="color:#fd7e14;"></i>Cảnh Báo Tồn
                        Kho Thấp</h5>
                </div>
                <div class="card-body p-0">
                    @if ($lowStockItems->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach ($lowStockItems as $stock)
                                <div class="list-group-item border-start border-danger border-2 py-2 px-3">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 small">
                                                <a href="{{ route('items.show', $stock->item) }}"
                                                    class="text-decoration-none">
                                                    {{ optional($stock->item)->name ?? '-' }}
                                                </a>
                                            </h6>
                                            <p class="mb-0 small text-muted">
                                                <i
                                                    class="bi bi-building me-1"></i>{{ optional($stock->agency)->name ?? '-' }}
                                            </p>
                                        </div>
                                        <span class="badge bg-danger flex-shrink-0">
                                            {{ number_format($stock->quantity, 2) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-success mb-0 m-3 small">
                            <i class="bi bi-check-circle me-2"></i> Không có cảnh báo tồn kho
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ── CHART.JS SCRIPT ── --}}
    @if (count(json_decode($chartLabels, true)) > 0)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            const ctx = document.getElementById('orderChart').getContext('2d');
            const chartLabels = {!! $chartLabels !!};
            const chartData = {!! $chartData !!};
            const chartColors = {!! $chartColors !!};

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        data: chartData,
                        backgroundColor: chartColors.slice(0, chartLabels.length),
                        borderColor: '#fff',
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 12,
                                font: {
                                    size: 12
                                },
                                usePointStyle: true,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + ' đơn';
                                }
                            }
                        }
                    }
                }
            });
        </script>
    @endif

@endsection
