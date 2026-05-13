<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'role_id'       => ['required', 'integer', 'exists:sys_lookup_values,id'],
            'agency_id'     => ['nullable', 'integer', 'exists:agencies,id'],
            'username'      => ['required', 'string', 'max:100', 'unique:users,username'],
            'password_hash' => ['required', 'string', 'min:6'],
            'full_name'     => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'role_id.required'       => 'Vui lòng chọn vai trò.',
            'username.required'      => 'Tên đăng nhập không được để trống.',
            'username.unique'        => 'Tên đăng nhập đã tồn tại.',
            'password_hash.required' => 'Mật khẩu không được để trống.',
            'password_hash.min'      => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'full_name.required'     => 'Họ tên không được để trống.',
        ];
    }
}
