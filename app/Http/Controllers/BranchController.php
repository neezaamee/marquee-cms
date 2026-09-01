<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Marquee;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);
        return view('branches.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        $marquees = [];
        if (auth()->user()->isSuperAdmin()) {
            $marquees = Marquee::all();
        }

        return view('branches.create', compact('marquees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->can('create', Branch::class), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'status' => 'required|in:active,inactive',
            'fbr_pos_id' => 'nullable|string|max:100',
            'fbr_pos_key' => 'nullable|string|max:255',
            'fbr_sandbox_mode' => 'sometimes|boolean',
        ];

        if ($user->isSuperAdmin()) {
            $rules['marquee_id'] = 'required|exists:marquees,id';
        }

        $validated = $request->validate($rules);
        $validated['fbr_sandbox_mode'] = $request->has('fbr_sandbox_mode');

        if (!$user->isSuperAdmin()) {
            $validated['marquee_id'] = $user->getActiveMarqueeId();
        }

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        abort_unless(auth()->user()->can('view', $branch), 403);

        $branch->load(['marquee', 'users']);
        return view('branches.show', compact('branch'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch)
    {
        abort_unless(auth()->user()->can('update', $branch), 403);

        $marquees = [];
        if (auth()->user()->isSuperAdmin()) {
            $marquees = Marquee::all();
        }

        return view('branches.edit', compact('branch', 'marquees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        $user = auth()->user();
        abort_unless($user->can('update', $branch), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'status' => 'required|in:active,inactive',
            'fbr_pos_id' => 'nullable|string|max:100',
            'fbr_pos_key' => 'nullable|string|max:255',
            'fbr_sandbox_mode' => 'sometimes|boolean',
        ];

        if ($user->isSuperAdmin()) {
            $rules['marquee_id'] = 'required|exists:marquees,id';
        }

        $validated = $request->validate($rules);
        $validated['fbr_sandbox_mode'] = $request->has('fbr_sandbox_mode');

        if (!$user->isSuperAdmin()) {
            $validated['marquee_id'] = $branch->marquee_id;
        }

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        abort_unless(auth()->user()->can('delete', $branch), 403);

        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }
}
