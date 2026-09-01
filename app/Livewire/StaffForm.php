<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class StaffForm extends Component
{
    use WithFileUploads;

    public $isEditMode = false;
    public $staffId = null;

    // Fields
    public $name = '';
    public $cnic = '';
    public $mobile_number = '';
    public $designation = '';
    public $joining_date = '';
    public $salary = '';
    public $employment_type = 'Permanent';
    public $status = 'active';
    public $branch_id = '';
    public $photo = null;
    public $existingPhoto = null;

    // Lists
    public $branches = [];
    public $roles = [];
    public $designations = [];
    public $employmentTypes = [];
    public $statuses = [];

    public function mount($staff = null)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasRole(['owner', 'branch_manager']) || $user->hasPermission('manage_staff'), 403);

        // Fetch lists based on authorization
        if ($user->hasRole('branch_manager')) {
            $this->branches = Branch::where('id', $user->branch_id)->get();
            $this->branch_id = $user->branch_id;
        } else {
            $this->branches = Branch::all();
        }

        // Branch Managers can't assign other Branch Managers
        $allDesignations = Employee::DESIGNATIONS;
        if ($user->hasRole('branch_manager')) {
            $this->designations = array_filter($allDesignations, fn($d) => $d !== 'Branch Manager');
        } else {
            $this->designations = $allDesignations;
        }

        $this->roles = Role::where('name', '!=', 'super_admin')->orderBy('label')->get();
        $this->employmentTypes = Employee::EMPLOYMENT_TYPES;
        $this->statuses = Employee::STATUSES;

        if ($staff) {
            // Tenant isolation check
            if (!$user->isSuperAdmin() && $staff->marquee_id !== $user->marquee_id) {
                abort(403, 'Unauthorized operation.');
            }

            $this->isEditMode = true;
            $this->staffId = $staff->id;
            $this->name = $staff->name;
            $this->cnic = $staff->cnic;
            $this->mobile_number = $staff->mobile_number;
            $this->designation = $staff->designation;
            $this->joining_date = date('Y-m-d', strtotime($staff->joining_date));
            $this->salary = $staff->salary;
            $this->employment_type = $staff->employment_type;
            $this->status = $staff->status;
            $this->branch_id = $staff->branch_id;
            $this->existingPhoto = $staff->photo;
        }
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'cnic' => 'required|string|max:20',
            'mobile_number' => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'designation' => 'required|string',
            'joining_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'employment_type' => 'required|string',
            'status' => 'required|string',
            'branch_id' => 'required|exists:branches,id',
            'photo' => 'nullable|image|max:2048', // 2MB Max
        ];
    }

    public function save()
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasRole(['owner', 'branch_manager']) || $user->hasPermission('manage_staff'), 403);

        $validatedData = $this->validate();

        // Handle photo upload
        $photoPath = $this->existingPhoto;
        if ($this->photo) {
            if ($this->existingPhoto) {
                Storage::disk('public')->delete($this->existingPhoto);
            }
            $photoPath = $this->photo->store('staff/photos', 'public');
        }

        // Create or Update Employee
        $staffData = [
            'marquee_id' => auth()->user()->marquee_id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'cnic' => $this->cnic,
            'mobile_number' => $this->mobile_number,
            'designation' => $this->designation,
            'joining_date' => $this->joining_date,
            'salary' => $this->salary,
            'employment_type' => $this->employment_type,
            'status' => $this->status,
            'photo' => $photoPath,
        ];

        if ($this->isEditMode) {
            $staff = Employee::findOrFail($this->staffId);
            
            // Security scope check
            if (!auth()->user()->isSuperAdmin() && $staff->marquee_id !== auth()->user()->marquee_id) {
                abort(403, 'Unauthorized.');
            }

            $staff->update($staffData);
            session()->flash('success', 'Employee updated successfully.');
        } else {
            Employee::create($staffData);
            session()->flash('success', 'Employee added successfully.');
        }

        return redirect()->route('staff.index');
    }

    public function render()
    {
        return view('livewire.staff-form');
    }
}
