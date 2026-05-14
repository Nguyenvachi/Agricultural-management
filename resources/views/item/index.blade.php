@extends('layouts.app')
@section('title', 'Danh sách mặt hàng')
@section('content')

    <div class="page-header">
        <h1><i class="bi bi-box-seam me-2"></i>Mặt hàng</h1>
        <a href="{{ route('items.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg"></i> Tạo mới
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-striped table-hover table-bordered mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Danh mục</th>
                        <th>Mã</th>
                        <th>Tên</th>
                        <th>Đơn vị</th>
                        <th class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="text-muted small">{{ $item->id }}</td>
                            <td><span class="badge bg-secondary">{{ $item->category?->name }}</span></td>
                            <td><code>{{ $item->code }}</code></td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>{{ $item->unit }}</td>
                            <td class="text-center">
                                <a href="{{ route('items.show', $item) }}" class="btn btn-outline-info btn-sm"><i
                                        class="bi bi-eye"></i></a>
                                <a href="{{ route('items.edit', $item) }}" class="btn btn-outline-warning btn-sm"><i
                                        class="bi bi-pencil"></i></a>
                                <form action="{{ route('items.destroy', $item) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Xóa mặt hàng {{ $item->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Chưa có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $items->links() }}</div>

@endsection
