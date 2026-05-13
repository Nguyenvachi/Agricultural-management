<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // Lấy user ID từ route để bỏ qua unique cho chính user đang sửa
        $userId = $this->route('user')?->id;

        return [
            'role_id'   => ['required', 'integer', 'exists:sys_lookup_values,id'],
            'agency_id' => ['nullable', 'integer', 'exists:agencies,id'],
            'username'  => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'role_id.required'  => 'Vui lòng chọn vai trò.',
            'username.required' => 'Tên đăng nhập không được để trống.',
            'username.unique'   => 'Tên đăng nhập đã tồn tại.',
            'full_name.required'=> 'Họ tên không được để trống.',
        ];
    }
}
