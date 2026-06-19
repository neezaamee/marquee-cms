<?php

namespace App\Livewire;

use App\Models\Marquee;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithFileUploads;

class MarqueeForm extends Component
{
    use WithFileUploads;

    public $isEditMode = false;
    public $marqueeId = null;

    // Fields
    public $name = '';
    public $logo = null; // New uploaded logo
    public $existingLogo = null; // Current logo path
    public $address = '';
    public $city = '';
    public $province = '';
    public $phone = '';
    public $email = '';
    public $ntn = '';
    public $strn = '';
    public $tax_authority = '';
    public $status = 'active';
    public $subscription_plan_id = '';
    public $subscription_ends_at = '';

    // Owner Fields (Creation only)
    public $owner_name = '';
    public $owner_username = '';
    public $owner_email = '';
    public $owner_password = '';
    public $owner_phone = '';

    // Lists
    public $plans = [];

    public function mount($marquee = null)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->plans = SubscriptionPlan::orderBy('name')->get();

        if ($marquee) {
            $this->isEditMode = true;
            $this->marqueeId = $marquee->id;
            $this->name = $marquee->name;
            $this->existingLogo = $marquee->logo;
            $this->address = $marquee->address;
            $this->city = $marquee->city;
            $this->province = $marquee->province;
            $this->phone = $marquee->phone;
            $this->email = $marquee->email;
            $this->ntn = $marquee->ntn;
            $this->strn = $marquee->strn;
            $this->tax_authority = $marquee->tax_authority;
            $this->status = $marquee->status;
            $this->subscription_plan_id = $marquee->subscription_plan_id;
            $this->subscription_ends_at = $marquee->subscription_ends_at ? $marquee->subscription_ends_at->format('Y-m-d') : '';
        }
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048', // 2MB max
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:marquees,email,' . ($this->marqueeId ?? 'NULL'),
            'ntn' => 'nullable|string|max:50',
            'strn' => 'nullable|string|max:50',
            'tax_authority' => 'required|string|max:50',
            'status' => 'required|in:active,inactive,suspended',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'subscription_ends_at' => 'nullable|date',
        ];

        if (!$this->isEditMode) {
            $rules['owner_name'] = 'required|string|max:255';
            $rules['owner_username'] = 'required|string|max:255|unique:users,username';
            $rules['owner_email'] = 'required|email|max:255|unique:users,email';
            $rules['owner_password'] = 'required|string|min:8';
            $rules['owner_phone'] = 'nullable|string|max:50';
        }

        return $rules;
    }

    protected $messages = [
        'owner_email.unique' => 'This email is already registered to a user account.',
        'owner_username.unique' => 'This username is already taken.',
    ];

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validatedData = $this->validate();

        // Handle logo upload
        if ($this->logo) {
            $path = $this->logo->store('logos', 'public');
            $validatedData['logo'] = $path;
        } else {
            // Keep current logo
            $validatedData['logo'] = $this->existingLogo;
        }

        if ($this->isEditMode) {
            $marquee = Marquee::findOrFail($this->marqueeId);
            $marquee->update($validatedData);
            session()->flash('success', 'Marquee updated successfully.');
        } else {
            DB::transaction(function () use ($validatedData) {
                // Separate owner fields from marquee fields
                $marqueeData = $validatedData;
                unset(
                    $marqueeData['owner_name'],
                    $marqueeData['owner_username'],
                    $marqueeData['owner_email'],
                    $marqueeData['owner_password'],
                    $marqueeData['owner_phone']
                );

                // Create Marquee
                $marquee = Marquee::create($marqueeData);

                // Create Default Head Office Branch
                \App\Models\Branch::create([
                    'marquee_id' => $marquee->id,
                    'name' => 'Head Office',
                    'address' => $marquee->address,
                    'city' => $marquee->city,
                    'province' => $marquee->province,
                    'phone' => $marquee->phone,
                    'status' => 'active',
                ]);

                // Resolve the owner role
                $ownerRole = Role::where('name', 'owner')->first();

                // Create the Owner User profile linked to this marquee
                User::create([
                    'name' => $this->owner_name,
                    'email' => $this->owner_email,
                    'username' => $this->owner_username,
                    'password' => Hash::make($this->owner_password),
                    'marquee_id' => $marquee->id,
                    'branch_id' => null,
                    'role_id' => $ownerRole ? $ownerRole->id : null,
                    'phone' => $this->owner_phone ?: null,
                    'status' => 'active',
                ]);
            });

            session()->flash('success', 'Marquee tenant and Owner user account created successfully.');
        }

        return redirect()->route('marquees.index');
    }

    public function render()
    {
        return view('livewire.marquee-form');
    }
}
