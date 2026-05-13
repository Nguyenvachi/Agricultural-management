@extends('layouts.app')

@section('title', 'Danh sách đơn hàng')

@section('content')
    <h1>Đơn hàng</h1>
    <p><a href="{{ route('orders.create') }}">+ Tạo đơn</a></p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã đơn</th>
                <th>Đại lý</th>
                <th>Đại lý nhận</th>
                <th>Loại đơn</th>
                <th>Trạng thái</th>
                <th>Ngày</th>
                <th>Tổng tiền</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->order_code }}</td>
                    <td>{{ $order->agency?->name }}</td>
                    <td>{{ $order->toAgency?->name }}</td>
                    <td>{{ $order->orderType?->display_name }}</td>
                    <td>{{ $order->status?->display_name }}</td>
                    <td>{{ $order->order_date?->format('Y-m-d') }}</td>
                    <td>{{ $order->total_amount }}</td>
                    <td><a href="{{ route('orders.show', $order) }}">Xem</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        {{ $orders->links() }}
    </div>
@endsection
