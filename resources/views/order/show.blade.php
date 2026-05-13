@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng — ' . $order->order_code)

@section('content')
    <h1>Chi tiết đơn hàng</h1>

    {{-- ─── THÔNG TIN CHUNG ──────────────────────────── --}}
    <table border="1" cellpadding="6" cellspacing="0" style="min-width:420px; margin-bottom:16px;">
        <tbody>
            <tr>
                <td><strong>Mã đơn</strong></td>
                <td>{{ $order->order_code }}</td>
            </tr>
            <tr>
                <td><strong>Loại đơn</strong></td>
                <td>{{ $order->orderType?->display_name }} ({{ $order->orderType?->code }})</td>
            </tr>
            <tr>
                <td><strong>Trạng thái</strong></td>
                <td>
                    @php $statusCode = $order->status?->code; @endphp
                    @if ($statusCode === 'COMPLETED')
                        <span style="color:green; font-weight:bold;">✅ {{ $order->status?->display_name }}</span>
                    @elseif ($statusCode === 'CANCELLED')
                        <span style="color:red; font-weight:bold;">❌ {{ $order->status?->display_name }}</span>
                    @elseif ($statusCode === 'PROCESSING')
                        <span style="color:orange; font-weight:bold;">🔄 {{ $order->status?->display_name }}</span>
                    @else
                        <span>{{ $order->status?->display_name }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Đại lý</strong></td>
                <td>{{ $order->agency?->name }} ({{ $order->agency?->code }})</td>
            </tr>
            @if ($order->toAgency)
                <tr>
                    <td><strong>Đại lý nhận</strong></td>
                    <td>{{ $order->toAgency->name }} ({{ $order->toAgency->code }})</td>
                </tr>
            @endif
            @if ($order->referenceOrder)
                <tr>
                    <td><strong>Đơn gốc (ref)</strong></td>
                    <td>
                        <a href="{{ route('orders.show', $order->referenceOrder) }}">
                            {{ $order->referenceOrder->order_code }}
                        </a>
                    </td>
                </tr>
            @endif
            <tr>
                <td><strong>Ngày đơn</strong></td>
                <td>{{ $order->order_date?->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Tổng tiền</strong></td>
                <td><strong>{{ number_format((float) $order->total_amount, 0, ',', '.') }} đ</strong></td>
            </tr>
            @if ($order->note)
                <tr>
                    <td><strong>Ghi chú</strong></td>
                    <td>{{ $order->note }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- ─── CHI TIẾT SẢN PHẨM ────────────────────────── --}}
    <h3>Chi tiết sản phẩm</h3>
    <table border="1" cellpadding="6" cellspacing="0" style="min-width:500px;">
        <thead>
            <tr>
                <th>#</th>
                <th>Mặt hàng</th>
                <th>Đơn vị</th>
                <th style="text-align:right;">Số lượng</th>
                <th style="text-align:right;">Đơn giá</th>
                <th style="text-align:right;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->details as $i => $d)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $d->item?->name }} ({{ $d->item?->code }})</td>
                    <td>{{ $d->item?->unit }}</td>
                    <td style="text-align:right;">{{ number_format((float) $d->quantity, 2, '.', ',') }}</td>
                    <td style="text-align:right;">{{ number_format((float) $d->unit_price, 0, ',', '.') }} đ</td>
                    <td style="text-align:right;">{{ number_format((float) $d->total_price, 0, ',', '.') }} đ</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;"><strong>Tổng cộng:</strong></td>
                <td style="text-align:right;"><strong>{{ number_format((float) $order->total_amount, 0, ',', '.') }} đ</strong></td>
            </tr>
        </tfoot>
    </table>

    <hr>

    {{-- ─── HÀNH ĐỘNG ─────────────────────────────────── --}}
    @if ($order->status?->code !== 'CANCELLED')
        <form action="{{ route('orders.cancel', $order) }}" method="POST" style="display:inline;"
              onsubmit="return confirm('Bạn có chắc muốn HỦY đơn {{ $order->order_code }}?\nTồn kho sẽ được rollback.')">
            @csrf
            <button type="submit" style="color:red; padding:6px 16px;">⛔ Hủy đơn hàng</button>
        </form>
    @else
        <p><em style="color:red;">Đơn đã bị hủy — không thể thực hiện thêm thao tác.</em></p>
    @endif

    <a href="{{ route('orders.index') }}" style="margin-left:16px;">← Quay lại danh sách</a>
@endsection
