@extends('layouts.app')

@section('title', 'Tồn kho hiện tại')

@section('content')
    <h1>Tồn kho hiện tại</h1>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Đại lý</th>
                <th>Mặt hàng</th>
                <th>Số lượng</th>
                <th>Cập nhật</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inventories as $inv)
                <tr>
                    <td>{{ $inv->id }}</td>
                    <td>{{ $inv->agency?->name }}</td>
                    <td>{{ $inv->item?->name }}</td>
                    <td>{{ $inv->quantity }}</td>
                    <td>{{ $inv->updated_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        {{ $inventories->links() }}
    </div>
@endsection
