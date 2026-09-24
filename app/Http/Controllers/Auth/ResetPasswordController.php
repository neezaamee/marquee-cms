<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    /**
     * Display the password reset view for the given token.
     */
    public function showResetForm(Request $request, ?string $token = null): View
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the given user's password.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $credentials = [
            'email' => strtolower(trim($request->email)),
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
            'token' => $request->token,
        ];

        $response = Password::broker()->reset(
            $credentials,
            function (User $user, string $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                try {
                    ActivityLog::create([
                        'marquee_id' => $user->getActiveMarqueeId(),
                        'user_id' => $user->id,
                        'action' => 'password_reset_completed',
                        'model_type' => User::class,
                        'model_id' => $user->id,
                        'description' => 'User successfully reset their password via reset link.',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                } catch (\Throwable $e) {}
            }
        );

        if ($response === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', trans($response));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => trans($response)]);
    }
}
