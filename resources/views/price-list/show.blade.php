@extends('layouts.app')

@section('title', 'Chi tiết bảng giá')

@section('content')
    <h1>Chi tiết bảng giá</h1>

    <p><strong>ID:</strong> {{ $priceList->id }}</p>
    <p><strong>Đại lý:</strong> {{ $priceList->agency?->name }}</p>
    <p><strong>Mặt hàng:</strong> {{ $priceList->item?->name }}</p>
    <p><strong>Loại giá:</strong> {{ $priceList->priceType?->display_name }}</p>
    <p><strong>Giá:</strong> {{ $priceList->price }}</p>
    <p><strong>Hiệu lực từ:</strong> {{ $priceList->effective_from?->format('Y-m-d') }}</p>
    <p><strong>Hiệu lực đến:</strong> {{ $priceList->effective_to?->format('Y-m-d') }}</p>
    <p><strong>Kích hoạt:</strong> {{ $priceList->is_active ? 'Yes' : 'No' }}</p>

    <p>
        <a href="{{ route('price-lists.edit', $priceList) }}">Sửa</a> |
        <a href="{{ route('price-lists.index') }}">Quay lại</a>
    </p>
@endsection
