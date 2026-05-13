@extends('layouts.app')
@section('title', 'Tạo bảng giá')
@section('content')

<div class="page-header">
    <h1><i class="bi bi-currency-dollar me-2"></i>Tạo bảng giá</h1>
    <a href="{{ route('price-lists.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Quay lại</a>
</div>

<div class="card shadow-sm" style="max-width:580px;">
    <div class="card-body">
        <form action="{{ route('price-lists.store') }}" method="POST">
            @csrf

            <div class="row mb-3">
                <div class="col">
                    <label class="form-label fw-semibold">Đại lý <span class="text-danger">*</span></label>
                    <select name="agency_id" class="form-select @error('agency_id') is-invalid @enderror">
                        <option value="">-- Chọn --</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency->id }}" {{ (string)old('agency_id') === (string)$agency->id ? 'selected' : '' }}>
                                {{ $agency->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('agency_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col">
                    <label class="form-label fw-semibold">Mặt hàng <span class="text-danger">*</span></label>
                    <select name="item_id" class="form-select @error('item_id') is-invalid @enderror">
                        <option value="">-- Chọn --</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" {{ (string)old('item_id') === (string)$item->id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('item_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label class="form-label fw-semibold">Loại giá <span class="text-danger">*</span></label>
                    <select name="price_type_id" class="form-select @error('price_type_id') is-invalid @enderror">
                        <option value="">-- Chọn --</option>
                        @foreach ($priceTypes as $type)
                            <option value="{{ $type->id }}" {{ (string)old('price_type_id') === (string)$type->id ? 'selected' : '' }}>
                                {{ $type->display_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('price_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col">
                    <label class="form-label fw-semibold">Giá (đ) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="price"
                           class="form-control @error('price') is-invalid @enderror"
                           value="{{ old('price') }}" placeholder="0">
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label class="form-label fw-semibold">Hiệu lực từ <span class="text-danger">*</span></label>
                    <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from') }}">
                </div>
                <div class="col">
                    <label class="form-label fw-semibold">Hiệu lực đến</label>
                    <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to') }}">
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                           id="isActive" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Kích hoạt ngay</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Lưu</button>
                <a href="{{ route('price-lists.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
@endsection
