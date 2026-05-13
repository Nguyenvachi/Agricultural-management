@extends('layouts.app')

@section('title', 'Chi tiết danh mục')

@section('content')
    <h1>Chi tiết danh mục</h1>

    <p><strong>ID:</strong> {{ $category->id }}</p>
    <p><strong>Mã:</strong> {{ $category->code }}</p>
    <p><strong>Tên:</strong> {{ $category->name }}</p>
    <p><strong>Mô tả:</strong> {{ $category->description }}</p>

    <p>
        <a href="{{ route('categories.edit', $category) }}">Sửa</a> |
        <a href="{{ route('categories.index') }}">Quay lại</a>
    </p>
@endsection
