<?php

namespace App\Http\Controllers;

use App\Constants\LookupCode;
use App\Helpers\LookupHelper;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with(['agency', 'role'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('user.index', compact('users'));
    }

    public function create()
    {
        $roles    = LookupHelper::getValuesByTypeCode(LookupCode::TYPE_USER_ROLE);
        $agencies = Agency::query()->orderBy('name')->get();

        return view('user.create', compact('roles', 'agencies'));
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        // Hash mật khẩu trước khi lưu
        $data['password_hash'] = Hash::make($data['password_hash']);
        $data['is_active']     = $request->boolean('is_active', true);

        $user = User::query()->create($data);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Tạo người dùng thành công.');
    }

    public function show(User $user)
    {
        $user->load(['agency', 'role']);

        return view('user.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles    = LookupHelper::getValuesByTypeCode(LookupCode::TYPE_USER_ROLE);
        $agencies = Agency::query()->orderBy('name')->get();

        return view('user.edit', compact('user', 'roles', 'agencies'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $user->update($data);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Cập nhật người dùng thành công.');
    }

    public function destroy(User $user)
    {
        // Soft disable trước khi soft delete (không xóa cứng dữ liệu nghiệp vụ)
        $user->update(['is_active' => false]);
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Vô hiệu hóa người dùng thành công.');
    }
}
