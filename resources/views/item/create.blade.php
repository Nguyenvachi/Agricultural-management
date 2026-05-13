@extends('layouts.app')

@section('title', 'Tạo mặt hàng')

@section('content')
    <h1>Tạo mặt hàng</h1>

    <form action="{{ route('items.store') }}" method="POST">
        @csrf

        <p>
            <label>Danh mục</label><br>
            <select name="category_id">
                <option value="">-- Chọn --</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->name }} ({{ $category->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Mã</label><br>
            <input type="text" name="code" value="{{ old('code') }}">
        </p>

        <p>
            <label>Tên</label><br>
            <input type="text" name="name" value="{{ old('name') }}">
        </p>

        <p>
            <label>Đơn vị</label><br>
            <input type="text" name="unit" value="{{ old('unit', 'kg') }}">
        </p>

        <button type="submit">Lưu</button>
        <a href="{{ route('items.index') }}">Hủy</a>
    </form>
@endsection
