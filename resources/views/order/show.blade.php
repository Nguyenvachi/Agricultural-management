@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng')

@section('content')
    <h1>Chi tiết đơn hàng</h1>

    <p><strong>ID:</strong> {{ $order->id }}</p>
    <p><strong>Mã đơn:</strong> {{ $order->order_code }}</p>
    <p><strong>Đại lý:</strong> {{ $order->agency?->name }}</p>
    <p><strong>Đại lý nhận:</strong> {{ $order->toAgency?->name }}</p>
    <p><strong>Loại đơn:</strong> {{ $order->orderType?->display_name }}</p>
    <p>
        <strong>Trạng thái:</strong>
        @if ($order->status?->code === 'CANCELLED')
            <span style="color: red; font-weight: bold;">{{ $order->status?->display_name }} ❌</span>
        @elseif ($order->status?->code === 'COMPLETED')
            <span style="color: green; font-weight: bold;">{{ $order->status?->display_name }} ✅</span>
        @else
            <span>{{ $order->status?->display_name }}</span>
        @endif
    </p>
    <p><strong>Ngày đơn:</strong> {{ $order->order_date?->format('d/m/Y') }}</p>
    <p><strong>Tổng tiền:</strong> {{ number_format((float) $order->total_amount, 0, ',', '.') }} đ</p>
    <p><strong>Ghi chú:</strong> {{ $order->note }}</p>

    <hr>

    <h3>Chi tiết</h3>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>Mặt hàng</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->details as $d)
                <tr>
                    <td>{{ $d->item?->name }}</td>
                    <td>{{ $d->quantity }}</td>
                    <td>{{ $d->unit_price }}</td>
                    <td>{{ $d->total_price }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <hr>

    @if ($order->status?->code !== 'CANCELLED')
        <form action="{{ route('orders.cancel', $order) }}" method="POST" style="display: inline;"
              onsubmit="return confirm('Bạn có chắc muốn HỦY đơn {{ $order->order_code }}? Tồn kho sẽ được rollback.')">
            @csrf
            <button type="submit" style="color: red;">⛔ Hủy đơn hàng</button>
        </form>
    @else
        <p><em style="color: red;">Đơn hàng này đã bị hủy. Không thể thực hiện thêm thao tác.</em></p>
    @endif

    <p><a href="{{ route('orders.index') }}">← Quay lại danh sách</a></p>
@endsection
