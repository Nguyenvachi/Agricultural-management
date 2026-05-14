@extends('layouts.app')
@section('title', 'Danh sách đại lý')
@section('content')

    <div class="page-header">
        <h1><i class="bi bi-building me-2"></i>Đại lý</h1>
        <a href="{{ route('agencies.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg"></i> Tạo mới
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-striped table-hover table-bordered mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Mã</th>
                        <th>Tên</th>
                        <th>Điện thoại</th>
                        <th>Địa chỉ</th>
                        <th>Kích hoạt</th>
                        <th class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agencies as $agency)
                        <tr>
                            <td class="text-muted small">{{ $agency->id }}</td>
                            <td><code>{{ $agency->code }}</code></td>
                            <td><strong>{{ $agency->name }}</strong></td>
                            <td>{{ $agency->phone ?? '—' }}</td>
                            <td class="text-truncate" style="max-width:200px;">{{ $agency->address ?? '—' }}</td>
                            <td>
                                @if ($agency->is_active)
                                    <span class="badge bg-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Vô hiệu</span>
                                @endif
                            </td>
                            <td class="text-center" style="white-space:nowrap;">
                                <a href="{{ route('agencies.show', $agency) }}" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('agencies.edit', $agency) }}" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('agencies.destroy', $agency) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Xóa đại lý {{ $agency->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Chưa có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $agencies->links() }}</div>

@endsection
