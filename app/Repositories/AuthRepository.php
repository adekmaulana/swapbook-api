<?php

namespace App\Repositories;

use App\Facades\ResponseFormatter;
use App\Interfaces\AuthRepositoryInterface;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthRepository implements AuthRepositoryInterface
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return ResponseFormatter::success(messages: 'User created successfully');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ResponseFormatter::error(
                401,
                'The provided credentials are incorrect.'
            );
        }

        // check token exist and delete it
        $token = $user->tokens->where('name', $request->device_name)->first();
        if ($token) {
            $token->delete();
        }

        return ResponseFormatter::success(
            [
                'token' => $user->createToken($request->device_name)->plainTextToken,
                'user' => $user,
            ],
            'User logged in successfully',
        );
    }

    public function loginGoogle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required',
            'google_id' => 'required',
            'device_name' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'google_id' => $request->google_id,
                'password' => Hash::make($request->google_id),
            ]);
        } else {
            if ($user->google_id === null) {
                $user->update([
                    'google_id' => $request->google_id,
                ]);
            }
        }

        // check token exist and delete it
        $token = $user->tokens->where('name', $request->device_name)->first();
        if ($token) {
            $token->delete();
        }

        return ResponseFormatter::success(
            [
                'token' => $user->createToken($request->device_name)->plainTextToken,
                'user' => $user,
            ],
            'User logged in with google successfully.',
        );
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return ResponseFormatter::success(messages: 'User logged out successfully.');
        } catch (\Exception) {
            return ResponseFormatter::error(
                500,
                'Oops! Something went wrong. Please try again later.'
            );
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return ResponseFormatter::success(messages: 'We have emailed your password reset link.');
        }

        if ($status === Password::INVALID_USER) {
            return ResponseFormatter::error(
                404,
                'We can\'t find a user with that email address.'
            );
        }

        if ($status === Password::RESET_THROTTLED) {
            return ResponseFormatter::error(
                429,
                'Too many reset password requests. Please try again later.'
            );
        }

        return ResponseFormatter::error(
            500,
            'Oops! Something went wrong. Please try again later.'
        );
    }

    public function resetPasswordProcess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }

        );

        return $status === Password::PASSWORD_RESET
            ? ResponseFormatter::success(messages: 'Password reset successfully.')
            : ResponseFormatter::error(
                500,
                'Oops! Something went wrong. Please try again later.'
            );
    }

    public function resetPassword(Request $request)
    {
        return view('auth.reset-password', [
            'token' => $request->token, 'email' => $request->email
        ]);
    }

    public function csrfCookie()
    {
        // redirect from '/api/v1/auth/sanctum/csrf-cookie' to '/sanctum/csrf-cookie'
        return new RedirectResponse('/sanctum/csrf-cookie');
    }

    public function ping(Request $request)
    {
        return ResponseFormatter::success(
            messages: 'Pong!'
        );
    }
}
