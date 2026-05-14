@extends('layouts.app')
@section('title', 'Sửa người dùng — ' . $user->username)
@section('content')

    <div class="page-header">
        <h1><i class="bi bi-person-gear me-2"></i>Sửa: <span class="text-success">{{ $user->username }}</span></h1>
        <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="card shadow-sm" style="max-width:600px;">
        <div class="card-body">
            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf @method('PUT')

                <div class="row g-3 mb-3">
                    <div class="col">
                        <label class="form-label fw-semibold">Vai trò <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select @error('role_id') is-invalid @enderror">
                            <option value="">-- Chọn --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}"
                                    {{ (string) old('role_id', $user->role_id) === (string) $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name }} ({{ $role->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col">
                        <label class="form-label fw-semibold">Đại lý</label>
                        <select name="agency_id" class="form-select @error('agency_id') is-invalid @enderror">
                            <option value="">-- Không liên kết --</option>
                            @foreach ($agencies as $agency)
                                <option value="{{ $agency->id }}"
                                    {{ (string) old('agency_id', $user->agency_id) === (string) $agency->id ? 'selected' : '' }}>
                                    {{ $agency->name }} ({{ $agency->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('agency_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên đăng nhập <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                        value="{{ old('username', $user->username) }}">
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Họ tên <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                        value="{{ old('full_name', $user->full_name) }}">
                    @error('full_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Điện thoại</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                            {{ old('is_active', $user->is_active ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Kích hoạt tài khoản</label>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Lưu thay đổi</button>
                    <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>

@endsection
