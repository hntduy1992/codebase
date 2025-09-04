<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getLogin()
    {
        return Inertia::render('auths/login/Login', [
            'username' => '',
            'password' => ''
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @throws ValidationException
     */
    public function checkLogin(LoginRequest $request): \Illuminate\Http\RedirectResponse
    {
        $request->validated();
        $credentials = $request->only(['username', 'password']);
        // Sử dụng Auth::attempt để xác thực người dùng
        if (Auth::attempt($credentials)) {
            // Tái tạo session để ngăn chặn tấn công session fixation
            $request->session()->regenerate();

            // Chuyển hướng người dùng sau khi đăng nhập thành công
            return redirect()->intended();
        }

        // Nếu xác thực thất bại, quay lại trang đăng nhập với lỗi
        return back()->withErrors([
            'password' => 'Sai mật khẩu!',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
