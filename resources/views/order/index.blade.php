@extends('layouts.app')

@section('title', 'Danh sách đơn hàng')

@section('content')
    <h1>Đơn hàng</h1>
    <p><a href="{{ route('orders.create') }}">+ Tạo đơn hàng</a></p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã đơn</th>
                <th>Loại đơn</th>
                <th>Đại lý</th>
                <th>Đại lý nhận</th>
                <th>Trạng thái</th>
                <th>Ngày đơn</th>
                <th>Tổng tiền</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->order_code }}</td>
                    <td>{{ $order->orderType?->display_name }}</td>
                    <td>{{ $order->agency?->name }}</td>
                    <td>{{ $order->toAgency?->name ?? '—' }}</td>
                    <td>
                        @php $statusCode = $order->status?->code; @endphp
                        @if ($statusCode === 'COMPLETED')
                            <span style="color:green; font-weight:bold;">✅ {{ $order->status?->display_name }}</span>
                        @elseif ($statusCode === 'CANCELLED')
                            <span style="color:red; font-weight:bold;">❌ {{ $order->status?->display_name }}</span>
                        @elseif ($statusCode === 'PROCESSING')
                            <span style="color:orange; font-weight:bold;">🔄 {{ $order->status?->display_name }}</span>
                        @else
                            <span style="color:gray;">{{ $order->status?->display_name }}</span>
                        @endif
                    </td>
                    <td>{{ $order->order_date?->format('d/m/Y') }}</td>
                    <td style="text-align:right;">{{ number_format((float) $order->total_amount, 0, ',', '.') }} đ</td>
                    <td>
                        <a href="{{ route('orders.show', $order) }}">Xem</a>
                        @if ($order->status?->code !== 'CANCELLED')
                            |
                            <form action="{{ route('orders.cancel', $order) }}" method="POST" style="display:inline;"
                                  onsubmit="return confirm('Hủy đơn {{ $order->order_code }}?')">
                                @csrf
                                <button type="submit" style="color:red; background:none; border:none; cursor:pointer;">Hủy</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:12px;">
        {{ $orders->links() }}
    </div>
@endsection
