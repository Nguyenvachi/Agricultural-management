@extends('layouts.app')

@section('title', 'Lịch sử biến động kho')

@section('content')
    <h1>Lịch sử biến động kho</h1>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ngày</th>
                <th>Đại lý</th>
                <th>Mặt hàng</th>
                <th>Loại</th>
                <th>Thay đổi</th>
                <th>Trước</th>
                <th>Sau</th>
                <th>Order</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $tx)
                <tr>
                    <td>{{ $tx->id }}</td>
                    <td>{{ $tx->created_at }}</td>
                    <td>{{ $tx->agency?->name }}</td>
                    <td>{{ $tx->item?->name }}</td>
                    <td>{{ $tx->transactionType?->display_name }}</td>
                    <td>{{ $tx->quantity_change }}</td>
                    <td>{{ $tx->balance_before }}</td>
                    <td>{{ $tx->balance_after }}</td>
                    <td>{{ $tx->order_id }}</td>
                    <td>{{ $tx->note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        {{ $transactions->links() }}
    </div>
@endsection
