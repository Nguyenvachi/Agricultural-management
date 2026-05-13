@extends('layouts.app')

@section('title', 'Chi tiết đại lý')

@section('content')
    <h1>Chi tiết đại lý</h1>

    <p><strong>ID:</strong> {{ $agency->id }}</p>
    <p><strong>Mã:</strong> {{ $agency->code }}</p>
    <p><strong>Tên:</strong> {{ $agency->name }}</p>
    <p><strong>Địa chỉ:</strong> {{ $agency->address }}</p>
    <p><strong>Điện thoại:</strong> {{ $agency->phone }}</p>
    <p><strong>Kích hoạt:</strong> {{ $agency->is_active ? 'Yes' : 'No' }}</p>

    <p>
        <a href="{{ route('agencies.edit', $agency) }}">Sửa</a> |
        <a href="{{ route('agencies.index') }}">Quay lại</a>
    </p>
@endsection
