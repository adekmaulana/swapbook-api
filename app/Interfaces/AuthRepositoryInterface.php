<?php

namespace App\Interfaces;

use Illuminate\Http\Request;

interface AuthRepositoryInterface
{
    public function register(Request $request);
    public function login(Request $request);
    public function loginGoogle(Request $request);
    public function logout(Request $request);
    public function forgotPassword(Request $request);
    public function resetPassword(Request $request);
    public function resetPasswordProcess(Request $request);
    public function csrfCookie();
    public function user(Request $request);
    public function ping(Request $request);
}
