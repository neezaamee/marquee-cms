<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    /**
     * Display the staff listing with search and pagination.
     */
    public function index()
    {
        return view('staff.index');
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $user = Auth::user();

        // Branch Managers can only assign staff to their own branch
        if ($user->hasRole('branch_manager')) {
            $branches = Branch::where('id', $user->branch_id)->get();
        } else {
            $branches = Branch::where('marquee_id', $user->marquee_id)->get();
        }

        // Branch Manager should not be able to add another Branch Manager
        $designations = \App\Models\Employee::DESIGNATIONS;
        if ($user->hasRole('branch_manager')) {
            $designations = array_filter($designations, fn($d) => $d !== 'Branch Manager');
        }

        $roles = Role::whereNotIn('name', ['super_admin'])->get();

        return view('staff.create', compact('branches', 'roles', 'designations'));
    }

    /**
     * Store a newly created employee in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'cnic'            => 'required|string|max:20',
            'mobile_number'   => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'designation'     => 'required|string',
            'joining_date'    => 'required|date',
            'salary'          => 'required|numeric|min:0',
            'employment_type' => 'required|string',
            'status'          => 'required|string',
            'branch_id'       => 'required|exists:branches,id',
            'photo'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Handle photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('staff/photos', 'public');
        }

        Employee::create([
            'marquee_id'      => Auth::user()->marquee_id,
            'branch_id'       => $validated['branch_id'],
            'name'            => $validated['name'],
            'cnic'            => $validated['cnic'],
            'mobile_number'   => $validated['mobile_number'],
            'designation'     => $validated['designation'],
            'joining_date'    => $validated['joining_date'],
            'salary'          => $validated['salary'],
            'employment_type' => $validated['employment_type'],
            'status'          => $validated['status'],
            'photo'           => $photoPath,
        ]);

        return redirect()->route('staff.index')
            ->with('success', 'Employee added successfully.');
    }

    /**
     * Display the specified employee's profile.
     */
    public function show(Employee $staff)
    {
        $staff->load(['branch', 'users.role']);
        return view('staff.show', compact('staff'));
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $staff)
    {
        $user = Auth::user();

        // Branch Managers can only see their own branch
        if ($user->hasRole('branch_manager')) {
            $branches = Branch::where('id', $user->branch_id)->get();
        } else {
            $branches = Branch::where('marquee_id', $user->marquee_id)->get();
        }

        // Branch Manager cannot change designation to Branch Manager, except when editing a Branch Manager (e.g. themselves)
        $designations = \App\Models\Employee::DESIGNATIONS;
        if ($user->hasRole('branch_manager')) {
            $designations = array_filter($designations, fn($d) => $d !== 'Branch Manager' || $staff->designation === 'Branch Manager');
        }

        $roles = Role::whereNotIn('name', ['super_admin'])->get();

        return view('staff.edit', compact('staff', 'branches', 'roles', 'designations'));
    }

    /**
     * Update the specified employee record.
     */
    public function update(Request $request, Employee $staff)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'cnic'            => 'required|string|max:20',
            'mobile_number'   => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'designation'     => 'required|string',
            'joining_date'    => 'required|date',
            'salary'          => 'required|numeric|min:0',
            'employment_type' => 'required|string',
            'status'          => 'required|string',
            'branch_id'       => 'required|exists:branches,id',
            'photo'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Handle photo upload
        $photoPath = $staff->photo;
        if ($request->hasFile('photo')) {
            // Delete old photo if it exists
            if ($staff->photo) {
                Storage::disk('public')->delete($staff->photo);
            }
            $photoPath = $request->file('photo')->store('staff/photos', 'public');
        }

        $staff->update([
            'branch_id'       => $validated['branch_id'],
            'name'            => $validated['name'],
            'cnic'            => $validated['cnic'],
            'mobile_number'   => $validated['mobile_number'],
            'designation'     => $validated['designation'],
            'joining_date'    => $validated['joining_date'],
            'salary'          => $validated['salary'],
            'employment_type' => $validated['employment_type'],
            'status'          => $validated['status'],
            'photo'           => $photoPath,
        ]);

        return redirect()->route('staff.index')
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Soft-delete the specified employee.
     */
    public function destroy(Employee $staff)
    {
        // Also soft-delete all linked user login accounts
        $staff->users()->delete();

        $staff->delete();

        return redirect()->route('staff.index')
            ->with('success', 'Employee removed successfully.');
    }

    /**
     * Manage CMS login profiles for a staff member.
     */
    public function logins(Employee $staff)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'), 403);
        return view('staff.logins', compact('staff'));
    }
}
