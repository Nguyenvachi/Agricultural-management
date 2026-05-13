<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /** Hiển thị form đăng nhập */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('orders.index');
        }

        return view('auth.login');
    }

    /** Xử lý đăng nhập */
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $credentials = [
            'username'  => $request->input('username'),
            'password'  => $request->input('password'),
            'is_active' => true,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Eager-load role để có sẵn trong session
            Auth::user()->loadMissing('role');

            return redirect()
                ->intended(route('orders.index'))
                ->with('success', 'Đăng nhập thành công! Xin chào, ' . Auth::user()->full_name . '.');
        }

        return back()
            ->withInput(['username' => $request->input('username')])
            ->withErrors(['username' => 'Tên đăng nhập hoặc mật khẩu không đúng, hoặc tài khoản đã bị vô hiệu hóa.']);
    }

    /** Đăng xuất */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Đã đăng xuất thành công.');
    }
}
