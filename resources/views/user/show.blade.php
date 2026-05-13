@extends('layouts.app')
@section('title', 'Chi tiết người dùng — ' . $user->username)
@section('content')

<div class="page-header">
    <h1><i class="bi bi-person-badge me-2"></i>Người dùng: <span class="text-success">{{ $user->username }}</span></h1>
    <div class="d-flex gap-2">
        <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Sửa</a>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Quay lại</a>
    </div>
</div>

<div class="card shadow-sm" style="max-width:480px;">
    <div class="card-body p-0">
        <table class="table table-borderless mb-0">
            <tbody>
                <tr><th width="130">ID</th><td>{{ $user->id }}</td></tr>
                <tr><th>Username</th><td><code>{{ $user->username }}</code></td></tr>
                <tr><th>Họ tên</th><td><strong>{{ $user->full_name }}</strong></td></tr>
                <tr><th>Vai trò</th><td><span class="badge bg-info text-dark">{{ $user->role?->display_name }}</span></td></tr>
                <tr><th>Đại lý</th><td>{{ $user->agency?->name ?? '—' }}</td></tr>
                <tr><th>Điện thoại</th><td>{{ $user->phone ?? '—' }}</td></tr>
                <tr><th>Số đơn tạo</th><td>{{ $user->orders()->count() }} đơn</td></tr>
                <tr>
                    <th>Trạng thái</th>
                    <td>
                        @if ($user->is_active)
                            <span class="badge bg-success">✅ Hoạt động</span>
                        @else
                            <span class="badge bg-danger">❌ Vô hiệu hóa</span>
                        @endif
                    </td>
                </tr>
                <tr><th>Ngày tạo</th><td class="text-muted">{{ $user->created_at?->format('d/m/Y H:i') }}</td></tr>
            </tbody>
        </table>
    </div>
    @if ($user->is_active)
        <div class="card-footer bg-transparent border-top-0">
            <form action="{{ route('users.destroy', $user) }}" method="POST"
                  onsubmit="return confirm('Vô hiệu hóa {{ $user->username }}?')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-person-dash"></i> Vô hiệu hóa
                </button>
            </form>
        </div>
    @endif
</div>

@endsection
