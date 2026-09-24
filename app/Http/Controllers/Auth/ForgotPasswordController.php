<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Marquee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Display the form to request a password reset link.
     */
    public function showLinkRequestForm(): View
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($request->email));

        $user = User::where('email', $email)->first();

        $canReset = false;
        if ($user) {
            if ($user->isSuperAdmin() || strtolower((string) $user->status) === 'active') {
                $tenantActive = true;
                if (!$user->isSuperAdmin() && $user->marquee_id) {
                    $marquee = Marquee::find($user->marquee_id);
                    if ($marquee && isset($marquee->status) && strtolower((string) $marquee->status) !== 'active') {
                        $tenantActive = false;
                    }
                }

                if ($tenantActive) {
                    $canReset = true;
                } else {
                    Log::warning("Password reset requested for email with deactivated tenant: {$email}");
                }
            } else {
                Log::warning("Password reset requested for deactivated user: {$email}");
            }
        } else {
            Log::info("Password reset requested for non-existent email: {$email}");
        }

        if ($canReset) {
            Password::broker()->sendResetLink(['email' => $email]);

            try {
                ActivityLog::create([
                    'marquee_id' => $user->getActiveMarqueeId(),
                    'user_id' => $user->id,
                    'action' => 'password_reset_requested',
                    'model_type' => User::class,
                    'model_id' => $user->id,
                    'description' => "Password reset link requested for email: {$email}",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {}
        }

        // Anti-enumeration: always return generic success message to prevent user enumeration
        return back()->with('status', trans('passwords.sent'));
    }
}
