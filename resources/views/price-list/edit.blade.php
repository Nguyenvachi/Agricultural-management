@extends('layouts.app')

@section('title', 'Sửa bảng giá')

@section('content')
    <h1>Sửa bảng giá</h1>

    <form action="{{ route('price-lists.update', $priceList) }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label>Đại lý</label><br>
            <select name="agency_id">
                <option value="">-- Chọn --</option>
                @foreach ($agencies as $agency)
                    <option value="{{ $agency->id }}" {{ (string) old('agency_id', $priceList->agency_id) === (string) $agency->id ? 'selected' : '' }}>
                        {{ $agency->name }} ({{ $agency->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Mặt hàng</label><br>
            <select name="item_id">
                <option value="">-- Chọn --</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}" {{ (string) old('item_id', $priceList->item_id) === (string) $item->id ? 'selected' : '' }}>
                        {{ $item->name }} ({{ $item->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Loại giá</label><br>
            <select name="price_type_id">
                <option value="">-- Chọn --</option>
                @foreach ($priceTypes as $type)
                    <option value="{{ $type->id }}" {{ (string) old('price_type_id', $priceList->price_type_id) === (string) $type->id ? 'selected' : '' }}>
                        {{ $type->display_name }} ({{ $type->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Giá</label><br>
            <input type="number" step="0.01" name="price" value="{{ old('price', $priceList->price) }}">
        </p>

        <p>
            <label>Hiệu lực từ</label><br>
            <input type="date" name="effective_from" value="{{ old('effective_from', $priceList->effective_from?->format('Y-m-d')) }}">
        </p>

        <p>
            <label>Hiệu lực đến</label><br>
            <input type="date" name="effective_to" value="{{ old('effective_to', $priceList->effective_to?->format('Y-m-d')) }}">
        </p>

        <p>
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $priceList->is_active) ? 'checked' : '' }}>
                Kích hoạt
            </label>
        </p>

        <button type="submit">Cập nhật</button>
        <a href="{{ route('price-lists.show', $priceList) }}">Hủy</a>
    </form>
@endsection
