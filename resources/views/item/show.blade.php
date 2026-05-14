@extends('layouts.app')
@section('title', 'Chi tiết mặt hàng — ' . $item->name)
@section('content')

    <div class="page-header">
        <h1><i class="bi bi-box-seam me-2"></i>Mặt hàng: <span class="text-success">{{ $item->name }}</span></h1>
        <div class="d-flex gap-2">
            <a href="{{ route('items.edit', $item) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Sửa</a>
            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Quay
                lại</a>
        </div>
    </div>

    <div class="card shadow-sm" style="max-width:440px;">
        <div class="card-body p-0">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr>
                        <th width="120">ID</th>
                        <td>{{ $item->id }}</td>
                    </tr>
                    <tr>
                        <th>Danh mục</th>
                        <td><span class="badge bg-secondary">{{ $item->category?->name }}</span></td>
                    </tr>
                    <tr>
                        <th>Mã</th>
                        <td><code>{{ $item->code }}</code></td>
                    </tr>
                    <tr>
                        <th>Tên</th>
                        <td><strong>{{ $item->name }}</strong></td>
                    </tr>
                    <tr>
                        <th>Đơn vị</th>
                        <td>{{ $item->unit }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
