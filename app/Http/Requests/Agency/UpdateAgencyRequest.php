<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgencyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $agencyId = (int) $this->route('agency')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('agencies', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($agencyId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
