@extends('layouts.app')

@section('title', 'Danh sách mặt hàng')

@section('content')
    <h1>Mặt hàng</h1>
    <p><a href="{{ route('items.create') }}">+ Tạo mới</a></p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Danh mục</th>
                <th>Mã</th>
                <th>Tên</th>
                <th>Đơn vị</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->category?->name }}</td>
                    <td>{{ $item->code }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->unit }}</td>
                    <td>
                        <a href="{{ route('items.show', $item) }}">Xem</a> |
                        <a href="{{ route('items.edit', $item) }}">Sửa</a> |
                        <form action="{{ route('items.destroy', $item) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Xóa mặt hàng này?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        {{ $items->links() }}
    </div>
@endsection
