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

            'item_id' => ['required', 'integer', 'exists:items,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function validatedOrderData(): array
    {
        $data = $this->validated();

        $orderTypeCode = (string) SysLookupValue::query()
            ->where('id', $data['order_type_id'])
            ->value('code');

        if ($orderTypeCode === LookupCode::ORDER_INTERNAL_TRANSFER) {
            if (empty($data['to_agency_id'])) {
                throw new InvalidArgumentException('Thiếu đại lý nhận (to_agency_id).');
            }
            if ((int) $data['to_agency_id'] === (int) $data['agency_id']) {
                throw new InvalidArgumentException('Đại lý chuyển và đại lý nhận phải khác nhau.');
            }
        }

        return [
            'agency_id' => (int) $data['agency_id'],
            'to_agency_id' => $data['to_agency_id'] ? (int) $data['to_agency_id'] : null,
            'user_id' => (int) $data['user_id'],
            'created_by' => (int) $data['created_by'],
            'order_type_id' => (int) $data['order_type_id'],
            'order_date' => $data['order_date'],
            'note' => $data['note'] ?? null,
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
}
