<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $itemId = (int) $this->route('item')?->id;

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('items', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($itemId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:20'],
        ];
    }
}
