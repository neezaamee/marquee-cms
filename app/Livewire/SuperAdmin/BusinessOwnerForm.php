<?php

namespace App\Livewire\SuperAdmin;

use App\Models\User;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class BusinessOwnerForm extends Component
{
    public $isEditMode = false;
    public $userId = null;

    // Fields
    public $name = '';
    public $email = '';
    public $username = '';
    public $password = '';
    public $phone = '';
    public $status = 'active';
    public $subscription_plan_id = '';
    public $subscription_ends_at = '';

    // Lists
    public $plans = [];

    public function mount($id = null)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->plans = SubscriptionPlan::orderBy('name')->get();

        if ($id) {
            $this->isEditMode = true;
            $user = User::findOrFail($id);
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->username = $user->username;
            $this->phone = $user->phone;
            $this->status = $user->status;
            $this->subscription_plan_id = $user->subscription_plan_id;
            $this->subscription_ends_at = $user->subscription_ends_at ? $user->subscription_ends_at->format('Y-m-d') : '';
        }
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . ($this->userId ?? 'NULL'),
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username,' . ($this->userId ?? 'NULL'), 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'phone' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,suspended',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'subscription_ends_at' => 'required|date',
        ];

        if (!$this->isEditMode) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        return $rules;
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validatedData = $this->validate();

        // Handle password hashing/updating
        if (empty($validatedData['password'])) {
            unset($validatedData['password']);
        } else {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        if ($this->isEditMode) {
            $userModel = User::findOrFail($this->userId);
            $userModel->update($validatedData);
            session()->flash('success', 'Business Owner updated successfully.');
        } else {
            // Find appropriate role
            $role = Role::where('name', 'business_owner')->first() ?? Role::where('name', 'owner')->first();
            
            $validatedData['role_id'] = $role ? $role->id : null;
            $validatedData['marquee_id'] = null;
            $validatedData['branch_id'] = null;
            
            User::create($validatedData);
            session()->flash('success', 'Business Owner account created successfully.');
        }

        return redirect()->route('super-admin.business-owners');
    }

    public function render()
    {
        return view('livewire.super-admin.business-owner-form')
            ->layout('layouts.admin');
    }
}
