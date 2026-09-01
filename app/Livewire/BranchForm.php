<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Marquee;
use Livewire\Component;

class BranchForm extends Component
{
    public $isEditMode = false;
    public $branchId = null;

    // Fields
    public $marquee_id = null;
    public $name = '';
    public $address = '';
    public $city = '';
    public $province = '';
    public $phone = '';
    public $status = 'active';
    public $fbr_pos_id = '';
    public $fbr_pos_key = '';
    public $fbr_sandbox_mode = true;
    public $tax_rate = 13.00;
    public $invoice_prefix = 'INV-';
    public $booking_prefix = 'BK-';
    public $branch_manager = '';
    public $custom_branch_manager = '';

    // Starter Hall Configuration (When creating a new branch)
    public $create_initial_hall = true;
    public $initial_hall_name = 'Main Banquet Hall';
    public $initial_hall_code = 'HALL-01';
    public $initial_hall_capacity = 500;
    public $initial_hall_type = 'Banquet Hall';
    public $initial_hall_price = 50000;

    // Lists
    public $marquees = [];
    public $cities = [];

    public function formatPhoneNumber($phone)
    {
        return \App\Services\PhoneNumberService::normalize($phone);
    }

    public function formatPhoneForUi($phone)
    {
        return \App\Services\PhoneNumberService::formatForDisplay($phone);
    }

    protected function getCitiesForProvince($province)
    {
        $citiesByProvince = [
            "Punjab" => ["Lahore", "Faisalabad", "Rawalpindi", "Gujranwala", "Multan", "Bahawalpur", "Sargodha", "Sialkot", "Sheikhupura", "Rahim Yar Khan"],
            "Sindh" => ["Karachi", "Hyderabad", "Sukkur", "Larkana", "Nawabshah", "Mirpur Khas"],
            "Khyber Pakhtunkhwa" => ["Peshawar", "Mardan", "Mingora", "Kohat", "Abbottabad", "Dera Ismail Khan"],
            "Balochistan" => ["Quetta", "Gwadar", "Khuzdar", "Turbat", "Sibi"],
            "Islamabad Capital Territory" => ["Islamabad"]
        ];

        return $citiesByProvince[$province] ?? [];
    }

    public function updatedProvince($value)
    {
        $this->cities = $this->getCitiesForProvince($value);
        $this->city = '';
    }

    public function mount($branch = null)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $this->marquees = Marquee::orderBy('name')->get();
        } else {
            $this->marquee_id = $user->marquee_id;
        }

        if ($branch) {
            $this->isEditMode = true;
            $this->branchId = $branch->id;
            $this->marquee_id = $branch->marquee_id;
            $this->name = $branch->name;
            $this->address = $branch->address;
            $this->province = $branch->province;
            $this->cities = $this->getCitiesForProvince($branch->province);
            $this->city = $branch->city;
            $this->phone = $this->formatPhoneForUi($branch->phone);
            $this->status = $branch->status;
            $this->fbr_pos_id = $branch->fbr_pos_id;
            $this->fbr_pos_key = $branch->fbr_pos_key;
            $this->fbr_sandbox_mode = (bool)$branch->fbr_sandbox_mode;
            $this->tax_rate = $branch->tax_rate !== null ? (float)$branch->tax_rate : 13.00;
            $this->invoice_prefix = $branch->invoice_prefix ?: 'INV-';
            $this->booking_prefix = $branch->booking_prefix ?: 'BK-';
            $this->branch_manager = $branch->branch_manager ?: '';
        }
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'address' => 'required|string|min:5|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
            'status' => 'required|in:active,inactive',
            'fbr_pos_id' => 'nullable|string|max:100',
            'fbr_pos_key' => 'nullable|string|max:255',
            'fbr_sandbox_mode' => 'boolean',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'invoice_prefix' => 'nullable|string|max:20',
            'booking_prefix' => 'nullable|string|max:20',
        ];

        if (auth()->user()->isSuperAdmin()) {
            $rules['marquee_id'] = 'required|exists:marquees,id';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'phone.regex' => 'The phone number must be a valid Pakistani number starting with 03 (e.g. 0321-8611353).',
            'custom_branch_manager.required_if' => 'Please enter the custom branch manager name.',
        ];
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        if (!auth()->user()->isSuperAdmin()) {
            $this->marquee_id = auth()->user()->marquee_id;
        }

        $validatedData = $this->validate();

        $validatedData['phone'] = $this->formatPhoneNumber($this->phone);

        if ($this->isEditMode) {
            $branch = Branch::findOrFail($this->branchId);

            // Tenant security check
            if (!auth()->user()->isSuperAdmin() && $branch->marquee_id !== auth()->user()->marquee_id) {
                abort(403, 'Unauthorized operation.');
            }

            $branch->update($validatedData);
            session()->flash('success', 'Branch updated successfully.');
        } else {
            // Check plan limits
            $owner = null;
            if (auth()->user()->isSuperAdmin()) {
                $marquee = Marquee::find($this->marquee_id);
                $owner = $marquee ? $marquee->owners()->first() : null;
            } else {
                $marquee = auth()->user()->marquee;
                $owner = $marquee ? $marquee->owners()->first() : null;
            }

            if ($owner && !$owner->canCreateBranch()) {
                $this->addError('name', 'This tenant has reached the maximum number of branches allowed by their subscription plan.');
                return;
            }

            $branch = Branch::create($validatedData);

            if ($this->create_initial_hall) {
                $hallCode = $this->initial_hall_code ?: 'HALL-01';
                $hallCodeExists = \App\Models\Hall::where('branch_id', $branch->id)->where('hall_code', $hallCode)->exists();
                if ($hallCodeExists) {
                    $hallCode = 'HALL-' . strtoupper(substr(uniqid(), -4));
                }

                \App\Models\Hall::create([
                    'marquee_id' => $branch->marquee_id,
                    'branch_id' => $branch->id,
                    'hall_name' => $this->initial_hall_name ?: ($branch->name . ' - Main Hall'),
                    'hall_code' => $hallCode,
                    'capacity' => is_numeric($this->initial_hall_capacity) && (int)$this->initial_hall_capacity > 0 ? (int)$this->initial_hall_capacity : 500,
                    'hall_type' => $this->initial_hall_type ?: 'Banquet Hall',
                    'default_booking_price' => is_numeric($this->initial_hall_price) && (float)$this->initial_hall_price >= 0 ? (float)$this->initial_hall_price : 50000,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);
            }

            session()->flash('success', 'Branch created and initial venue hall configured successfully.');
        }

        return redirect()->route('branches.index');
    }

    public function render()
    {
        $managerRoles = \App\Models\Role::whereIn('name', ['super_admin', 'owner', 'business_owner'])->pluck('id');
        $possibleManagers = \App\Models\User::whereIn('role_id', $managerRoles)->orderBy('name')->get();

        return view('livewire.branch-form', compact('possibleManagers'));
    }
}
