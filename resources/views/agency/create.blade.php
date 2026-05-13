@extends('layouts.app')

@section('title', 'Tạo đại lý')

@section('content')
    <h1>Tạo đại lý</h1>

    <form action="{{ route('agencies.store') }}" method="POST">
        @csrf

        <p>
            <label>Mã</label><br>
            <input type="text" name="code" value="{{ old('code') }}">
        </p>

        <p>
            <label>Tên</label><br>
            <input type="text" name="name" value="{{ old('name') }}">
        </p>

        <p>
            <label>Địa chỉ</label><br>
            <textarea name="address">{{ old('address') }}</textarea>
        </p>

        <p>
            <label>Điện thoại</label><br>
            <input type="text" name="phone" value="{{ old('phone') }}">
        </p>

        <p>
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                Kích hoạt
            </label>
        </p>

        <button type="submit">Lưu</button>
        <a href="{{ route('agencies.index') }}">Hủy</a>
    </form>
@endsection
