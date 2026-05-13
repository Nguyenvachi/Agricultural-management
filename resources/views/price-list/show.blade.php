@extends('layouts.app')
@section('title', 'Chi tiết bảng giá #' . $priceList->id)
@section('content')

<div class="page-header">
    <h1><i class="bi bi-currency-dollar me-2"></i>Chi tiết bảng giá</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('price-lists.edit', $priceList) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Sửa</a>
        <a href="{{ route('price-lists.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Quay lại</a>
    </div>
</div>

<div class="card shadow-sm" style="max-width:480px;">
    <div class="card-body p-0">
        <table class="table table-borderless mb-0">
            <tbody>
                <tr><th width="140">ID</th><td>{{ $priceList->id }}</td></tr>
                <tr><th>Đại lý</th><td>{{ $priceList->agency?->name }}</td></tr>
                <tr><th>Mặt hàng</th><td>{{ $priceList->item?->name }} <code>({{ $priceList->item?->code }})</code></td></tr>
                <tr><th>Loại giá</th><td><span class="badge bg-info text-dark">{{ $priceList->priceType?->display_name }}</span></td></tr>
                <tr><th>Giá</th><td class="fw-semibold">{{ number_format((float)$priceList->price, 0, ',', '.') }} đ</td></tr>
                <tr><th>Hiệu lực từ</th><td>{{ $priceList->effective_from?->format('d/m/Y') }}</td></tr>
                <tr><th>Hiệu lực đến</th><td>{{ $priceList->effective_to?->format('d/m/Y') ?? '—' }}</td></tr>
                <tr>
                    <th>Trạng thái</th>
                    <td>
                        @if ($priceList->is_active)
                            <span class="badge bg-success">✅ Hoạt động</span>
                        @else
                            <span class="badge bg-secondary">❌ Ngừng hiệu lực</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
