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

    // Owner Selection
    public $createOwnerInline = false;
    public $selectedOwners = []; // For selecting existing business owners

    // Owner Fields (Inline creation)
    public $owner_name = '';
    public $owner_username = '';
    public $owner_email = '';
    public $owner_password = '';
    public $owner_phone = '';
    public $subscription_plan_id = '';
    public $subscription_ends_at = '';

    // Lists
    public $plans = [];
    public $businessOwnersList = [];

    public function mount($marquee = null)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->plans = SubscriptionPlan::orderBy('name')->get();
        
        // Fetch all business owners
        $ownerRoleIds = Role::whereIn('name', ['owner', 'business_owner'])->pluck('id');
        $this->businessOwnersList = User::whereIn('role_id', $ownerRoleIds)->orderBy('name')->get();

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
            
            // Get un-linked owners
            $this->selectedOwners = $marquee->owners()->pluck('users.id')->toArray();
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
        ];

        if (!$this->isEditMode) {
            if ($this->createOwnerInline) {
                $rules['owner_name'] = 'required|string|max:255';
                $rules['owner_username'] = 'required|string|max:255|unique:users,username';
                $rules['owner_email'] = 'required|email|max:255|unique:users,email';
                $rules['owner_password'] = 'required|string|min:8';
                $rules['owner_phone'] = 'nullable|string|max:50';
                $rules['subscription_plan_id'] = 'required|exists:subscription_plans,id';
                $rules['subscription_ends_at'] = 'required|date';
            } else {
                $rules['selectedOwners'] = 'required|array|min:1';
            }
        }

        return $rules;
    }

    protected $messages = [
        'owner_email.unique' => 'This email is already registered to a user account.',
        'owner_username.unique' => 'This username is already taken.',
        'selectedOwners.required' => 'Please select at least one Business Owner or create one inline.',
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
            
            // Separate fields
            $marqueeData = $validatedData;
            unset($marqueeData['selectedOwners']);
            
            $marquee->update($marqueeData);
            
            // Sync owners using pivot
            $marquee->owners()->sync($this->selectedOwners);
            
            session()->flash('success', 'Marquee updated successfully.');
        } else {
            DB::transaction(function () use ($validatedData) {
                $marqueeData = $validatedData;
                unset(
                    $marqueeData['owner_name'],
                    $marqueeData['owner_username'],
                    $marqueeData['owner_email'],
                    $marqueeData['owner_password'],
                    $marqueeData['owner_phone'],
                    $marqueeData['subscription_plan_id'],
                    $marqueeData['subscription_ends_at'],
                    $marqueeData['selectedOwners']
                );

                // Create Marquee
                $marquee = Marquee::create($marqueeData);

                // Determine owner ID list to attach
                $ownerIds = [];

                if ($this->createOwnerInline) {
                    // Create inline owner user
                    $ownerRole = Role::where('name', 'business_owner')->first() ?? Role::where('name', 'owner')->first();
                    $newOwner = User::create([
                        'name' => $this->owner_name,
                        'email' => $this->owner_email,
                        'username' => $this->owner_username,
                        'password' => Hash::make($this->owner_password),
                        'marquee_id' => null,
                        'branch_id' => null,
                        'role_id' => $ownerRole ? $ownerRole->id : null,
                        'phone' => $this->owner_phone ?: null,
                        'status' => 'active',
                        'subscription_plan_id' => $this->subscription_plan_id,
                        'subscription_ends_at' => $this->subscription_ends_at,
                    ]);
                    $ownerIds[] = $newOwner->id;
                } else {
                    $ownerIds = $this->selectedOwners;
                }

                // Sync pivot relation
                $marquee->owners()->sync($ownerIds);
            });

            session()->flash('success', 'Marquee tenant created and Owner un-linked successfully.');
        }

        return redirect()->route('marquees.index');
    }

    public function render()
    {
        return view('livewire.marquee-form');
    }
}
