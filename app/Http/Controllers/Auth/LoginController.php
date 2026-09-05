<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $loginData = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = $loginData['login'];
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $field => $loginInput,
            'password' => $loginData['password'],
        ];

        $user = \App\Models\User::withoutGlobalScope('tenant')->where($field, $loginInput)->first();
        if ($user && !\Illuminate\Support\Facades\Hash::check($loginData['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        if ($user && strtolower($user->status) !== 'active') {
            throw ValidationException::withMessages([
                'login' => 'Your account has been deactivated. Please contact your administrator.',
            ]);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt(array_merge($credentials, ['status' => 'active']), $remember)) {
            $request->session()->regenerate();

            $authUser = Auth::user();
            try {
                \App\Models\ActivityLog::create([
                    'marquee_id' => $authUser->marquee_id,
                    'user_id' => $authUser->id,
                    'action' => 'login',
                    'model_type' => get_class($authUser),
                    'model_id' => $authUser->id,
                    'description' => "User '{$authUser->name}' logged into system",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {}

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'login' => __('auth.failed'),
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser) {
            try {
                \App\Models\ActivityLog::create([
                    'marquee_id' => $authUser->marquee_id,
                    'user_id' => $authUser->id,
                    'action' => 'logout',
                    'model_type' => get_class($authUser),
                    'model_id' => $authUser->id,
                    'description' => "User '{$authUser->name}' logged out of system",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {}
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
