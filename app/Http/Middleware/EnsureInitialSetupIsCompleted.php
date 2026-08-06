<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInitialSetupIsCompleted
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is guest, let standard auth middleware handle it
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // 1. Super Admin is exempt from onboarding restrictions
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // 2. Allow core routes: dashboard, setup wizard, logout, and Livewire assets/requests
        $allowedRoutes = [
            'dashboard',
            'setup.wizard',
            'logout',
        ];

        $currentRouteName = $request->route() ? $request->route()->getName() : null;

        if (in_array($currentRouteName, $allowedRoutes) || 
            $request->is('livewire/*') || 
            $request->is('livewire/update') || 
            $request->is('_debugbar/*')) {
            return $next($request);
        }

        // 3. Check if setup is completed for the user's marquee
        $marquee = $user->marquee;
        
        if (!$marquee || !$marquee->is_setup_completed) {
            // Redirect to dashboard with a flash message warning
            return redirect()->route('dashboard')->with('warning', 'Please complete the initial configuration wizard before accessing operational modules.');
        }

        return $next($request);
    }
}
