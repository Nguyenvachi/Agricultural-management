@extends('layouts.app')
@section('title', 'Danh sách đơn hàng')
@section('content')

    <div class="page-header">
        <h1><i class="bi bi-receipt me-2"></i>Đơn hàng</h1>
        @if (auth()->user()->isAdmin() || auth()->user()->isAgency() || auth()->user()->isFarmer())
            <a href="{{ route('orders.create') }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg"></i> Tạo đơn hàng
            </a>
        @endif
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Loại đơn</th>
                        <th>Đại lý</th>
                        <th>Đại lý nhận</th>
                        <th>Trạng thái</th>
                        <th>Ngày đơn</th>
                        <th class="text-end">Tổng tiền</th>
                        <th class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            $typeCode = $order->orderType?->code;
                            $statusCode = $order->status?->code;
                            $canOperate = auth()->user()->isAdmin() || auth()->user()->isAgency();
                        @endphp
                        <tr>
                            <td><a href="{{ route('orders.show', $order) }}"><code>{{ $order->order_code }}</code></a></td>
                            <td>
                                @if ($typeCode === 'PURCHASE_ORDER')
                                    <span class="badge bg-primary">Nhập hàng</span>
                                @elseif ($typeCode === 'SALES_ORDER')
                                    <span class="badge bg-warning text-dark">Bán hàng</span>
                                @elseif ($typeCode === 'INTERNAL_TRANSFER')
                                    <span class="badge bg-info text-dark">Chuyển kho</span>
                                @elseif ($typeCode === 'RETURN_ORDER')
                                    <span class="badge bg-secondary">Trả hàng</span>
                                @elseif ($typeCode === 'ADJUSTMENT_ORDER')
                                    <span class="badge bg-dark">Điều chỉnh</span>
                                @else
                                    <span class="badge bg-light text-dark">{{ $order->orderType?->display_name }}</span>
                                @endif
                            </td>
                            <td>{{ $order->agency?->name }}</td>
                            <td>{{ $order->toAgency?->name ?? '-' }}</td>
                            <td>
                                @if ($statusCode === 'PENDING')
                                    <span class="badge bg-secondary">Chờ xử lý</span>
                                @elseif ($statusCode === 'PROCESSING')
                                    <span class="badge bg-warning text-dark">Đang xử lý</span>
                                @elseif ($statusCode === 'COMPLETED')
                                    <span class="badge bg-success">Hoàn thành</span>
                                @elseif ($statusCode === 'CANCELLED')
                                    <span class="badge bg-danger">Đã hủy</span>
                                @else
                                    <span class="badge bg-light text-dark">{{ $order->status?->display_name }}</span>
                                @endif
                            </td>
                            <td>{{ $order->order_date?->format('d/m/Y') }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $order->total_amount, 0, ',', '.') }}
                                d</td>
                            <td class="text-center text-nowrap">
                                <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if ($canOperate && $statusCode === 'PENDING')
                                        <form action="{{ route('orders.process', $order) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Chuyển đơn {{ $order->order_code }} sang PROCESSING?')">
                                            @csrf
                                            <button class="btn btn-outline-primary btn-sm" title="Xử lý đơn">
                                                <i class="bi bi-play-circle"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if ($canOperate && $statusCode === 'PROCESSING')
                                        <form action="{{ route('orders.complete', $order) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Hoàn thành đơn {{ $order->order_code }} và cập nhật kho?')">
                                            @csrf
                                            <button class="btn btn-outline-success btn-sm" title="Hoàn thành đơn">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if ($canOperate && $statusCode !== 'CANCELLED')
                                        <form action="{{ route('orders.cancel', $order) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Hủy đơn {{ $order->order_code }}?')">
                                            @csrf
                                            <button class="btn btn-outline-danger btn-sm" title="Hủy đơn">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">Chưa có đơn hàng nào</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">{{ $orders->links() }}</div>

@endsection
