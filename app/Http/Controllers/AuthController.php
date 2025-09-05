<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getLogin(Request $request)
    {

        die($request->get('callback'));

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
            $url = session()->pull('url.intended');
            return redirect()->intended($url);
        }

        // Nếu xác thực thất bại, quay lại trang đăng nhập với lỗi
        return back()->withErrors([
            'password' => 'Sai mật khẩu!',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
