<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Super Admins are exempt from tenant/account status lockouts
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $isBlocked = false;
        $reason = '';

        // 1. Check authenticated user account status
        $userStatus = strtolower(trim((string) ($user->status ?: 'active')));
        if ($userStatus !== 'active') {
            $isBlocked = true;
            $reason = 'Your account has been deactivated. Please contact your administrator.';
        }

        // 2. Check tenant/marquee status if user is scoped to a marquee
        if (!$isBlocked && $user->marquee_id) {
            $marquee = \App\Models\Marquee::find($user->marquee_id);
            if ($marquee && isset($marquee->status) && strtolower((string) $marquee->status) !== 'active') {
                $isBlocked = true;
                $reason = 'Your organization account is currently inactive. Please contact support.';
            }
        }

        if ($isBlocked) {
            // Log security session termination event
            try {
                \App\Models\ActivityLog::create([
                    'marquee_id' => $user->marquee_id,
                    'user_id' => $user->id,
                    'action' => 'session_terminated',
                    'model_type' => get_class($user),
                    'model_id' => $user->id,
                    'description' => "Session terminated mid-request for user '{$user->name}' due to inactive status",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {}

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->is('livewire/*') || $request->is('livewire/update')) {
                return response()->json([
                    'error' => 'Account Inactive',
                    'message' => $reason,
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'login' => $reason,
            ]);
        }

        return $next($request);
    }
}
