<?php

namespace App\Http\Requests\Order;

use App\Constants\LookupCode;
use App\Models\SysLookupValue;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class StoreOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'agency_id' => ['required', 'integer', 'exists:agencies,id'],
            'to_agency_id' => ['nullable', 'integer', 'exists:agencies,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'created_by' => ['required', 'integer', 'exists:users,id'],
            'order_type_id' => ['required', 'integer', 'exists:sys_lookup_values,id'],
            'order_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'adjustment_direction' => ['nullable', 'string', 'in:IMPORT,EXPORT'],

            'reference_order_id' => ['nullable', 'integer', 'exists:orders,id'],

            'item_id' => ['required', 'integer', 'exists:items,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],

            'details' => ['nullable', 'array', 'min:1'],
            'details.*.item_id' => ['required_with:details', 'integer', 'exists:items,id'],
            'details.*.quantity' => ['required_with:details', 'numeric', 'gt:0'],
            'details.*.unit_price' => ['required_with:details', 'numeric', 'min:0'],
        ];
    }

    public function validatedOrderData(): array
    {
        $data = $this->validated();
        $authUser = $this->user();

        if (! $authUser) {
            throw new InvalidArgumentException('Phiên đăng nhập không hợp lệ.');
        }

        $authUser->loadMissing('role', 'agency');

        $orderTypeCode = (string) SysLookupValue::query()
            ->where('id', $data['order_type_id'])
            ->value('code');

        if ($authUser->isAdmin()) {
            // Admin được tạo tất cả loại đơn.
        } elseif ($authUser->isAgency()) {
            if ((int) $data['agency_id'] !== (int) $authUser->agency_id) {
                throw new InvalidArgumentException('Bạn chỉ được tạo đơn cho đại lý của mình.');
            }

            if ($orderTypeCode === LookupCode::ORDER_ADJUSTMENT) {
                throw new InvalidArgumentException('Tài khoản đại lý không được tạo ADJUSTMENT_ORDER.');
            }
        } elseif ($authUser->isFarmer()) {
            if ($orderTypeCode !== LookupCode::ORDER_SALES) {
                throw new InvalidArgumentException('Tài khoản nông hộ chỉ được tạo SALES_ORDER.');
            }

            if (! $authUser->agency_id) {
                throw new InvalidArgumentException('Tài khoản nông hộ chưa được gắn đại lý nên chưa thể tạo đơn.');
            }
        } else {
            throw new InvalidArgumentException('Bạn không có quyền tạo đơn hàng.');
        }

        if ($orderTypeCode === LookupCode::ORDER_INTERNAL_TRANSFER) {
            if (empty($data['to_agency_id'])) {
                throw new InvalidArgumentException('Thiếu đại lý nhận (to_agency_id).');
            }

            if ((int) $data['to_agency_id'] === (int) $data['agency_id']) {
                throw new InvalidArgumentException('Đại lý chuyển và đại lý nhận phải khác nhau.');
            }
        }

        if ($orderTypeCode === LookupCode::ORDER_RETURN && empty($data['reference_order_id'])) {
            throw new InvalidArgumentException('RETURN_ORDER cần chọn đơn gốc (reference_order_id).');
        }

        if ($orderTypeCode === LookupCode::ORDER_ADJUSTMENT && empty($data['adjustment_direction'])) {
            throw new InvalidArgumentException('ADJUSTMENT_ORDER cần chọn hướng điều chỉnh kho.');
        }

        $note = $data['note'] ?? null;
        if ($orderTypeCode === LookupCode::ORDER_ADJUSTMENT && ! empty($data['adjustment_direction'])) {
            $prefix = '[ADJUSTMENT:' . $data['adjustment_direction'] . ']';
            $note = $note ? $prefix . ' ' . $note : $prefix;
        }

        return [
            'agency_id' => (int) $data['agency_id'],
            'to_agency_id' => ! empty($data['to_agency_id']) ? (int) $data['to_agency_id'] : null,
            'reference_order_id' => ! empty($data['reference_order_id']) ? (int) $data['reference_order_id'] : null,
            'user_id' => (int) $data['user_id'],
            'created_by' => (int) $authUser->id,
            'order_type_id' => (int) $data['order_type_id'],
            'order_date' => $data['order_date'],
            'note' => $note,
            'adjustment_direction' => $data['adjustment_direction'] ?? null,
        ];
    }

    public function validatedDetailData(): array
    {
        $data = $this->validated();

        return [
            'item_id' => (int) $data['item_id'],
            'quantity' => (float) $data['quantity'],
            'unit_price' => (float) $data['unit_price'],
        ];
    }

    /** @return array<int, array{item_id:int, quantity:float, unit_price:float}> */
    public function validatedExtraDetailsData(): array
    {
        $data = $this->validated();

        $details = $data['details'] ?? [];
        if (! is_array($details)) {
            return [];
        }

        $result = [];
        foreach ($details as $row) {
            if (! is_array($row) || empty($row['item_id'])) {
                continue;
            }

            $result[] = [
                'item_id' => (int) ($row['item_id'] ?? 0),
                'quantity' => (float) ($row['quantity'] ?? 0),
                'unit_price' => (float) ($row['unit_price'] ?? 0),
            ];
        }

        return $result;
    }

    protected function prepareForValidation()
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        $user->loadMissing('role');

        $payload = [
            'created_by' => $user->id,
        ];

        if (! $user->isAdmin()) {
            $payload['user_id'] = $user->id;
            $payload['agency_id'] = $user->agency_id;
        }

        if (! $this->has('adjustment_direction')) {
            $payload['adjustment_direction'] = null;
        }

        $this->merge($payload);
    }
}
