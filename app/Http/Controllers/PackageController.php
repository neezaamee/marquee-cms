<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display a listing of the packages.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_packages'), 403);
        return view('packages.index');
    }

    /**
     * Show the form for creating a new package.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('create_packages'), 403);
        return view('packages.create');
    }

    /**
     * Display the specified package details/preview.
     */
    public function show(Package $package)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_packages'), 403);

        if (!auth()->user()->isSuperAdmin() && $package->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this package.');
        }

        return redirect()->route('packages.preview', $package->id);
    }

    /**
     * Show the form for editing the specified package metadata.
     */
    public function edit(Package $package)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_packages'), 403);

        if (!auth()->user()->isSuperAdmin() && $package->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this package.');
        }

        return view('packages.edit', compact('package'));
    }

    /**
     * Show the package builder screen.
     */
    public function builder(Package $package)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_packages'), 403);

        if (!auth()->user()->isSuperAdmin() && $package->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this package.');
        }

        return view('packages.builder', compact('package'));
    }

    /**
     * Show the package preview dashboard.
     */
    public function preview(Package $package)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_packages'), 403);

        if (!auth()->user()->isSuperAdmin() && $package->marquee_id !== auth()->user()->marquee_id) {
            abort(403, 'Unauthorized access to this package.');
        }

        return view('packages.preview', compact('package'));
    }
}
