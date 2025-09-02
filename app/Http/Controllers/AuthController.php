<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getLogin(Request $request)
    {
        return Inertia::render('auths/login/Login', [
            'username' => '',
            'password' => '',
            'callback' => $request->get('callback')
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @throws ValidationException|AuthenticationException
     */
    public function checkLogin(LoginRequest $request)
    {
        $request->validated();

        if (Auth::attempt(['username' => $request['username'], 'password' => $request['password']])) {
            $request->session()->regenerate();
            $user = Auth::user();

            $token = $user->createToken('web-token', ['*'])->plainTextToken;

            //lưu api token trên session
            session('api-token', $token);
            return redirect()->intended();
        } else {
            throw ValidationException::withMessages([
                'password' => 'Sai mật khẩu'
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function logout(Request $request)
    {
        // Xóa tất cả tokens của user
        $request->user()?->tokens()?->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Logged out successfully']);
        }

        return redirect()->route('home');
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
