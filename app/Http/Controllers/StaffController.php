<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /**
     * Authorize access to staff management with tenant and branch scoping.
     */
    private function authorizeStaffAccess(?Employee $staff = null): void
    {
        $user = Auth::user();
        $canManageStaff = $user && ($user->isSuperAdmin() || $user->isBusinessOwner() || $user->hasRole('branch_manager') || $user->hasPermission('manage_staff'));
        abort_unless($canManageStaff, 403, 'Unauthorized access to staff management.');

        if ($staff) {
            // Tenant isolation check
            if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($staff->marquee_id)) {
                abort(403, 'Unauthorized access to staff from another organization.');
            }

            // Branch Manager scoping check
            if ($user->hasRole('branch_manager') && (int) $staff->branch_id !== (int) $user->branch_id) {
                abort(403, 'Branch Managers cannot manage staff from another branch.');
            }
        }
    }

    /**
     * Display the staff listing with search and pagination.
     */
    public function index()
    {
        $this->authorizeStaffAccess();
        return view('staff.index');
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $this->authorizeStaffAccess();

        $user = Auth::user();
        $activeMarqueeId = $user->getActiveMarqueeId();

        // Branch Managers can only assign staff to their own branch
        if ($user->hasRole('branch_manager')) {
            $branches = Branch::where('id', $user->branch_id)->get();
        } elseif ($activeMarqueeId) {
            $branches = Branch::withoutGlobalScope('tenant')->where('marquee_id', $activeMarqueeId)->get();
        } else {
            $branches = Branch::where('marquee_id', $user->marquee_id)->get();
        }

        // Branch Manager should not be able to add another Branch Manager
        $designations = Employee::getDesignations();
        if ($user->hasRole('branch_manager')) {
            $designations = array_values(array_filter($designations, fn($d) => !in_array($d, ['Branch Manager', 'Admin / Area Manager / Branches Head'])));
        }

        $roles = Role::whereNotIn('name', ['super_admin'])->get();

        return view('staff.create', compact('branches', 'roles', 'designations'));
    }

    /**
     * Store a newly created employee in the database.
     */
    public function store(Request $request)
    {
        $this->authorizeStaffAccess();

        $user = Auth::user();
        $activeMarqueeId = $user->getActiveMarqueeId() ?: $user->marquee_id;

        // Branch Managers can only assign staff to their own branch
        if ($user->hasRole('branch_manager') && (int) $request->branch_id !== (int) $user->branch_id) {
            abort(403, 'Branch Managers cannot assign staff to another branch.');
        }

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'cnic'            => 'required|string|max:20',
            'mobile_number'   => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'designation'     => 'required|string',
            'joining_date'    => 'required|date',
            'salary'          => 'required|numeric|min:0',
            'employment_type' => 'required|string',
            'status'          => 'required|string',
            'branch_id'       => [
                'required',
                $user->isSuperAdmin()
                    ? 'exists:branches,id'
                    : Rule::exists('branches', 'id')->where('marquee_id', $activeMarqueeId),
            ],
            'photo'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Handle photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('staff/photos', 'public');
        }

        Employee::create([
            'marquee_id'      => $activeMarqueeId,
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
        $this->authorizeStaffAccess($staff);

        $staff->load(['branch', 'users.role']);
        return view('staff.show', compact('staff'));
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $staff)
    {
        $this->authorizeStaffAccess($staff);

        $user = Auth::user();
        $activeMarqueeId = $user->getActiveMarqueeId();

        // Branch Managers can only see their own branch
        if ($user->hasRole('branch_manager')) {
            $branches = Branch::where('id', $user->branch_id)->get();
        } elseif ($activeMarqueeId) {
            $branches = Branch::withoutGlobalScope('tenant')->where('marquee_id', $activeMarqueeId)->get();
        } else {
            $branches = Branch::where('marquee_id', $user->marquee_id)->get();
        }

        // Branch Manager cannot change designation to Branch Manager, except when editing a Branch Manager (e.g. themselves)
        $designations = Employee::getDesignations();
        if (!empty($staff->designation) && !in_array($staff->designation, $designations)) {
            $designations[] = $staff->designation;
            natcasesort($designations);
            $designations = array_values($designations);
        }
        if ($user->hasRole('branch_manager')) {
            $designations = array_values(array_filter($designations, fn($d) => $d !== 'Branch Manager' || $staff->designation === 'Branch Manager'));
        }

        $roles = Role::whereNotIn('name', ['super_admin'])->get();

        return view('staff.edit', compact('staff', 'branches', 'roles', 'designations'));
    }

    /**
     * Update the specified employee record.
     */
    public function update(Request $request, Employee $staff)
    {
        $this->authorizeStaffAccess($staff);

        $user = Auth::user();
        $activeMarqueeId = $staff->marquee_id ?: ($user->getActiveMarqueeId() ?: $user->marquee_id);

        // Branch Managers can only assign staff to their own branch
        if ($user->hasRole('branch_manager') && (int) $request->branch_id !== (int) $user->branch_id) {
            abort(403, 'Branch Managers cannot reassign staff to another branch.');
        }

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'cnic'            => 'required|string|max:20',
            'mobile_number'   => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'designation'     => 'required|string',
            'joining_date'    => 'required|date',
            'salary'          => 'required|numeric|min:0',
            'employment_type' => 'required|string',
            'status'          => 'required|string',
            'branch_id'       => [
                'required',
                $user->isSuperAdmin()
                    ? 'exists:branches,id'
                    : Rule::exists('branches', 'id')->where('marquee_id', $activeMarqueeId),
            ],
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
        $this->authorizeStaffAccess($staff);

        // Also soft-delete all linked user login accounts
        $staff->users()->delete();

        $staff->delete();

        try {
            ActivityLog::create([
                'marquee_id' => $staff->marquee_id,
                'user_id' => Auth::id(),
                'action' => 'staff_deleted',
                'model_type' => Employee::class,
                'model_id' => $staff->id,
                'description' => "Employee '{$staff->name}' ({$staff->designation}) was deleted.",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('staff.index')
            ->with('success', 'Employee removed successfully.');
    }

    /**
     * Manage CMS login profiles for a staff member.
     */
    public function logins(Employee $staff)
    {
        $this->authorizeStaffAccess($staff);
        return view('staff.logins', compact('staff'));
    }
}
