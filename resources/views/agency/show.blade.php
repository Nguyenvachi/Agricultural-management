@extends('layouts.app')
@section('title', 'Chi tiết đại lý — ' . $agency->name)
@section('content')

<div class="page-header">
    <h1><i class="bi bi-building me-2"></i>Chi tiết đại lý</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('agencies.edit', $agency) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil"></i> Sửa
        </a>
        <a href="{{ route('agencies.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>
</div>

<div class="card shadow-sm" style="max-width:500px;">
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tbody>
                <tr><th width="140">ID</th><td>{{ $agency->id }}</td></tr>
                <tr><th>Mã</th><td><code>{{ $agency->code }}</code></td></tr>
                <tr><th>Tên</th><td><strong>{{ $agency->name }}</strong></td></tr>
                <tr><th>Địa chỉ</th><td>{{ $agency->address ?? '—' }}</td></tr>
                <tr><th>Điện thoại</th><td>{{ $agency->phone ?? '—' }}</td></tr>
                <tr>
                    <th>Trạng thái</th>
                    <td>
                        @if ($agency->is_active)
                            <span class="badge bg-success">✅ Hoạt động</span>
                        @else
                            <span class="badge bg-secondary">❌ Vô hiệu</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
