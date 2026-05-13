<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $categoryId = (int) $this->route('category')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($categoryId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
