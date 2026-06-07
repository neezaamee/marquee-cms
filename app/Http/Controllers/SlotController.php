<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);
        return view('slots.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);
        return view('slots.create');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Slot $slot)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);
        
        // Ensure tenant scoping if not super admin
        if (!auth()->user()->isSuperAdmin() && $slot->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this slot.');
        }

        return view('slots.edit', compact('slot'));
    }
}
