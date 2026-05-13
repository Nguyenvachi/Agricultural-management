@extends('layouts.app')

@section('title', 'Chi tiết người dùng — ' . $user->username)

@section('content')
    <h1>Chi tiết người dùng</h1>

    <p><strong>ID:</strong> {{ $user->id }}</p>
    <p><strong>Username:</strong> {{ $user->username }}</p>
    <p><strong>Họ tên:</strong> {{ $user->full_name }}</p>
    <p><strong>Vai trò:</strong> {{ $user->role?->display_name }} ({{ $user->role?->code }})</p>
    <p><strong>Đại lý:</strong> {{ $user->agency?->name ?? '—' }}</p>
    <p><strong>Điện thoại:</strong> {{ $user->phone ?? '—' }}</p>
    <p>
        <strong>Trạng thái:</strong>
        @if ($user->is_active)
            <span style="color: green; font-weight: bold;">Đang hoạt động ✅</span>
        @else
            <span style="color: red; font-weight: bold;">Vô hiệu hóa ❌</span>
        @endif
    </p>
    <p><strong>Ngày tạo:</strong> {{ $user->created_at?->format('d/m/Y H:i') }}</p>

    <hr>

    <a href="{{ route('users.edit', $user) }}">✏️ Sửa thông tin</a> |

    @if ($user->is_active)
        <form action="{{ route('users.destroy', $user) }}" method="POST" style="display: inline;"
              onsubmit="return confirm('Vô hiệu hóa người dùng {{ $user->username }}?')">
            @csrf
            @method('DELETE')
            <button type="submit" style="color: red; background: none; border: none; cursor: pointer;">
                🚫 Vô hiệu hóa
            </button>
        </form>
    @endif

    <p><a href="{{ route('users.index') }}">← Quay lại danh sách</a></p>
@endsection
