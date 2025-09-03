<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
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
     */
    public function checkLogin(LoginRequest $request)
    {
        $request->validated();

        return response()->json([
            'message'=>'check ok'
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
