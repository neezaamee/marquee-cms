<?php

namespace App\Livewire;

use App\Models\Package;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PackageForm extends Component
{
    public $isEditMode = false;
    public $packageId = null;

    // Fields
    public $package_name = '';
    public $package_code = '';
    public $description = '';
    public $package_type = 'Custom'; // Silver, Gold, Platinum, VIP, Custom
    public $minimum_guests = 0;
    public $maximum_guests = '';
    public $base_price = '';
    public $per_plate_price = '';
    public $seasonal_package = false;
    public $season_start_date = '';
    public $season_end_date = '';
    public $status = 'Draft'; // Draft, Active, Inactive, Archived

    public function mount($package = null)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission($package ? 'edit_packages' : 'create_packages'), 403);

        if ($package) {
            $this->isEditMode = true;
            $this->packageId = $package->id;
            $this->package_name = $package->package_name;
            $this->package_code = $package->package_code;
            $this->description = $package->description ?? '';
            $this->package_type = $package->package_type;
            $this->minimum_guests = $package->minimum_guests;
            $this->maximum_guests = $package->maximum_guests ?? '';
            $this->base_price = $package->base_price ?? '';
            $this->per_plate_price = $package->per_plate_price;
            $this->seasonal_package = $package->seasonal_package;
            $this->season_start_date = $package->season_start_date ? $package->season_start_date->format('Y-m-d') : '';
            $this->season_end_date = $package->season_end_date ? $package->season_end_date->format('Y-m-d') : '';
            $this->status = $package->status;
        }
    }

    protected function rules()
    {
        $marqueeId = auth()->user()->marquee_id;

        return [
            'package_name' => 'required|string|max:255',
            'package_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('packages', 'package_code')
                    ->ignore($this->packageId)
                    ->where('marquee_id', $marqueeId)
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'package_type' => 'required|in:Silver,Gold,Platinum,VIP,Custom',
            'minimum_guests' => 'required|integer|min:0',
            'maximum_guests' => 'nullable|integer|gt:minimum_guests',
            'base_price' => 'nullable|numeric|min:0',
            'per_plate_price' => 'required|numeric|min:0',
            'seasonal_package' => 'boolean',
            'season_start_date' => 'required_if:seasonal_package,true|nullable|date',
            'season_end_date' => 'required_if:seasonal_package,true|nullable|date|after_or_equal:season_start_date',
            'status' => 'required|in:Draft,Active,Inactive,Archived',
        ];
    }

    protected $messages = [
        'package_code.unique' => 'This package code is already registered in your Marquee database.',
        'season_start_date.required_if' => 'Start date is required for seasonal packages.',
        'season_end_date.required_if' => 'End date is required for seasonal packages.',
        'season_end_date.after_or_equal' => 'End date must be on or after the start date.',
        'maximum_guests.gt' => 'Maximum guests must be greater than minimum guests.',
    ];

    /**
     * Save package.
     */
    public function save()
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission($this->isEditMode ? 'edit_packages' : 'create_packages'), 403);

        $validatedData = $this->validate();

        $packageData = [
            'marquee_id' => auth()->user()->marquee_id,
            'package_name' => $this->package_name,
            'package_code' => $this->package_code,
            'description' => $this->description,
            'package_type' => $this->package_type,
            'minimum_guests' => $this->minimum_guests ?: 0,
            'maximum_guests' => $this->maximum_guests ?: null,
            'base_price' => $this->base_price ?: null,
            'per_plate_price' => $this->per_plate_price,
            'seasonal_package' => $this->seasonal_package,
            'season_start_date' => $this->seasonal_package ? $this->season_start_date : null,
            'season_end_date' => $this->seasonal_package ? $this->season_end_date : null,
            'status' => $this->status,
        ];

        if ($this->isEditMode) {
            $package = Package::findOrFail($this->packageId);

            if (!auth()->user()->isSuperAdmin() && $package->marquee_id !== auth()->user()->marquee_id) {
                abort(403, 'Unauthorized.');
            }

            $package->update($packageData);
            session()->flash('success', 'Package details updated successfully.');
        } else {
            $package = Package::create($packageData);
            session()->flash('success', 'Package created successfully. Add menu items in the builder below.');
            return redirect()->route('packages.builder', $package->id);
        }

        return redirect()->route('packages.index');
    }

    public function render()
    {
        return view('livewire.package-form');
    }
}
