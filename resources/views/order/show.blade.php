@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng')

@section('content')
    <h1>Chi tiết đơn hàng</h1>

    <p><strong>ID:</strong> {{ $order->id }}</p>
    <p><strong>Mã đơn:</strong> {{ $order->order_code }}</p>
    <p><strong>Đại lý:</strong> {{ $order->agency?->name }}</p>
    <p><strong>Đại lý nhận:</strong> {{ $order->toAgency?->name }}</p>
    <p><strong>Loại đơn:</strong> {{ $order->orderType?->display_name }}</p>
    <p><strong>Trạng thái:</strong> {{ $order->status?->display_name }}</p>
    <p><strong>Ngày đơn:</strong> {{ $order->order_date?->format('Y-m-d') }}</p>
    <p><strong>Tổng tiền:</strong> {{ $order->total_amount }}</p>
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

    <p><a href="{{ route('orders.index') }}">Quay lại</a></p>
@endsection
