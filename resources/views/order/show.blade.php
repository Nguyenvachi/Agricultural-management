@extends('layouts.app')
@section('title', 'Chi tiết đơn hàng - ' . $order->order_code)
@section('content')

    @php
        $statusCode = $order->status?->code;
        $typeCode = $order->orderType?->code;
        $canOperate = auth()->user()->isAdmin() || auth()->user()->isAgency();
    @endphp

    <div class="page-header">
        <h1><i class="bi bi-receipt me-2"></i>Đơn hàng <span class="text-success">{{ $order->order_code }}</span></h1>
        <div class="d-flex gap-2">
            @if ($canOperate && $statusCode === 'PENDING')
                <form action="{{ route('orders.process', $order) }}" method="POST"
                    onsubmit="return confirm('Chuyển đơn {{ $order->order_code }} sang PROCESSING?')">
                    @csrf
                    <button class="btn btn-primary btn-sm"><i class="bi bi-play-circle"></i> Xử lý</button>
                </form>
            @endif

            @if ($canOperate && $statusCode === 'PROCESSING')
                <form action="{{ route('orders.complete', $order) }}" method="POST"
                    onsubmit="return confirm('Hoàn thành đơn {{ $order->order_code }} và cập nhật kho?')">
                    @csrf
                    <button class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> Hoàn thành</button>
                </form>
            @endif

            @if ($canOperate && $statusCode !== 'CANCELLED')
                <form action="{{ route('orders.cancel', $order) }}" method="POST"
                    onsubmit="return confirm('Hủy đơn {{ $order->order_code }}?')">
                    @csrf
                    <button class="btn btn-danger btn-sm"><i class="bi bi-x-circle"></i> Hủy đơn</button>
                </form>
            @endif

            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Thông tin đơn hàng</div>
                <div class="card-body p-0">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th width="130">Mã đơn</th>
                                <td><code>{{ $order->order_code }}</code></td>
                            </tr>
                            <tr>
                                <th>Loại đơn</th>
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
                            </tr>
                            <tr>
                                <th>Trạng thái</th>
                                <td>
                                    @if ($statusCode === 'PENDING')
                                        <span class="badge bg-secondary fs-6">Cho xử lý</span>
                                    @elseif ($statusCode === 'PROCESSING')
                                        <span class="badge bg-warning text-dark fs-6">Đang xử lý</span>
                                    @elseif ($statusCode === 'COMPLETED')
                                        <span class="badge bg-success fs-6">Hoàn thành</span>
                                    @elseif ($statusCode === 'CANCELLED')
                                        <span class="badge bg-danger fs-6">Đã hủy</span>
                                    @else
                                        <span class="badge bg-light text-dark">{{ $order->status?->display_name }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Đại lý</th>
                                <td>{{ $order->agency?->name }}</td>
                            </tr>
                            @if ($order->toAgency)
                                <tr>
                                    <th>Đại lý nhận</th>
                                    <td>{{ $order->toAgency->name }}</td>
                                </tr>
                            @endif
                            @if ($order->referenceOrder)
                                <tr>
                                    <th>Đơn gốc</th>
                                    <td>
                                        <a href="{{ route('orders.show', $order->referenceOrder) }}">
                                            <code>{{ $order->referenceOrder->order_code }}</code>
                                        </a>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th>Ngày đơn</th>
                                <td>{{ $order->order_date?->format('d/m/Y') }}</td>
                            </tr>
                            @if ($order->note)
                                <tr>
                                    <th>Ghi chú</th>
                                    <td>{{ $order->note }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Chi tiết sản phẩm</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-bordered mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Mặt hàng</th>
                                <th>DVT</th>
                                <th class="text-end">Số lượng</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->details as $i => $detail)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $detail->item?->name }} <span
                                            class="text-muted small">({{ $detail->item?->code }})</span></td>
                                    <td>{{ $detail->item?->unit }}</td>
                                    <td class="text-end">{{ number_format((float) $detail->quantity, 2, '.', ',') }}</td>
                                    <td class="text-end">{{ number_format((float) $detail->unit_price, 0, ',', '.') }} d
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format((float) $detail->total_price, 0, ',', '.') }} d</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-success">
                            <tr>
                                <td colspan="5" class="text-end fw-bold">Tổng cộng:</td>
                                <td class="text-end fw-bold fs-6">
                                    {{ number_format((float) $order->total_amount, 0, ',', '.') }} d</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if ($statusCode === 'CANCELLED')
                <div class="alert alert-danger">
                    <strong>Đơn đã bị hủy.</strong>
                    @if ($order->inventoryTransactions()->exists())
                        Tồn kho đã được rollback tự động.
                    @endif
                </div>
            @elseif ($statusCode === 'PENDING')
                <div class="alert alert-secondary">
                    Đơn hàng đã được tạo và đang chờ xử lý. Chưa có cập nhật tồn kho.
                </div>
            @elseif ($statusCode === 'PROCESSING')
                <div class="alert alert-warning">
                    Đơn hàng đang được xử lý. Tồn kho sẽ chỉ được cập nhật khi đơn được COMPLETED.
                </div>
            @elseif ($statusCode === 'COMPLETED')
                <div class="alert alert-success">
                    Đơn hàng đã hoàn thành và tồn kho đã được cập nhật.
                </div>
            @endif
        </div>
    </div>

@endsection
