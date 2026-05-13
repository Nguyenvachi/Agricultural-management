@extends('layouts.app')
@section('title', 'Sửa mặt hàng — ' . $item->name)
@section('content')

<div class="page-header">
    <h1><i class="bi bi-pencil-square me-2"></i>Sửa mặt hàng: <span class="text-success">{{ $item->name }}</span></h1>
    <a href="{{ route('items.show', $item) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Quay lại</a>
</div>

<div class="card shadow-sm" style="max-width:520px;">
    <div class="card-body">
        <form action="{{ route('items.update', $item) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                    <option value="">-- Chọn --</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ (string)old('category_id', $item->category_id) === (string)$category->id ? 'selected' : '' }}>
                            {{ $category->name }} ({{ $category->code }})
                        </option>
                    @endforeach
                </select>
                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label class="form-label fw-semibold">Mã <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $item->code) }}">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col">
                    <label class="form-label fw-semibold">Đơn vị</label>
                    <input type="text" name="unit" class="form-control" value="{{ old('unit', $item->unit) }}">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Tên mặt hàng <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $item->name) }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Cập nhật</button>
                <a href="{{ route('items.show', $item) }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
@endsection
