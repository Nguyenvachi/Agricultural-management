@extends('layouts.app')

@section('title', 'Tạo đơn hàng')

@section('content')
    <h1>Tạo đơn hàng</h1>

    <form action="{{ route('orders.store') }}" method="POST" id="orderForm">
        @csrf

        {{-- ─── THÔNG TIN CHUNG ─────────────────────────────────── --}}
        <fieldset>
            <legend><strong>Thông tin chung</strong></legend>

            <p>
                <label>Loại đơn <span style="color:red">*</span></label><br>
                <select name="order_type_id" id="orderTypeSelect">
                    <option value="">-- Chọn --</option>
                    @foreach ($orderTypes as $type)
                        <option value="{{ $type->id }}"
                                data-code="{{ $type->code }}"
                                {{ (string) old('order_type_id') === (string) $type->id ? 'selected' : '' }}>
                            {{ $type->display_name }} ({{ $type->code }})
                        </option>
                    @endforeach
                </select>
            </p>

            {{-- Đại lý: ẩn nếu AGENCY (tự điền) --}}
            @if (auth()->user()->isAdmin())
                <p>
                    <label>Đại lý <span style="color:red">*</span></label><br>
                    <select name="agency_id">
                        <option value="">-- Chọn --</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency->id }}" {{ (string) old('agency_id') === (string) $agency->id ? 'selected' : '' }}>
                                {{ $agency->name }} ({{ $agency->code }})
                            </option>
                        @endforeach
                    </select>
                </p>
            @else
                {{-- AGENCY/FARMER: tự điền agency_id của mình --}}
                <input type="hidden" name="agency_id" value="{{ $defaultAgencyId ?? auth()->user()->agency_id }}">
                <p><strong>Đại lý:</strong> {{ auth()->user()->agency?->name }}</p>
            @endif

            {{-- Chỉ hiện khi INTERNAL_TRANSFER --}}
            <p id="toAgencyBlock" style="display:none;">
                <label>Đại lý nhận (INTERNAL_TRANSFER)</label><br>
                <select name="to_agency_id">
                    <option value="">-- Không áp dụng --</option>
                    @foreach ($agencies as $agency)
                        <option value="{{ $agency->id }}" {{ (string) old('to_agency_id') === (string) $agency->id ? 'selected' : '' }}>
                            {{ $agency->name }} ({{ $agency->code }})
                        </option>
                    @endforeach
                </select>
            </p>

            {{-- Chỉ hiện khi RETURN_ORDER --}}
            <p id="referenceOrderBlock" style="display:none;">
                <label>Đơn gốc (RETURN_ORDER — bắt buộc)</label><br>
                <select name="reference_order_id">
                    <option value="">-- Chọn đơn gốc --</option>
                    @foreach ($completedOrders as $refOrder)
                        <option value="{{ $refOrder->id }}" {{ (string) old('reference_order_id') === (string) $refOrder->id ? 'selected' : '' }}>
                            {{ $refOrder->order_code }} | {{ $refOrder->agency?->name }} | {{ $refOrder->orderType?->code }}
                        </option>
                    @endforeach
                </select>
            </p>

            <p>
                <label>Người tạo <span style="color:red">*</span></label><br>
                <select name="user_id" id="userIdSelect">
                    <option value="">-- Chọn --</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ (string) old('user_id') === (string) $user->id ? 'selected' : '' }}>
                            #{{ $user->id }} — {{ $user->full_name ?? $user->username }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="created_by" id="createdByInput" value="{{ old('created_by') }}">
            </p>

            <p>
                <label>Ngày đơn <span style="color:red">*</span></label><br>
                <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}">
            </p>

            <p>
                <label>Ghi chú</label><br>
                <textarea name="note" rows="2" style="width:100%;max-width:500px;">{{ old('note') }}</textarea>
            </p>
        </fieldset>

        <br>

        {{-- ─── CHI TIẾT ĐƠN ────────────────────────────────────── --}}
        <fieldset>
            <legend><strong>Chi tiết đơn — Dòng 1 (bắt buộc)</strong></legend>

            <table border="1" cellpadding="6" cellspacing="0" style="min-width:600px;">
                <thead>
                    <tr>
                        <th>Mặt hàng</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="item_id">
                                <option value="">-- Chọn --</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}" {{ (string) old('item_id') === (string) $item->id ? 'selected' : '' }}>
                                        {{ $item->name }} ({{ $item->code }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.01" name="quantity" value="{{ old('quantity') }}" style="width:100px;"></td>
                        <td><input type="number" step="0.01" name="unit_price" value="{{ old('unit_price') }}" style="width:120px;"></td>
                    </tr>
                </tbody>
            </table>
        </fieldset>

        <br>

        {{-- ─── CHI TIẾT BỔ SUNG ────────────────────────────────── --}}
        <fieldset>
            <legend><strong>Chi tiết bổ sung (tuỳ chọn — nhiều dòng)</strong></legend>
            <p style="margin-top:0; color:#555;">Hệ thống tự cộng <code>total_amount</code> và cập nhật kho cho từng dòng.</p>

            @php
                $oldDetails = old('details', []);
                if (!is_array($oldDetails)) { $oldDetails = []; }
            @endphp

            <table border="1" cellpadding="6" cellspacing="0" style="min-width:700px;">
                <thead>
                    <tr>
                        <th>Mặt hàng</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Xóa</th>
                    </tr>
                </thead>
                <tbody id="extraDetailsBody">
                    @foreach ($oldDetails as $i => $row)
                        <tr>
                            <td>
                                <select name="details[{{ $i }}][item_id]">
                                    <option value="">-- Chọn --</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}" {{ (string) data_get($row, 'item_id') === (string) $item->id ? 'selected' : '' }}>
                                            {{ $item->name }} ({{ $item->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.01" name="details[{{ $i }}][quantity]" value="{{ data_get($row, 'quantity') }}" style="width:100px;"></td>
                            <td><input type="number" step="0.01" name="details[{{ $i }}][unit_price]" value="{{ data_get($row, 'unit_price') }}" style="width:120px;"></td>
                            <td><button type="button" class="btn-remove-row" style="color:red;">✕</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p><button type="button" id="btnAddRow">+ Thêm dòng</button></p>
        </fieldset>

        <br>
        <button type="submit" style="padding:8px 20px; font-size:1em;">✅ Tạo đơn (cập nhật kho)</button>
        <a href="{{ route('orders.index') }}" style="margin-left:12px;">Hủy</a>
    </form>

    {{-- ─── JavaScript ─────────────────────────────────────────── --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ── Đồng bộ created_by = user_id ────────────────────
            var userSelect    = document.getElementById('userIdSelect');
            var createdByInput = document.getElementById('createdByInput');
            function syncCreatedBy() {
                if (createdByInput) createdByInput.value = userSelect ? userSelect.value : '';
            }
            if (userSelect) { userSelect.addEventListener('change', syncCreatedBy); syncCreatedBy(); }

            // ── Hiện/ẩn to_agency_id và reference_order_id theo order_type ──
            var typeSelect          = document.getElementById('orderTypeSelect');
            var toAgencyBlock       = document.getElementById('toAgencyBlock');
            var referenceOrderBlock = document.getElementById('referenceOrderBlock');

            function updateFieldVisibility() {
                var selected = typeSelect ? typeSelect.options[typeSelect.selectedIndex] : null;
                var code = selected ? selected.getAttribute('data-code') : '';
                if (toAgencyBlock)       toAgencyBlock.style.display       = (code === 'INTERNAL_TRANSFER') ? '' : 'none';
                if (referenceOrderBlock) referenceOrderBlock.style.display = (code === 'RETURN_ORDER')      ? '' : 'none';
            }
            if (typeSelect) { typeSelect.addEventListener('change', updateFieldVisibility); updateFieldVisibility(); }

            // ── Thêm / xóa dòng chi tiết bổ sung ───────────────
            var body    = document.getElementById('extraDetailsBody');
            var addBtn  = document.getElementById('btnAddRow');
            var nextIdx = body ? body.querySelectorAll('tr').length : 0;

            function bindRemove(root) {
                root.querySelectorAll('.btn-remove-row').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var tr = btn.closest('tr');
                        if (tr) tr.remove();
                    });
                });
            }

            function makeRow(index) {
                var tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <select name="details[${index}][item_id]">
                            <option value="">-- Chọn --</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="details[${index}][quantity]" style="width:100px;"></td>
                    <td><input type="number" step="0.01" name="details[${index}][unit_price]" style="width:120px;"></td>
                    <td><button type="button" class="btn-remove-row" style="color:red;">✕</button></td>
                `;
                return tr;
            }

            if (addBtn && body) {
                addBtn.addEventListener('click', function () {
                    var tr = makeRow(nextIdx++);
                    body.appendChild(tr);
                    bindRemove(tr);
                });
            }
            if (body) bindRemove(body);
        });
    </script>
@endsection
