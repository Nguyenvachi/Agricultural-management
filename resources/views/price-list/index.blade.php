@extends('layouts.app')
@section('title', 'Danh sách bảng giá')
@section('content')

<div class="page-header">
    <h1><i class="bi bi-currency-dollar me-2"></i>Bảng giá</h1>
    <a href="{{ route('price-lists.create') }}" class="btn btn-success btn-sm">
        <i class="bi bi-plus-lg"></i> Tạo mới
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Đại lý</th>
                    <th>Mặt hàng</th>
                    <th>Loại giá</th>
                    <th class="text-end">Giá (đ)</th>
                    <th>Từ ngày</th>
                    <th>Đến ngày</th>
                    <th>Kích hoạt</th>
                    <th class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($priceLists as $priceList)
                    <tr>
                        <td class="text-muted small">{{ $priceList->id }}</td>
                        <td>{{ $priceList->agency?->name }}</td>
                        <td>{{ $priceList->item?->name }}</td>
                        <td><span class="badge bg-info text-dark">{{ $priceList->priceType?->display_name }}</span></td>
                        <td class="text-end fw-semibold">{{ number_format((float)$priceList->price, 0, ',', '.') }}</td>
                        <td>{{ $priceList->effective_from?->format('d/m/Y') }}</td>
                        <td>{{ $priceList->effective_to?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if ($priceList->is_active)
                                <span class="badge bg-success">Hoạt động</span>
                            @else
                                <span class="badge bg-secondary">Vô hiệu</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('price-lists.show', $priceList) }}" class="btn btn-outline-info btn-sm"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('price-lists.edit', $priceList) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('price-lists.destroy', $priceList) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Ngừng hiệu lực bảng giá này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-slash-circle"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-3">Chưa có dữ liệu</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $priceLists->links() }}</div>

@endsection
