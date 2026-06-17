<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ManageStaffLogins extends Component
{
    public $staff;

    // Add Login Form fields
    public $branch_id = '';
    public $email = '';
    public $username = '';
    public $role_id = '';
    public $password = '';

    // Action Edit states
    public $editingUserId = null;
    public $edit_role_id = '';
    public $edit_password = '';

    public function mount(Employee $staff)
    {
        $currentUser = auth()->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->hasRole(['owner', 'branch_manager']) || $currentUser->hasPermission('manage_staff'), 403);

        $this->staff = $staff;

        // Auto-assign branch if there is only one available
        $availableBranches = $this->getBranches();
        if ($availableBranches->count() === 1) {
            $this->branch_id = $availableBranches->first()->id;
        }
    }

    protected function rules()
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'email' => 'required|email|max:255|unique:users,email',
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:6',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'branch_id' => 'branch',
            'role_id' => 'role',
        ];
    }

    public function addLogin()
    {
        $currentUser = auth()->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->hasRole(['owner', 'branch_manager']) || $currentUser->hasPermission('manage_staff'), 403);

        $this->validate();

        // Security check for branch manager scope
        if ($currentUser->hasRole('branch_manager') && (int)$this->branch_id !== (int)$currentUser->branch_id) {
            abort(403, 'Unauthorized branch assignment.');
        }

        // Security check for role assignment
        $assignedRole = Role::findOrFail($this->role_id);
        if ($assignedRole->name === 'super_admin' || ($currentUser->hasRole('branch_manager') && $assignedRole->name === 'branch_manager')) {
            abort(403, 'Unauthorized role assignment.');
        }

        User::create([
            'name' => $this->staff->name,
            'email' => $this->email,
            'username' => $this->username,
            'password' => Hash::make($this->password),
            'role_id' => $this->role_id,
            'employee_id' => $this->staff->id,
            'branch_id' => $this->branch_id,
            'marquee_id' => $this->staff->marquee_id,
            'phone' => $this->staff->mobile_number,
            'status' => 'active',
        ]);

        $this->reset(['email', 'username', 'role_id', 'password']);
        
        // Auto-assign branch if there is only one available
        $availableBranches = $this->getBranches();
        if ($availableBranches->count() === 1) {
            $this->branch_id = $availableBranches->first()->id;
        } else {
            $this->branch_id = '';
        }

        session()->flash('success', 'CMS Login account created successfully.');
        $this->staff->load('users');
    }

    public function toggleStatus(int $userId)
    {
        $currentUser = auth()->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->hasRole(['owner', 'branch_manager']) || $currentUser->hasPermission('manage_staff'), 403);

        $user = User::where('employee_id', $this->staff->id)->findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot deactivate your own account.');
            return;
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        session()->flash('success', 'User login status updated.');
        $this->staff->load('users');
    }

    public function deleteLogin(int $userId)
    {
        $currentUser = auth()->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->hasRole(['owner', 'branch_manager']) || $currentUser->hasPermission('manage_staff'), 403);

        $user = User::where('employee_id', $this->staff->id)->findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $user->delete();

        session()->flash('success', 'CMS Login account deleted.');
        $this->staff->load('users');
    }

    public function startEditing(int $userId)
    {
        $user = User::where('employee_id', $this->staff->id)->findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->edit_role_id = $user->role_id;
        $this->edit_password = '';
    }

    public function cancelEditing()
    {
        $this->editingUserId = null;
        $this->edit_role_id = '';
        $this->edit_password = '';
    }

    public function saveEdit()
    {
        $currentUser = auth()->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->hasRole(['owner', 'branch_manager']) || $currentUser->hasPermission('manage_staff'), 403);

        $user = User::where('employee_id', $this->staff->id)->findOrFail($this->editingUserId);

        $rules = [
            'edit_role_id' => 'required|exists:roles,id',
            'edit_password' => 'nullable|string|min:6',
        ];

        $this->validate($rules);

        // Security check for role assignment
        $assignedRole = Role::findOrFail($this->edit_role_id);
        if ($assignedRole->name === 'super_admin' || ($currentUser->hasRole('branch_manager') && $assignedRole->name === 'branch_manager')) {
            abort(403, 'Unauthorized role assignment.');
        }

        $user->role_id = $this->edit_role_id;

        if (!empty($this->edit_password)) {
            $user->password = Hash::make($this->edit_password);
        }

        $user->save();

        $this->cancelEditing();
        session()->flash('success', 'CMS Login account updated successfully.');
        $this->staff->load('users');
    }

    private function getBranches()
    {
        $currentUser = auth()->user();
        if ($currentUser->hasRole('branch_manager')) {
            return Branch::where('id', $currentUser->branch_id)->get();
        } else {
            return Branch::where('marquee_id', $this->staff->marquee_id)->get();
        }
    }

    private function getRoles()
    {
        $currentUser = auth()->user();
        if ($currentUser->hasRole('branch_manager')) {
            return Role::whereNotIn('name', ['super_admin', 'branch_manager', 'owner'])->orderBy('label')->get();
        } else {
            return Role::where('name', '!=', 'super_admin')->orderBy('label')->get();
        }
    }

    public function render()
    {
        return view('livewire.manage-staff-logins', [
            'branches' => $this->getBranches(),
            'roles' => $this->getRoles(),
        ]);
    }
}
