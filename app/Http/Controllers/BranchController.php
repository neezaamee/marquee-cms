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
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        $query = Branch::with('marquee');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $branches = $query->paginate(10);
        return view('branches.index', compact('branches'));
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
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
            'fbr_pos_id' => 'nullable|string|max:100',
            'fbr_pos_key' => 'nullable|string|max:255',
            'fbr_sandbox_mode' => 'sometimes|boolean',
        ];

        if (auth()->user()->isSuperAdmin()) {
            $rules['marquee_id'] = 'required|exists:marquees,id';
        }

        $validated = $request->validate($rules);
        $validated['fbr_sandbox_mode'] = $request->has('fbr_sandbox_mode');

        if (!auth()->user()->isSuperAdmin()) {
            $validated['marquee_id'] = auth()->user()->marquee_id;
        }

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        $branch->load(['marquee', 'users']);
        return view('branches.show', compact('branch'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

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
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
            'fbr_pos_id' => 'nullable|string|max:100',
            'fbr_pos_key' => 'nullable|string|max:255',
            'fbr_sandbox_mode' => 'sometimes|boolean',
        ];

        if (auth()->user()->isSuperAdmin()) {
            $rules['marquee_id'] = 'required|exists:marquees,id';
        }

        $validated = $request->validate($rules);
        $validated['fbr_sandbox_mode'] = $request->has('fbr_sandbox_mode');

        if (!auth()->user()->isSuperAdmin()) {
            $validated['marquee_id'] = auth()->user()->marquee_id;
        }

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully.');
    }
}
