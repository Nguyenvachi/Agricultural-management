@extends('layouts.app')

@section('title', 'Tạo danh mục')

@section('content')
    <h1>Tạo danh mục</h1>

    <form action="{{ route('categories.store') }}" method="POST">
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
            <label>Mô tả</label><br>
            <textarea name="description">{{ old('description') }}</textarea>
        </p>

        <button type="submit">Lưu</button>
        <a href="{{ route('categories.index') }}">Hủy</a>
    </form>
@endsection
