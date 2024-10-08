<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\AuthRepository;

class AuthController extends Controller
{
    protected $authRepository;

    public function __construct(AuthRepository $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function register(Request $request)
    {
        return $this->authRepository->register($request);
    }

    public function login(Request $request)
    {
        return $this->authRepository->login($request);
    }

    public function loginGoogle(Request $request)
    {
        return $this->authRepository->loginGoogle($request);
    }

    public function logout(Request $request)
    {
        return $this->authRepository->logout($request);
    }

    public function forgotPassword(Request $request)
    {
        return $this->authRepository->forgotPassword($request);
    }

    public function resetPassword(Request $request)
    {
        return $this->authRepository->resetPassword($request);
    }

    public function resetPasswordProcess(Request $request)
    {
        return $this->authRepository->resetPasswordProcess($request);
    }

    public function csrfCookie()
    {
        return $this->authRepository->csrfCookie();
    }

    public function ping(Request $request)
    {
        return $this->authRepository->ping($request);
    }
}
