<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class UserForm extends Component
{
    public $isEditMode = false;
    public $userId = null;

    // Fields
    public $marquee_id = null;
    public $branch_id = null;
    public $role_id = '';
    public $name = '';
    public $email = '';
    public $username = '';
    public $password = '';
    public $phone = '';
    public $status = 'active';
    public $employee_id = null;

    // Lists
    public $marquees = [];
    public $branches = [];
    public $roles = [];

    public function mount($user = null)
    {
        $currentUser = auth()->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->hasPermission('manage_staff'), 403);

        // Fetch marquees for super admin
        if ($currentUser->isSuperAdmin()) {
            $this->marquees = Marquee::orderBy('name')->get();
        } else {
            $this->marquee_id = $currentUser->marquee_id;
            $this->branches = Branch::where('marquee_id', $this->marquee_id)->orderBy('name')->get();
        }

        // Fetch roles
        $this->roles = $currentUser->isSuperAdmin()
            ? Role::orderBy('label')->get()
            : Role::where('name', '!=', 'super_admin')->orderBy('label')->get();

        if ($user) {
            $this->isEditMode = true;
            $this->userId = $user->id;
            $this->marquee_id = $user->marquee_id;
            
            // Reload branches for super admin based on user's marquee
            if ($currentUser->isSuperAdmin() && $this->marquee_id) {
                $this->branches = Branch::where('marquee_id', $this->marquee_id)->orderBy('name')->get();
            }

            $this->branch_id = $user->branch_id;
            $this->role_id = $user->role_id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->username = $user->username;
            $this->phone = $user->phone;
            $this->status = $user->status;
            $this->employee_id = $user->employee_id;
        }
    }

    public function updatedMarqueeId($value)
    {
        if (auth()->user()->isSuperAdmin() && $value) {
            $this->branches = Branch::where('marquee_id', $value)->orderBy('name')->get();
            $this->branch_id = null;
        } else {
            $this->branches = [];
            $this->branch_id = null;
        }
    }

    protected function rules()
    {
        $rules = [
            'email' => 'required|string|email|max:255|unique:users,email,' . ($this->userId ?? 'NULL'),
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username,' . ($this->userId ?? 'NULL'), 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'role_id' => 'required|exists:roles,id',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:active,inactive',
        ];

        if (!$this->employee_id) {
            $rules['name'] = 'required|string|max:255';
            $rules['phone'] = 'nullable|string|max:50';
        }

        if (auth()->user()->isSuperAdmin()) {
            $rules['marquee_id'] = 'nullable|exists:marquees,id';
        }

        if (!$this->isEditMode) {
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        return $rules;
    }

    public function save()
    {
        $currentUser = auth()->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->hasPermission('manage_staff'), 403);

        if (!$currentUser->isSuperAdmin()) {
            $this->marquee_id = $currentUser->marquee_id;
        }

        $validatedData = $this->validate();

        // Extra role assignment security check
        $assignedRole = Role::find($this->role_id);
        if ($assignedRole->name === 'super_admin' && !$currentUser->isSuperAdmin()) {
            abort(403, 'Unauthorized role assignment.');
        }

        // Format password
        if (empty($validatedData['password'])) {
            unset($validatedData['password']);
        } else {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        if ($this->employee_id) {
            $employee = \App\Models\Employee::find($this->employee_id);
            if ($employee) {
                $validatedData['name'] = $employee->name;
                $validatedData['phone'] = $employee->mobile_number;
                $validatedData['employee_id'] = $this->employee_id;
            }
        }

        if ($this->isEditMode) {
            $userModel = User::findOrFail($this->userId);

            // Tenant security check
            if (!$currentUser->isSuperAdmin() && $userModel->marquee_id !== $currentUser->marquee_id) {
                abort(403, 'Unauthorized operation.');
            }

            $userModel->update($validatedData);
            session()->flash('success', 'User updated successfully.');
        } else {
            // Check plan limits
            $owner = null;
            if (auth()->user()->isSuperAdmin()) {
                if ($this->marquee_id) {
                    $marquee = Marquee::find($this->marquee_id);
                    $owner = $marquee ? $marquee->owners()->first() : null;
                }
            } else {
                $marquee = auth()->user()->marquee;
                $owner = $marquee ? $marquee->owners()->first() : null;
            }

            if ($owner && !$owner->canCreateUser()) {
                $this->addError('username', 'This tenant has reached the maximum number of users allowed by their subscription plan.');
                return;
            }

            User::create($validatedData);
            session()->flash('success', 'User created successfully.');
        }

        return redirect()->route('users.index');
    }

    public function render()
    {
        return view('livewire.user-form');
    }
}
