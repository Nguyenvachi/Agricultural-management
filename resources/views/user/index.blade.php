@extends('layouts.app')

@section('title', 'Danh sách người dùng')

@section('content')
    <h1>Người dùng</h1>
    <p><a href="{{ route('users.create') }}">+ Tạo người dùng</a></p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Họ tên</th>
                <th>Vai trò</th>
                <th>Đại lý</th>
                <th>Điện thoại</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->full_name }}</td>
                    <td>{{ $user->role?->display_name ?? $user->role?->code }}</td>
                    <td>{{ $user->agency?->name ?? '—' }}</td>
                    <td>{{ $user->phone ?? '—' }}</td>
                    <td>
                        @if ($user->is_active)
                            <span style="color: green;">Hoạt động</span>
                        @else
                            <span style="color: red;">Vô hiệu</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('users.show', $user) }}">Xem</a> |
                        <a href="{{ route('users.edit', $user) }}">Sửa</a> |
                        <form action="{{ route('users.destroy', $user) }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('Vô hiệu hóa người dùng {{ $user->username }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="color:red; background:none; border:none; cursor:pointer;">Vô hiệu</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        {{ $users->links() }}
    </div>
@endsection
