@extends('layouts.app')
@section('title', 'Tồn kho hiện tại')
@section('content')

    <div class="page-header">
        <h1><i class="bi bi-clipboard-data me-2"></i>Tồn kho hiện tại</h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Đại lý</th>
                        <th>Mặt hàng</th>
                        <th>Đơn vị</th>
                        <th class="text-end">Số lượng</th>
                        <th>Cập nhật lần cuối</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventories as $inv)
                        <tr>
                            <td class="text-muted small">{{ $inv->id }}</td>
                            <td><strong>{{ $inv->agency?->name }}</strong></td>
                            <td>{{ $inv->item?->name }} <code class="small">{{ $inv->item?->code }}</code></td>
                            <td>{{ $inv->item?->unit }}</td>
                            <td
                                class="text-end fw-semibold
                            {{ (float) $inv->quantity <= 0 ? 'text-danger' : ((float) $inv->quantity < 10 ? 'text-warning' : 'text-success') }}">
                                {{ number_format((float) $inv->quantity, 2, '.', ',') }}
                            </td>
                            <td class="text-muted small">{{ $inv->updated_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Chưa có dữ liệu tồn kho</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">{{ $inventories->links() }}</div>

@endsection
