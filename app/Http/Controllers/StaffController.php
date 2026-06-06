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
    public function index(Request $request)
    {
        $query = Employee::with(['branch', 'user']);

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('cnic', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Designation filter
        if ($designation = $request->get('designation')) {
            $query->where('designation', $designation);
        }

        $employees = $query->latest()->paginate(15)->withQueryString();

        return view('staff.index', compact('employees'));
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
            'mobile_number'   => 'required|string|max:20',
            'designation'     => 'required|string',
            'joining_date'    => 'required|date',
            'salary'          => 'required|numeric|min:0',
            'employment_type' => 'required|string',
            'status'          => 'required|string',
            'branch_id'       => 'required|exists:branches,id',
            'photo'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // CMS Login fields (conditional)
            'enable_login'    => 'nullable|boolean',
            'login_email'     => 'nullable|required_if:enable_login,1|email|unique:users,email',
            'login_password'  => 'nullable|required_if:enable_login,1|min:6',
            'login_role_id'   => 'nullable|required_if:enable_login,1|exists:roles,id',
        ]);

        // Handle photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('staff/photos', 'public');
        }

        // Create CMS login user if requested
        $userId = null;
        if ($request->boolean('enable_login')) {
            $loginUser = User::create([
                'name'       => $validated['name'],
                'email'      => $validated['login_email'],
                'password'   => Hash::make($validated['login_password']),
                'role_id'    => $validated['login_role_id'],
                'marquee_id' => Auth::user()->marquee_id,
                'branch_id'  => $validated['branch_id'],
                'status'     => 'active',
            ]);
            $userId = $loginUser->id;
        }

        Employee::create([
            'marquee_id'      => Auth::user()->marquee_id,
            'branch_id'       => $validated['branch_id'],
            'user_id'         => $userId,
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
        $staff->load(['branch', 'user.role']);
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
        $staff->load('user');

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
            'mobile_number'   => 'required|string|max:20',
            'designation'     => 'required|string',
            'joining_date'    => 'required|date',
            'salary'          => 'required|numeric|min:0',
            'employment_type' => 'required|string',
            'status'          => 'required|string',
            'branch_id'       => 'required|exists:branches,id',
            'photo'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // CMS Login fields (conditional)
            'enable_login'    => 'nullable|boolean',
            'login_email'     => 'nullable|email|unique:users,email,' . ($staff->user_id ?? 'NULL'),
            'login_password'  => 'nullable|min:6',
            'login_role_id'   => 'nullable|exists:roles,id',
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

        // Handle CMS login account
        if ($request->boolean('enable_login')) {
            $loginData = [
                'name'       => $validated['name'],
                'email'      => $validated['login_email'],
                'role_id'    => $validated['login_role_id'],
                'marquee_id' => Auth::user()->marquee_id,
                'branch_id'  => $validated['branch_id'],
            ];
            if ($validated['login_password'] ?? null) {
                $loginData['password'] = Hash::make($validated['login_password']);
            }

            if ($staff->user_id) {
                // Update existing user
                $staff->user->update($loginData);
            } else {
                // Create new user
                $loginData['password'] = Hash::make($validated['login_password']);
                $loginData['status']   = 'active';
                $loginUser             = User::create($loginData);
                $staff->user_id        = $loginUser->id;
            }
        } else {
            // Disable login: delete the linked user if they exist
            if ($staff->user_id && $staff->user) {
                $staff->user->delete();
                $staff->user_id = null;
            }
        }

        $staff->update([
            'branch_id'       => $validated['branch_id'],
            'user_id'         => $staff->user_id,
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
        // Also soft-delete the linked user login account if they have one
        if ($staff->user_id && $staff->user) {
            $staff->user->delete();
        }

        $staff->delete();

        return redirect()->route('staff.index')
            ->with('success', 'Employee removed successfully.');
    }
}
