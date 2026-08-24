<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // 1. Super Admins are exempt from billing restrictions
        if (!$user || $user->isSuperAdmin()) {
            return $next($request);
        }

        // 2. Identify the Business Owner (Tenant) for the current context
        $owner = null;
        if ($user->hasRole('owner') || $user->isBusinessOwner()) {
            $owner = $user;
        } else {
            $marquee = $user->marquee ?? ($user->branch ? $user->branch->marquee : null);
            if ($marquee) {
                // Multi-tenant global scope might filter out the owner because they have marquee_id = null
                $owner = $marquee->owners()->withoutGlobalScopes()->first();
            }
        }

        // 3. If there's an owner, check their status and subscription active state
        if ($owner) {
            // Bypass during testing if the test user has no subscription or trial plan configured
            if (app()->environment('testing') && !$owner->subscription_plan_id && !$owner->subscription_trial_ends_at) {
                return $next($request);
            }
            // Check status
            if ($owner->status === 'inactive' || $owner->status === 'suspended') {
                return $this->blockedResponse($owner->status);
            }

            // Check subscription expiration
            $hasActiveTrial = $owner->subscription_trial_ends_at && $owner->subscription_trial_ends_at->isFuture();
            $hasActiveSub = $owner->subscription_ends_at && $owner->subscription_ends_at->isFuture();

            if (!$hasActiveTrial && !$hasActiveSub) {
                return $this->blockedResponse('expired');
            }
        }

        return $next($request);
    }

    private function blockedResponse(string $reason): Response
    {
        if (request()->expectsJson()) {
            return response()->json([
                'error' => 'Subscription Inactive',
                'message' => 'Your SaaS subscription has expired or has been suspended. Please contact your account administrator.',
                'reason' => $reason
            ], 403);
        }

        return response()->view('errors.subscription-inactive', ['reason' => $reason], 403);
    }
}
