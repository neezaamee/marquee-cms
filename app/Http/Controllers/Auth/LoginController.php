<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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
        // 1. Data sanitization & trimming
        $rawLogin = trim((string) $request->input('login', ''));
        if (filter_var($rawLogin, FILTER_VALIDATE_EMAIL)) {
            $rawLogin = strtolower($rawLogin);
        }
        $request->merge(['login' => $rawLogin]);

        // 2. Input validation with bounded length
        $loginData = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $loginInput = $loginData['login'];
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // 3. Rate limiting / brute-force throttle check
        $throttleKey = Str::transliterate(Str::lower($loginInput) . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'login' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ])->status(429);
        }

        // 4. Identity & Status verification
        $user = \App\Models\User::withoutGlobalScope('tenant')->where($field, $loginInput)->first();
        $authenticated = false;
        $failureReason = 'invalid_credentials';

        if ($user && Hash::check($loginData['password'], $user->password)) {
            // Check user account status
            if (strtolower((string) $user->status) !== 'active') {
                $failureReason = 'user_deactivated';
            } else {
                // Check tenant/marquee status if applicable (Super Admins are exempt)
                $tenantActive = true;
                if (!$user->isSuperAdmin() && $user->marquee_id) {
                    $marquee = \App\Models\Marquee::find($user->marquee_id);
                    if ($marquee && isset($marquee->status) && strtolower((string) $marquee->status) !== 'active') {
                        $tenantActive = false;
                        $failureReason = 'tenant_deactivated';
                    }
                }

                if ($tenantActive) {
                    $authenticated = true;
                }
            }
        }

        $remember = $request->boolean('remember');

        // 5. Authentication success
        if ($authenticated && Auth::attempt([$field => $loginInput, 'password' => $loginData['password'], 'status' => 'active'], $remember)) {
            RateLimiter::clear($throttleKey);
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

        // 6. Authentication failure: Hit rate limiter, log failure, and return generic error
        RateLimiter::hit($throttleKey, 60);

        try {
            \App\Models\ActivityLog::create([
                'marquee_id' => $user?->marquee_id,
                'user_id' => $user?->id,
                'action' => 'failed_login',
                'model_type' => $user ? get_class($user) : \App\Models\User::class,
                'model_id' => $user?->id,
                'description' => "Failed login attempt for identifier '{$loginInput}' [Reason: {$failureReason}]",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {}

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
