<?php

namespace App\Http\Controllers;

use App\Models\Hall;
use Illuminate\Http\Request;

class HallController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_halls'), 403);
        return view('halls.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_halls'), 403);
        return view('halls.create');
    }

    /**
     * Display the specified resource.
     */
    public function show(Hall $hall)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('view_halls'), 403);
        
        // Ensure tenant and branch scoping if not super admin
        if (!$user->isSuperAdmin()) {
            $marqueeId = $user->getActiveMarqueeId();
            if ($hall->marquee_id !== $marqueeId) {
                abort(403, 'Unauthorized access to this hall.');
            }
            if ($user->branch_id && (int)$hall->branch_id !== (int)$user->branch_id) {
                abort(403, 'Unauthorized access to another branch hall.');
            }
        }

        $hall->load(['branch', 'creator', 'slots']);
        return view('halls.show', compact('hall'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hall $hall)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('edit_halls'), 403);
        
        // Ensure tenant and branch scoping if not super admin
        if (!$user->isSuperAdmin()) {
            $marqueeId = $user->getActiveMarqueeId();
            if ($hall->marquee_id !== $marqueeId) {
                abort(403, 'Unauthorized access to this hall.');
            }
            if ($user->branch_id && (int)$hall->branch_id !== (int)$user->branch_id) {
                abort(403, 'Unauthorized access to another branch hall.');
            }
        }

        return view('halls.edit', compact('hall'));
    }
}
