<?php

namespace App\Http\Controllers;

use App\Models\ExtraService;
use Illuminate\Http\Request;

class ExtraServiceController extends Controller
{
    /**
     * Display a listing of the extra services.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);
        return view('extra_services.index');
    }

    /**
     * Show the form for creating a new extra service.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);
        return view('extra_services.create');
    }

    /**
     * Show the form for editing the specified extra service.
     */
    public function edit(ExtraService $extraService)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        // Tenant scoping security check
        if (!auth()->user()->isSuperAdmin() && $extraService->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this extra service.');
        }

        return view('extra_services.edit', compact('extraService'));
    }
}
