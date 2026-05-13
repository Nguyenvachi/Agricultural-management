@extends('layouts.app')

@section('title', 'Danh sách đại lý')

@section('content')
    <h1>Đại lý</h1>
    <p><a href="{{ route('agencies.create') }}">+ Tạo mới</a></p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã</th>
                <th>Tên</th>
                <th>Điện thoại</th>
                <th>Kích hoạt</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($agencies as $agency)
                <tr>
                    <td>{{ $agency->id }}</td>
                    <td>{{ $agency->code }}</td>
                    <td>{{ $agency->name }}</td>
                    <td>{{ $agency->phone }}</td>
                    <td>{{ $agency->is_active ? 'Yes' : 'No' }}</td>
                    <td>
                        <a href="{{ route('agencies.show', $agency) }}">Xem</a> |
                        <a href="{{ route('agencies.edit', $agency) }}">Sửa</a> |
                        <form action="{{ route('agencies.destroy', $agency) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Xóa đại lý này?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        {{ $agencies->links() }}
    </div>
@endsection
