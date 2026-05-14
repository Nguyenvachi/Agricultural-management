<?php

namespace App\Http\Requests\PriceList;

use App\Models\PriceList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePriceListRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $priceListId = (int) $this->route('price_list')?->id;

        return [
            'agency_id' => ['required', 'integer', 'exists:agencies,id'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'price_type_id' => ['required', 'integer', 'exists:sys_lookup_values,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'uq_price_effective' => [
                Rule::unique('price_lists', 'id')
                    ->ignore($priceListId)
                    ->where(function ($query) {
                        return $query
                            ->where('agency_id', $this->input('agency_id'))
                            ->where('item_id', $this->input('item_id'))
                            ->where('price_type_id', $this->input('price_type_id'))
                            ->where('effective_from', $this->input('effective_from'));
                    }),
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->boolean('is_active')) {
                return;
            }

            if ($this->hasOverlappingActiveRange()) {
                $validator->errors()->add(
                    'effective_from',
                    'Khoang hieu luc bi chong lan voi mot bang gia dang hoat dong cung dai ly, mat hang va loai gia.'
                );
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    private function hasOverlappingActiveRange(): bool
    {
        $priceListId = (int) $this->route('price_list')?->id;
        $newFrom = (string) $this->input('effective_from');
        $newTo = $this->input('effective_to');

        return PriceList::query()
            ->whereKeyNot($priceListId)
            ->where('agency_id', $this->input('agency_id'))
            ->where('item_id', $this->input('item_id'))
            ->where('price_type_id', $this->input('price_type_id'))
            ->where('is_active', true)
            ->where(function ($query) use ($newFrom, $newTo) {
                $query->where(function ($inner) use ($newFrom) {
                    $inner->where('effective_from', '<=', $newFrom)
                        ->where(function ($end) use ($newFrom) {
                            $end->whereNull('effective_to')
                                ->orWhere('effective_to', '>=', $newFrom);
                        });
                });

                if ($newTo) {
                    $query->orWhere(function ($inner) use ($newTo) {
                        $inner->where('effective_from', '<=', $newTo)
                            ->where(function ($end) use ($newTo) {
                                $end->whereNull('effective_to')
                                    ->orWhere('effective_to', '>=', $newTo);
                            });
                    })->orWhere(function ($inner) use ($newFrom, $newTo) {
                        $inner->where('effective_from', '>=', $newFrom)
                            ->where('effective_from', '<=', $newTo);
                    });
                }
            })
            ->exists();
    }
}
