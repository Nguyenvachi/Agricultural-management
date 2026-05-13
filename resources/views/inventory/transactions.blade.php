@extends('layouts.app')
@section('title', 'Lịch sử biến động kho')
@section('content')

<div class="page-header">
    <h1><i class="bi bi-arrow-left-right me-2"></i>Lịch sử biến động kho</h1>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-bordered mb-0 align-middle" style="font-size:.9rem;">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Thời gian</th>
                    <th>Đại lý</th>
                    <th>Mặt hàng</th>
                    <th>Loại GD</th>
                    <th class="text-end">Thay đổi</th>
                    <th class="text-end">Trước</th>
                    <th class="text-end">Sau</th>
                    <th>Đơn hàng</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $tx)
                    <tr>
                        <td class="text-muted small">{{ $tx->id }}</td>
                        <td class="text-muted small" style="white-space:nowrap;">{{ $tx->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $tx->agency?->name }}</td>
                        <td>{{ $tx->item?->name }}</td>
                        <td>
                            @php $txCode = $tx->transactionType?->code; @endphp
                            @if ($txCode === 'IMPORT')
                                <span class="badge bg-success">⬆ Nhập</span>
                            @elseif ($txCode === 'EXPORT')
                                <span class="badge bg-warning text-dark">⬇ Xuất</span>
                            @else
                                <span class="badge bg-secondary">{{ $tx->transactionType?->display_name }}</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold {{ (float)$tx->quantity_change >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ (float)$tx->quantity_change >= 0 ? '+' : '' }}{{ number_format((float)$tx->quantity_change, 2, '.', ',') }}
                        </td>
                        <td class="text-end text-muted">{{ number_format((float)$tx->balance_before, 2, '.', ',') }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float)$tx->balance_after, 2, '.', ',') }}</td>
                        <td>
                            @if ($tx->order_id)
                                <a href="{{ route('orders.show', $tx->order_id) }}" class="small">
                                    <i class="bi bi-receipt"></i> #{{ $tx->order_id }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $tx->note ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-3">Chưa có biến động kho</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $transactions->links() }}</div>

@endsection
