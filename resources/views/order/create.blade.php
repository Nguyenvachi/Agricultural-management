@extends('layouts.app')

@section('title', 'Tạo đơn hàng')

@section('content')
    <h1>Tạo đơn hàng</h1>

    <form action="{{ route('orders.store') }}" method="POST">
        @csrf

        <p>
            <label>Đại lý</label><br>
            <select name="agency_id">
                <option value="">-- Chọn --</option>
                @foreach ($agencies as $agency)
                    <option value="{{ $agency->id }}" {{ (string) old('agency_id') === (string) $agency->id ? 'selected' : '' }}>
                        {{ $agency->name }} ({{ $agency->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Đại lý nhận (chỉ dùng cho chuyển kho)</label><br>
            <select name="to_agency_id">
                <option value="">-- Không áp dụng --</option>
                @foreach ($agencies as $agency)
                    <option value="{{ $agency->id }}" {{ (string) old('to_agency_id') === (string) $agency->id ? 'selected' : '' }}>
                        {{ $agency->name }} ({{ $agency->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Loại đơn</label><br>
            <select name="order_type_id">
                <option value="">-- Chọn --</option>
                @foreach ($orderTypes as $type)
                    <option value="{{ $type->id }}" {{ (string) old('order_type_id') === (string) $type->id ? 'selected' : '' }}>
                        {{ $type->display_name }} ({{ $type->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Người tạo (user_id / created_by)</label><br>
            <select name="user_id">
                <option value="">-- Chọn --</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ (string) old('user_id') === (string) $user->id ? 'selected' : '' }}>
                        #{{ $user->id }} - {{ $user->username ?? $user->name ?? 'User' }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="created_by" value="{{ old('created_by') }}">
        </p>

        <p>
            <label>Ngày đơn</label><br>
            <input type="date" name="order_date" value="{{ old('order_date') }}">
        </p>

        <p>
            <label>Ghi chú</label><br>
            <textarea name="note">{{ old('note') }}</textarea>
        </p>

        <hr>

        <h3>Chi tiết đơn (1 dòng)</h3>
        <p>
            <label>Mặt hàng</label><br>
            <select name="item_id">
                <option value="">-- Chọn --</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}" {{ (string) old('item_id') === (string) $item->id ? 'selected' : '' }}>
                        {{ $item->name }} ({{ $item->code }})
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label>Số lượng</label><br>
            <input type="number" step="0.01" name="quantity" value="{{ old('quantity') }}">
        </p>

        <p>
            <label>Đơn giá</label><br>
            <input type="number" step="0.01" name="unit_price" value="{{ old('unit_price') }}">
        </p>

        <button type="submit">Tạo đơn (hoàn thành + cập nhật kho)</button>
        <a href="{{ route('orders.index') }}">Hủy</a>
    </form>

    <script>
        // Đồng bộ created_by = user_id (tạm thời bỏ qua auth)
        document.addEventListener('DOMContentLoaded', function () {
            var userSelect = document.querySelector('select[name="user_id"]');
            var createdByInput = document.querySelector('input[name="created_by"]');
            if (userSelect && createdByInput) {
                var sync = function () { createdByInput.value = userSelect.value; };
                userSelect.addEventListener('change', sync);
                sync();
            }
        });
    </script>
@endsection
