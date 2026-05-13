@extends('layouts.app')
@section('title', 'Danh sách người dùng')
@section('content')

<div class="page-header">
    <h1><i class="bi bi-people me-2"></i>Người dùng</h1>
    <a href="{{ route('users.create') }}" class="btn btn-success btn-sm">
        <i class="bi bi-plus-lg"></i> Tạo mới
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Họ tên</th>
                    <th>Vai trò</th>
                    <th>Đại lý</th>
                    <th>Điện thoại</th>
                    <th>Trạng thái</th>
                    <th class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="text-muted small">{{ $user->id }}</td>
                        <td><code>{{ $user->username }}</code></td>
                        <td><strong>{{ $user->full_name }}</strong></td>
                        <td><span class="badge bg-info text-dark">{{ $user->role?->display_name ?? $user->role?->code }}</span></td>
                        <td>{{ $user->agency?->name ?? '—' }}</td>
                        <td>{{ $user->phone ?? '—' }}</td>
                        <td>
                            @if ($user->is_active)
                                <span class="badge bg-success">Hoạt động</span>
                            @else
                                <span class="badge bg-secondary">Vô hiệu</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('users.show', $user) }}" class="btn btn-outline-info btn-sm"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil"></i></a>
                            @if ($user->is_active)
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Vô hiệu hóa {{ $user->username }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" title="Vô hiệu hóa">
                                        <i class="bi bi-person-dash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">Chưa có người dùng</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>

@endsection
