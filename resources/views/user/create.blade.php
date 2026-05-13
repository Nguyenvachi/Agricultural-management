@extends('layouts.app')

@section('title', 'Tạo người dùng')

@section('content')
    <h1>Tạo người dùng</h1>

    <form action="{{ route('users.store') }}" method="POST">
        @csrf

        <p>
            <label>Vai trò <span style="color:red">*</span></label><br>
            <select name="role_id">
                <option value="">-- Chọn vai trò --</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>
                        {{ $role->display_name }} ({{ $role->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Đại lý (nếu có)</label><br>
            <select name="agency_id">
                <option value="">-- Không liên kết --</option>
                @foreach ($agencies as $agency)
                    <option value="{{ $agency->id }}" {{ (string) old('agency_id') === (string) $agency->id ? 'selected' : '' }}>
                        {{ $agency->name }} ({{ $agency->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Tên đăng nhập <span style="color:red">*</span></label><br>
            <input type="text" name="username" value="{{ old('username') }}" maxlength="100">
        </p>

        <p>
            <label>Mật khẩu <span style="color:red">*</span></label><br>
            <input type="password" name="password_hash" minlength="6">
        </p>

        <p>
            <label>Họ tên <span style="color:red">*</span></label><br>
            <input type="text" name="full_name" value="{{ old('full_name') }}" maxlength="255">
        </p>

        <p>
            <label>Điện thoại</label><br>
            <input type="text" name="phone" value="{{ old('phone') }}" maxlength="20">
        </p>

        <p>
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }}>
                Kích hoạt tài khoản
            </label>
        </p>

        <button type="submit">Tạo người dùng</button>
        <a href="{{ route('users.index') }}">Hủy</a>
    </form>
@endsection
