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

    // Lists
    public $marquees = [];
    public $cities = [];

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
            $this->phone = $branch->phone;
            $this->status = $branch->status;
            $this->fbr_pos_id = $branch->fbr_pos_id;
            $this->fbr_pos_key = $branch->fbr_pos_key;
            $this->fbr_sandbox_mode = (bool)$branch->fbr_sandbox_mode;
        }
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
            'fbr_pos_id' => 'nullable|string|max:100',
            'fbr_pos_key' => 'nullable|string|max:255',
            'fbr_sandbox_mode' => 'boolean',
        ];

        if (auth()->user()->isSuperAdmin()) {
            $rules['marquee_id'] = 'required|exists:marquees,id';
        }

        return $rules;
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_settings'), 403);

        if (!auth()->user()->isSuperAdmin()) {
            $this->marquee_id = auth()->user()->marquee_id;
        }

        $validatedData = $this->validate();

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

            Branch::create($validatedData);
            session()->flash('success', 'Branch created successfully.');
        }

        return redirect()->route('branches.index');
    }

    public function render()
    {
        return view('livewire.branch-form');
    }
}
