<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\Marquee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'), 403);
        return view('users.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('staff.index')->with('error', 'Users can only be created from the Staff Management section by adding logins to a staff member.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return redirect()->route('staff.index')->with('error', 'Users can only be created from the Staff Management section by adding logins to a staff member.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'), 403);

        $user->load(['role', 'branch', 'marquee']);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'), 403);

        $roles = auth()->user()->isSuperAdmin()
            ? Role::all()
            : Role::where('name', '!=', 'super_admin')->get();

        $branches = Branch::all();
        $marquees = auth()->user()->isSuperAdmin() ? Marquee::all() : [];

        return view('users.edit', compact('user', 'roles', 'branches', 'marquees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'), 403);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', Rules\Password::defaults()],
            'phone' => 'nullable|string|max:50',
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:active,inactive',
        ];

        if (auth()->user()->isSuperAdmin()) {
            $rules['marquee_id'] = 'nullable|exists:marquees,id';
        }

        $validated = $request->validate($rules);

        // Security check for role assignment
        $assignedRole = Role::find($validated['role_id']);
        if ($assignedRole->name === 'super_admin' && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized role assignment.');
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        if (!auth()->user()->isSuperAdmin()) {
            $validated['marquee_id'] = $user->marquee_id ?? auth()->user()->getActiveMarqueeId();
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'), 403);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
