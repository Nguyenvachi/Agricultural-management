<?php

namespace App\Http\Requests\PriceList;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePriceListRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'agency_id' => ['required', 'integer', 'exists:agencies,id'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'price_type_id' => ['required', 'integer', 'exists:sys_lookup_values,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'effective_from' => [
                'required',
                'date',
                Rule::unique('price_lists', 'effective_from')->where(function ($query) {
                    return $query
                        ->where('agency_id', $this->input('agency_id'))
                        ->where('item_id', $this->input('item_id'))
                        ->where('price_type_id', $this->input('price_type_id'));
                }),
            ],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'uq_price_effective' => [
                Rule::unique('price_lists', 'id')->where(function ($query) {
                    return $query
                        ->where('agency_id', $this->input('agency_id'))
                        ->where('item_id', $this->input('item_id'))
                        ->where('price_type_id', $this->input('price_type_id'))
                        ->where('effective_from', $this->input('effective_from'));
                }),
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
