<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Authorize access to user management with tenant isolation and privilege escalation prevention.
     */
    private function authorizeUserAccess(?User $targetUser = null, bool $allowSelf = false): void
    {
        $currentUser = Auth::user();
        abort_unless($currentUser && ($currentUser->isSuperAdmin() || $currentUser->hasPermission('manage_staff')), 403, 'Unauthorized access to user management.');

        if ($targetUser) {
            // Tenant isolation check
            if (!$currentUser->isSuperAdmin() && !$currentUser->hasAccessToMarquee($targetUser->marquee_id)) {
                abort(403, 'Unauthorized access to user from another organization.');
            }

            // Privilege escalation prevention:
            // Non-super admins cannot modify or delete Super Admin users
            if ($targetUser->isSuperAdmin() && !$currentUser->isSuperAdmin()) {
                abort(403, 'Unauthorized operation on Super Admin user.');
            }

            // Non-super admins cannot modify or delete Business Owners (unless it is themselves)
            if ($targetUser->isBusinessOwner() && !$currentUser->isSuperAdmin() && (!$allowSelf || $currentUser->id !== $targetUser->id)) {
                abort(403, 'Unauthorized operation on Business Owner account.');
            }
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorizeUserAccess();
        return view('users.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorizeUserAccess();
        return view('users.create');
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
        $this->authorizeUserAccess($user, true);

        $user->load(['role', 'branch', 'marquee']);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $this->authorizeUserAccess($user, true);

        $roles = Auth::user()->isSuperAdmin()
            ? Role::all()
            : Role::whereNotIn('name', ['super_admin', 'business_owner', 'owner'])->get();

        $branches = Branch::all();
        $marquees = Auth::user()->isSuperAdmin() ? Marquee::all() : [];

        return view('users.edit', compact('user', 'roles', 'branches', 'marquees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeUserAccess($user, true);

        $currentUser = Auth::user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:active,inactive',
        ];

        if ($currentUser->isSuperAdmin()) {
            $rules['marquee_id'] = 'nullable|exists:marquees,id';
        }

        $validated = $request->validate($rules);

        // Security check for role assignment privilege escalation
        $assignedRole = Role::findOrFail($validated['role_id']);
        if ($assignedRole->name === 'super_admin' && !$currentUser->isSuperAdmin()) {
            abort(403, 'Unauthorized role assignment: cannot assign super admin.');
        }
        if (in_array($assignedRole->name, ['business_owner', 'owner']) && !$currentUser->isSuperAdmin() && !$currentUser->isBusinessOwner()) {
            abort(403, 'Unauthorized role assignment: cannot assign owner role.');
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        if (!$currentUser->isSuperAdmin()) {
            $validated['marquee_id'] = $user->marquee_id ?? $currentUser->getActiveMarqueeId();
        }

        $user->update($validated);

        try {
            ActivityLog::create([
                'marquee_id' => $user->marquee_id,
                'user_id' => $currentUser->id,
                'action' => 'user_updated',
                'model_type' => User::class,
                'model_id' => $user->id,
                'description' => "User account '{$user->name}' was updated.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorizeUserAccess($user, false);

        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        try {
            ActivityLog::create([
                'marquee_id' => $user->marquee_id,
                'user_id' => Auth::id(),
                'action' => 'user_deleted',
                'model_type' => User::class,
                'model_id' => $user->id,
                'description' => "User account '{$user->name}' was deleted.",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
