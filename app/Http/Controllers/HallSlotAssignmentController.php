<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HallSlotAssignmentController extends Controller
{
    /**
     * Display the slot assignment view.
     */
    public function index()
    {
        abort_unless(
            auth()->user()->isSuperAdmin() || 
            auth()->user()->hasPermission('manage_settings') || 
            auth()->user()->hasPermission('edit_halls'), 
            403
        );
        return view('hall-slots.index');
    }
}
