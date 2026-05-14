@extends('layouts.app')

@section('title', 'Tạo đơn hàng')

@section('content')
    <div class="page-header">
        <h1><i class="bi bi-plus-square me-2"></i>Tạo đơn hàng</h1>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    <form action="{{ route('orders.store') }}" method="POST" id="orderForm">
        @csrf

        @error('order')
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-octagon-fill me-2"></i>{{ $message }}
            </div>
        @enderror

        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Thông tin chung</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Loại đơn <span class="text-danger">*</span></label>
                        <select name="order_type_id" id="orderTypeSelect"
                            class="form-select @error('order_type_id') is-invalid @enderror">
                            <option value="">-- Chọn --</option>
                            @foreach ($orderTypes as $type)
                                <option value="{{ $type->id }}" data-code="{{ $type->code }}"
                                    {{ (string) old('order_type_id') === (string) $type->id ? 'selected' : '' }}>
                                    {{ $type->display_name }} ({{ $type->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('order_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        @if (auth()->user()->isAdmin())
                            <label class="form-label fw-semibold">Đại lý <span class="text-danger">*</span></label>
                            <select name="agency_id" class="form-select @error('agency_id') is-invalid @enderror">
                                <option value="">-- Chọn --</option>
                                @foreach ($agencies as $agency)
                                    <option value="{{ $agency->id }}"
                                        {{ (string) old('agency_id') === (string) $agency->id ? 'selected' : '' }}>
                                        {{ $agency->name }} ({{ $agency->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('agency_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @else
                            <input type="hidden" name="agency_id"
                                value="{{ old('agency_id', $defaultAgencyId ?? auth()->user()->agency_id) }}">
                            <label class="form-label fw-semibold">Đại lý</label>
                            <div class="form-control bg-light">
                                {{ auth()->user()->agency?->name ?? 'Chưa gắn đại lý' }}
                            </div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        @if (auth()->user()->isAdmin())
                            <label class="form-label fw-semibold">Người tạo <span class="text-danger">*</span></label>
                            <select name="user_id" id="userIdSelect"
                                class="form-select @error('user_id') is-invalid @enderror">
                                <option value="">-- Chọn --</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ (string) old('user_id') === (string) $user->id ? 'selected' : '' }}>
                                        #{{ $user->id }} - {{ $user->full_name ?? $user->username }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="user_id" id="userIdSelect" value="{{ auth()->id() }}">
                            <label class="form-label fw-semibold">Người tạo</label>
                            <div class="form-control bg-light">
                                #{{ auth()->id() }} - {{ auth()->user()->full_name ?? auth()->user()->username }}
                            </div>
                        @endif
                        <input type="hidden" name="created_by" id="createdByInput"
                            value="{{ old('created_by', auth()->id()) }}">
                        @error('user_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('created_by')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6" id="toAgencyBlock" style="display:none;">
                        <label class="form-label fw-semibold">Đại lý nhận</label>
                        <select name="to_agency_id" class="form-select @error('to_agency_id') is-invalid @enderror">
                            <option value="">-- Không áp dụng --</option>
                            @foreach ($agencies as $agency)
                                <option value="{{ $agency->id }}"
                                    {{ (string) old('to_agency_id') === (string) $agency->id ? 'selected' : '' }}>
                                    {{ $agency->name }} ({{ $agency->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('to_agency_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6" id="referenceOrderBlock" style="display:none;">
                        <label class="form-label fw-semibold">Đơn gốc</label>
                        <select name="reference_order_id"
                            class="form-select @error('reference_order_id') is-invalid @enderror">
                            <option value="">-- Chọn đơn gốc --</option>
                            @foreach ($completedOrders as $refOrder)
                                <option value="{{ $refOrder->id }}"
                                    {{ (string) old('reference_order_id') === (string) $refOrder->id ? 'selected' : '' }}>
                                    {{ $refOrder->order_code }} | {{ $refOrder->agency?->name }} |
                                    {{ $refOrder->orderType?->code }}
                                </option>
                            @endforeach
                        </select>
                        @error('reference_order_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6" id="adjustmentDirectionBlock" style="display:none;">
                        <label class="form-label fw-semibold">Hướng điều chỉnh</label>
                        <select name="adjustment_direction"
                            class="form-select @error('adjustment_direction') is-invalid @enderror">
                            <option value="">-- Chọn hướng --</option>
                            <option value="IMPORT" {{ old('adjustment_direction') === 'IMPORT' ? 'selected' : '' }}>
                                Tăng kho (IMPORT)
                            </option>
                            <option value="EXPORT" {{ old('adjustment_direction') === 'EXPORT' ? 'selected' : '' }}>
                                Giảm kho (EXPORT)
                            </option>
                        </select>
                        @error('adjustment_direction')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Ngày đơn <span class="text-danger">*</span></label>
                        <input type="date" name="order_date"
                            class="form-control @error('order_date') is-invalid @enderror"
                            value="{{ old('order_date', now()->toDateString()) }}">
                        @error('order_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-9">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <textarea name="note" rows="2" class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                        @error('note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Chi tiết đơn - dòng 1</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:55%;">Mặt hàng</th>
                                <th style="width:20%;" class="text-end">Số lượng</th>
                                <th style="width:25%;" class="text-end">Đơn giá</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="item_id" class="form-select @error('item_id') is-invalid @enderror">
                                        <option value="">-- Chọn --</option>
                                        @foreach ($items as $item)
                                            <option value="{{ $item->id }}"
                                                {{ (string) old('item_id') === (string) $item->id ? 'selected' : '' }}>
                                                {{ $item->name }} ({{ $item->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('item_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="quantity"
                                        class="form-control text-end @error('quantity') is-invalid @enderror"
                                        value="{{ old('quantity') }}" placeholder="0">
                                    @error('quantity')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="unit_price"
                                        class="form-control text-end @error('unit_price') is-invalid @enderror"
                                        value="{{ old('unit_price') }}" placeholder="0">
                                    @error('unit_price')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Chi tiết bổ sung</div>
            <div class="card-body">
                <p class="mb-2 text-muted">Hệ thống tự động tính total_amount và cập nhật kho cho từng dòng.</p>

                @php
                    $oldDetails = old('details', []);
                    if (!is_array($oldDetails)) {
                        $oldDetails = [];
                    }
                @endphp

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:50%;">Mặt hàng</th>
                                <th style="width:20%;" class="text-end">Số lượng</th>
                                <th style="width:20%;" class="text-end">Đơn giá</th>
                                <th style="width:10%;" class="text-center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="extraDetailsBody">
                            @foreach ($oldDetails as $i => $row)
                                <tr>
                                    <td>
                                        <select name="details[{{ $i }}][item_id]" class="form-select">
                                            <option value="">-- Chọn --</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}"
                                                    {{ (string) data_get($row, 'item_id') === (string) $item->id ? 'selected' : '' }}>
                                                    {{ $item->name }} ({{ $item->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01"
                                            name="details[{{ $i }}][quantity]"
                                            value="{{ data_get($row, 'quantity') }}" class="form-control text-end"
                                            placeholder="0">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01"
                                            name="details[{{ $i }}][unit_price]"
                                            value="{{ data_get($row, 'unit_price') }}" class="form-control text-end"
                                            placeholder="0">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button type="button" id="btnAddRow" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Thêm dòng
                    </button>
                </div>

                @error('details')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-lg"></i> Tạo đơn
            </button>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var userSelect = document.getElementById('userIdSelect');
            var createdByInput = document.getElementById('createdByInput');

            function syncCreatedBy() {
                if (createdByInput && userSelect) {
                    createdByInput.value = userSelect.value || '';
                }
            }

            if (userSelect) {
                userSelect.addEventListener('change', syncCreatedBy);
                syncCreatedBy();
            }

            var typeSelect = document.getElementById('orderTypeSelect');
            var toAgencyBlock = document.getElementById('toAgencyBlock');
            var referenceOrderBlock = document.getElementById('referenceOrderBlock');
            var adjustmentDirectionBlock = document.getElementById('adjustmentDirectionBlock');

            function updateFieldVisibility() {
                var selected = typeSelect ? typeSelect.options[typeSelect.selectedIndex] : null;
                var code = selected ? selected.getAttribute('data-code') : '';

                if (toAgencyBlock) {
                    toAgencyBlock.style.display = code === 'INTERNAL_TRANSFER' ? '' : 'none';
                }

                if (referenceOrderBlock) {
                    referenceOrderBlock.style.display = code === 'RETURN_ORDER' ? '' : 'none';
                }

                if (adjustmentDirectionBlock) {
                    adjustmentDirectionBlock.style.display = code === 'ADJUSTMENT_ORDER' ? '' : 'none';
                }
            }

            if (typeSelect) {
                typeSelect.addEventListener('change', updateFieldVisibility);
                updateFieldVisibility();
            }

            var body = document.getElementById('extraDetailsBody');
            var addBtn = document.getElementById('btnAddRow');
            var nextIdx = body ? body.querySelectorAll('tr').length : 0;

            function bindRemove(root) {
                root.querySelectorAll('.btn-remove-row').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var tr = btn.closest('tr');
                        if (tr) {
                            tr.remove();
                        }
                    });
                });
            }

            function makeRow(index) {
                var tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <select name="details[${index}][item_id]" class="form-select">
                            <option value="">-- Chọn --</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="details[${index}][quantity]" class="form-control text-end" placeholder="0"></td>
                    <td><input type="number" step="0.01" name="details[${index}][unit_price]" class="form-control text-end" placeholder="0"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row">
                            <i class="bi bi-x"></i>
                        </button>
                    </td>
                `;
                return tr;
            }

            if (addBtn && body) {
                addBtn.addEventListener('click', function() {
                    var tr = makeRow(nextIdx++);
                    body.appendChild(tr);
                    bindRemove(tr);
                });
            }

            if (body) {
                bindRemove(body);
            }
        });
    </script>
@endsection
