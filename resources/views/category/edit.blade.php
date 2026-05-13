@extends('layouts.app')

@section('title', 'Sửa danh mục')

@section('content')
    <h1>Sửa danh mục</h1>

    <form action="{{ route('categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label>Mã</label><br>
            <input type="text" name="code" value="{{ old('code', $category->code) }}">
        </p>

        <p>
            <label>Tên</label><br>
            <input type="text" name="name" value="{{ old('name', $category->name) }}">
        </p>

        <p>
            <label>Mô tả</label><br>
            <textarea name="description">{{ old('description', $category->description) }}</textarea>
        </p>

        <button type="submit">Cập nhật</button>
        <a href="{{ route('categories.show', $category) }}">Hủy</a>
    </form>
@endsection
