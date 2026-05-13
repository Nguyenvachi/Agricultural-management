@extends('layouts.app')
@section('title', 'Tạo người dùng')
@section('content')

<div class="page-header">
    <h1><i class="bi bi-person-plus me-2"></i>Tạo người dùng</h1>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Quay lại
    </a>
</div>

<div class="card shadow-sm" style="max-width:600px;">
    <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf

            <div class="row mb-3">
                <div class="col">
                    <label class="form-label fw-semibold">Vai trò <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select @error('role_id') is-invalid @enderror">
                        <option value="">-- Chọn vai trò --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ (string)old('role_id') === (string)$role->id ? 'selected' : '' }}>
                                {{ $role->display_name }} ({{ $role->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col">
                    <label class="form-label fw-semibold">Đại lý</label>
                    <select name="agency_id" class="form-select">
                        <option value="">-- Không liên kết --</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency->id }}" {{ (string)old('agency_id') === (string)$agency->id ? 'selected' : '' }}>
                                {{ $agency->name }} ({{ $agency->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Tên đăng nhập <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                       value="{{ old('username') }}" placeholder="username">
                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
                <input type="password" name="password_hash" class="form-control @error('password_hash') is-invalid @enderror"
                       placeholder="Tối thiểu 6 ký tự">
                @error('password_hash')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Họ tên <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                       value="{{ old('full_name') }}">
                @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Điện thoại</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
            </div>

            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                           id="isActive" {{ old('is_active', '1') === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Kích hoạt tài khoản ngay</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Tạo người dùng</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>

@endsection
