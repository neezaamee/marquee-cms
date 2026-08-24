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
    public $subscription_trial_ends_at = '';

    // UI Presets and password visibility
    public $showPassword = false;
    public $sub_ends_preset = '';
    public $trial_ends_preset = '';

    // Lists
    public $plans = [];

    public function updatedEmail($value)
    {
        if (!$this->isEditMode && empty($this->username)) {
            $parts = explode('@', $value);
            $username = $parts[0] ?? '';
            $username = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $username);
            $this->username = strtolower($username);
        }
    }

    public function generatePassword()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
        $password = '';
        $password .= 'abcdefghijklmnopqrstuvwxyz'[rand(0, 25)];
        $password .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[rand(0, 25)];
        $password .= '0123456789'[rand(0, 9)];
        $password .= '!@#$%^&*()-_=+'[rand(0, 13)];
        for ($i = 0; $i < 8; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        $this->password = str_shuffle($password);
        $this->showPassword = true;
    }

    public function updatedSubEndsPreset($value)
    {
        if (empty($value)) return;
        
        if ($value === 'permanent') {
            $this->subscription_ends_at = '2099-12-31';
        } else {
            $date = match($value) {
                '1_month' => now()->addMonth(),
                '3_months' => now()->addMonths(3),
                '6_months' => now()->addMonths(6),
                '1_year' => now()->addYear(),
                default => null
            };
            $this->subscription_ends_at = $date ? $date->format('Y-m-d') : '';
        }
    }

    public function updatedTrialEndsPreset($value)
    {
        if (empty($value)) return;
        
        $date = match($value) {
            '1_day' => now()->addDay(),
            '14_days' => now()->addDays(14),
            '1_month' => now()->addMonth(),
            default => null
        };
        $this->subscription_trial_ends_at = $date ? $date->format('Y-m-d') : '';
    }

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
            $this->phone = $this->formatPhoneForUi($user->phone);
            $this->status = $user->status;
            $this->subscription_plan_id = $user->subscription_plan_id;
            $this->subscription_ends_at = $user->subscription_ends_at ? $user->subscription_ends_at->format('Y-m-d') : '';
            $this->subscription_trial_ends_at = $user->subscription_trial_ends_at ? $user->subscription_trial_ends_at->format('Y-m-d') : '';
        }
    }

    public function formatPhoneNumber($phone)
    {
        // Strip out all non-numeric characters except maybe leading '+'
        $clean = preg_replace('/[^0-9+]/', '', $phone);
        
        // If it starts with '+', replace with '00'
        if (str_starts_with($clean, '+')) {
            $clean = '00' . substr($clean, 1);
        }
        
        // If it starts with '03' and has 11 digits (e.g. 03218611353)
        if (preg_match('/^03\d{9}$/', $clean)) {
            $clean = '0092' . substr($clean, 1);
        }
        // If it starts with '3' and has 10 digits (e.g. 3218611353)
        elseif (preg_match('/^3\d{9}$/', $clean)) {
            $clean = '0092' . $clean;
        }
        // If it starts with '923' and has 12 digits (e.g. 923218611353)
        elseif (preg_match('/^923\d{9}$/', $clean)) {
            $clean = '00' . $clean;
        }
        
        return $clean;
    }

    public function formatPhoneForUi($phone)
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (preg_match('/^0092(3\d{9})$/', $clean, $matches)) {
            $digits = '0' . $matches[1]; // e.g. 03218611353
            return substr($digits, 0, 4) . '-' . substr($digits, 4);
        }
        return $phone;
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . ($this->userId ?? 'NULL'),
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username,' . ($this->userId ?? 'NULL'), 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'phone' => [
                'required',
                'string',
                'regex:/^00923\d{9}$/',
                'unique:users,phone,' . ($this->userId ?? 'NULL')
            ],
            'status' => 'required|in:active,inactive,suspended',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'subscription_ends_at' => 'nullable|date|after_or_equal:today',
            'subscription_trial_ends_at' => 'nullable|date|after_or_equal:today',
        ];

        if (!$this->isEditMode) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'The phone number must be a valid 11-digit number starting with 03 (e.g. 0321-8611353).',
            'phone.unique' => 'This phone number is already registered.',
        ];
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $data = $this->all();
        if (!empty($this->phone)) {
            $data['phone'] = $this->formatPhoneNumber($this->phone);
        }

        // Run validation on data instead of mutating properties, preventing screen flicker
        $validator = \Illuminate\Support\Facades\Validator::make($data, $this->rules(), $this->messages());
        $validatedData = $validator->validate();

        // Convert empty dates to null
        if (empty($validatedData['subscription_ends_at'])) {
            $validatedData['subscription_ends_at'] = null;
        }
        if (empty($validatedData['subscription_trial_ends_at'])) {
            $validatedData['subscription_trial_ends_at'] = null;
        }

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
