@extends('layouts.app')

@section('title', 'Chi tiết mặt hàng')

@section('content')
    <h1>Chi tiết mặt hàng</h1>

    <p><strong>ID:</strong> {{ $item->id }}</p>
    <p><strong>Danh mục:</strong> {{ $item->category?->name }}</p>
    <p><strong>Mã:</strong> {{ $item->code }}</p>
    <p><strong>Tên:</strong> {{ $item->name }}</p>
    <p><strong>Đơn vị:</strong> {{ $item->unit }}</p>

    <p>
        <a href="{{ route('items.edit', $item) }}">Sửa</a> |
        <a href="{{ route('items.index') }}">Quay lại</a>
    </p>
@endsection
