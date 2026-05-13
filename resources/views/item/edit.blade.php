@extends('layouts.app')

@section('title', 'Sửa mặt hàng')

@section('content')
    <h1>Sửa mặt hàng</h1>

    <form action="{{ route('items.update', $item) }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label>Danh mục</label><br>
            <select name="category_id">
                <option value="">-- Chọn --</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) old('category_id', $item->category_id) === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->name }} ({{ $category->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Mã</label><br>
            <input type="text" name="code" value="{{ old('code', $item->code) }}">
        </p>

        <p>
            <label>Tên</label><br>
            <input type="text" name="name" value="{{ old('name', $item->name) }}">
        </p>

        <p>
            <label>Đơn vị</label><br>
            <input type="text" name="unit" value="{{ old('unit', $item->unit) }}">
        </p>

        <button type="submit">Cập nhật</button>
        <a href="{{ route('items.show', $item) }}">Hủy</a>
    </form>
@endsection
