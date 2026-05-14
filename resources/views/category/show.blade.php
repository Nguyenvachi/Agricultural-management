@extends('layouts.app')
@section('title', 'Chi tiết danh mục — ' . $category->name)
@section('content')

    <div class="page-header">
        <h1><i class="bi bi-tags me-2"></i>Danh mục: <span class="text-success">{{ $category->name }}</span></h1>
        <div class="d-flex gap-2">
            <a href="{{ route('categories.edit', $category) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i>
                Sửa</a>
            <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i>
                Quay lại</a>
        </div>
    </div>

    <div class="card shadow-sm" style="max-width:420px;">
        <div class="card-body p-0">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr>
                        <th width="120">ID</th>
                        <td>{{ $category->id }}</td>
                    </tr>
                    <tr>
                        <th>Mã</th>
                        <td><code>{{ $category->code }}</code></td>
                    </tr>
                    <tr>
                        <th>Tên</th>
                        <td><strong>{{ $category->name }}</strong></td>
                    </tr>
                    <tr>
                        <th>Mô tả</th>
                        <td>{{ $category->description ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
