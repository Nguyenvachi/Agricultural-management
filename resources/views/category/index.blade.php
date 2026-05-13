@extends('layouts.app')
@section('title', 'Danh sách danh mục')
@section('content')

<div class="page-header">
    <h1><i class="bi bi-tags me-2"></i>Danh mục</h1>
    <a href="{{ route('categories.create') }}" class="btn btn-success btn-sm">
        <i class="bi bi-plus-lg"></i> Tạo mới
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered mb-0 align-middle">
            <thead class="table-dark">
                <tr><th>ID</th><th>Mã</th><th>Tên</th><th class="text-center">Hành động</th></tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td class="text-muted small">{{ $category->id }}</td>
                        <td><code>{{ $category->code }}</code></td>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td class="text-center">
                            <a href="{{ route('categories.show', $category) }}" class="btn btn-outline-info btn-sm"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('categories.edit', $category) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Xóa danh mục {{ $category->name }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $categories->links() }}</div>

@endsection
