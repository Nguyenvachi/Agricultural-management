@extends('layouts.app')

@section('title', 'Sửa đại lý')

@section('content')
    <h1>Sửa đại lý</h1>

    <form action="{{ route('agencies.update', $agency) }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label>Mã</label><br>
            <input type="text" name="code" value="{{ old('code', $agency->code) }}">
        </p>

        <p>
            <label>Tên</label><br>
            <input type="text" name="name" value="{{ old('name', $agency->name) }}">
        </p>

        <p>
            <label>Địa chỉ</label><br>
            <textarea name="address">{{ old('address', $agency->address) }}</textarea>
        </p>

        <p>
            <label>Điện thoại</label><br>
            <input type="text" name="phone" value="{{ old('phone', $agency->phone) }}">
        </p>

        <p>
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $agency->is_active) ? 'checked' : '' }}>
                Kích hoạt
            </label>
        </p>

        <button type="submit">Cập nhật</button>
        <a href="{{ route('agencies.show', $agency) }}">Hủy</a>
    </form>
@endsection
