@extends('layouts.app')
@section('title', 'Sửa đại lý — ' . $agency->name)
@section('content')

<div class="page-header">
    <h1><i class="bi bi-pencil-square me-2"></i>Sửa đại lý: <span class="text-success">{{ $agency->name }}</span></h1>
    <a href="{{ route('agencies.show', $agency) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Quay lại
    </a>
</div>

<div class="card shadow-sm" style="max-width:560px;">
    <div class="card-body">
        <form action="{{ route('agencies.update', $agency) }}" method="POST">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold">Mã đại lý <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code', $agency->code) }}">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Tên đại lý <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $agency->name) }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Địa chỉ</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $agency->address) }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Điện thoại</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $agency->phone) }}">
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                           id="isActive" {{ old('is_active', $agency->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Kích hoạt</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Cập nhật</button>
                <a href="{{ route('agencies.show', $agency) }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

@endsection
