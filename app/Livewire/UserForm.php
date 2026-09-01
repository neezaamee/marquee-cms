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
    public $cnic = '';

    // Lists
    public $marquees = [];
    public $branches = [];
    public $roles = [];

    public function formatPhoneNumber($phone)
    {
        return \App\Services\PhoneNumberService::normalize($phone);
    }

    public function formatPhoneForUi($phone)
    {
        return \App\Services\PhoneNumberService::formatForDisplay($phone);
    }

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
            // Tenant isolation check
            if (!$currentUser->isSuperAdmin() && $user->marquee_id !== $currentUser->marquee_id) {
                abort(403, 'Unauthorized operation.');
            }

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
            $this->phone = $this->formatPhoneForUi($user->phone);
            $this->status = $user->status;
            $this->employee_id = $user->employee_id;
            $this->cnic = $user->employee ? $user->employee->cnic : '';
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
            $rules['phone'] = ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'];
            $rules['cnic'] = ['required', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'];
        } else {
            $rules['cnic'] = 'nullable|string';
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

    protected function messages()
    {
        return [
            'phone.regex' => 'The phone number must be a valid Pakistani number starting with 03 (e.g. 0321-8611353).',
            'cnic.regex' => 'The CNIC format must be XXXXX-XXXXXXX-X (e.g. 35201-1234567-1).',
        ];
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

        if (!$this->employee_id) {
            $cleanPhone = $this->formatPhoneNumber($this->phone);
            
            // Map role to designation
            $role = Role::find($this->role_id);
            $designation = match($role->name) {
                'branch_manager' => 'Branch Manager',
                'booking_officer' => 'Booking Officer',
                'accountant' => 'Accountant',
                'store_keeper' => 'Store Keeper',
                'kitchen_manager' => 'Kitchen Manager',
                default => 'Helper / Labor',
            };

            $employee = \App\Models\Employee::create([
                'marquee_id' => $this->marquee_id,
                'branch_id' => $this->branch_id,
                'name' => $this->name,
                'cnic' => $this->cnic,
                'mobile_number' => $cleanPhone,
                'designation' => $designation,
                'joining_date' => now()->format('Y-m-d'),
                'salary' => 0.00,
                'employment_type' => 'Permanent',
                'status' => 'active',
            ]);

            $validatedData['employee_id'] = $employee->id;
            $validatedData['name'] = $this->name;
            $validatedData['phone'] = $cleanPhone;
        } else {
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
