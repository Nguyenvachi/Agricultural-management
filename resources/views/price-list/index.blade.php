@extends('layouts.app')

@section('title', 'Danh sách bảng giá')

@section('content')
    <h1>Bảng giá</h1>
    <p><a href="{{ route('price-lists.create') }}">+ Tạo mới</a></p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Đại lý</th>
                <th>Mặt hàng</th>
                <th>Loại giá</th>
                <th>Giá</th>
                <th>Từ ngày</th>
                <th>Đến ngày</th>
                <th>Kích hoạt</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($priceLists as $priceList)
                <tr>
                    <td>{{ $priceList->id }}</td>
                    <td>{{ $priceList->agency?->name }}</td>
                    <td>{{ $priceList->item?->name }}</td>
                    <td>{{ $priceList->priceType?->display_name }}</td>
                    <td>{{ $priceList->price }}</td>
                    <td>{{ $priceList->effective_from?->format('Y-m-d') }}</td>
                    <td>{{ $priceList->effective_to?->format('Y-m-d') }}</td>
                    <td>{{ $priceList->is_active ? 'Yes' : 'No' }}</td>
                    <td>
                        <a href="{{ route('price-lists.show', $priceList) }}">Xem</a> |
                        <a href="{{ route('price-lists.edit', $priceList) }}">Sửa</a> |
                        <form action="{{ route('price-lists.destroy', $priceList) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Xóa bảng giá này?')">Xóa</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        {{ $priceLists->links() }}
    </div>
@endsection
