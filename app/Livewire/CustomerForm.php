<?php

namespace App\Livewire;

use App\Models\Customer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CustomerForm extends Component
{
    use WithFileUploads;

    public $isEditMode = false;
    public $customerId = null;

    // Fields
    public $customer_type = 'Individual'; // Individual / Corporate
    public $first_name = '';
    public $last_name = '';
    public $company_name = '';
    public $gender = '';
    public $date_of_birth = '';
    public $cnic_national_id = '';
    public $ntn_number = '';
    public $email = '';
    public $phone_number = '';
    public $alternate_phone = '';
    public $address = '';
    public $city = '';
    public $province = '';
    public $postal_code = '';
    public $photo = null; // profile photo uploaded
    public $existingPhoto = null;
    public $notes = '';
    public $status = 'Active';

    // Referral fields
    public $referred_by_type = 'Walk-In';
    public $referred_by_name = '';
    public $referred_by_contact = '';

    // Dropdown/Option arrays
    public $referralTypes = [
        'Walk-In',
        'Social Media',
        'Facebook',
        'Google',
        'Website',
        'Wedding Planner',
        'Event Manager',
        'Staff Member',
        'Existing Customer',
        'Other'
    ];

    public function mount($customer = null)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('create_bookings'), 403);

        if ($customer) {
            $this->isEditMode = true;
            $this->customerId = $customer->id;
            $this->customer_type = $customer->customer_type;
            $this->first_name = $customer->first_name;
            $this->last_name = $customer->last_name;
            $this->company_name = $customer->company_name ?? '';
            $this->gender = $customer->gender ?? '';
            $this->date_of_birth = $customer->date_of_birth ? date('Y-m-d', strtotime($customer->date_of_birth)) : '';
            $this->cnic_national_id = $customer->cnic_national_id ?? '';
            $this->ntn_number = $customer->ntn_number ?? '';
            $this->email = $customer->email ?? '';
            $this->phone_number = $customer->phone_number;
            $this->alternate_phone = $customer->alternate_phone ?? '';
            $this->address = $customer->address ?? '';
            $this->city = $customer->city ?? '';
            $this->province = $customer->province ?? '';
            $this->postal_code = $customer->postal_code ?? '';
            $this->existingPhoto = $customer->profile_photo;
            $this->notes = $customer->notes ?? '';
            $this->status = $customer->status;
            $this->referred_by_type = $customer->referred_by_type ?? 'Walk-In';
            $this->referred_by_name = $customer->referred_by_name ?? '';
            $this->referred_by_contact = $customer->referred_by_contact ?? '';
        }
    }

    protected function rules()
    {
        $user = auth()->user();
        $marqueeId = $user->marquee_id;

        return [
            'customer_type' => 'required|in:Individual,Corporate',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'nullable|required_if:customer_type,Corporate|string|max:255',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'cnic_national_id' => [
                'nullable',
                'regex:/^\d{5}-\d{7}-\d{1}$/',
                Rule::unique('customers', 'cnic_national_id')
                    ->ignore($this->customerId)
                    ->where('marquee_id', $marqueeId)
                    ->whereNull('deleted_at'),
            ],
            'ntn_number' => 'nullable|string|max:100',
            'email' => [
                'nullable',
                'email',
                Rule::unique('customers', 'email')
                    ->ignore($this->customerId)
                    ->where('marquee_id', $marqueeId)
                    ->whereNull('deleted_at'),
            ],
            'phone_number' => ['required', 'string', 'regex:/^((\+92)|(0092)|0)?3\d{9}$/'],
            'alternate_phone' => ['nullable', 'string', 'regex:/^((\+92)|(0092)|0)?3\d{9}$/'],
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'status' => 'required|in:Active,Inactive,Blocked',
            'referred_by_type' => 'nullable|string|max:100',
            'referred_by_name' => 'nullable|string|max:255',
            'referred_by_contact' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048', // Max 2MB profile picture
        ];
    }

    protected $messages = [
        'cnic_national_id.regex' => 'The CNIC format must be XXXXX-XXXXXXX-X.',
        'cnic_national_id.unique' => 'This CNIC is already registered in your Marquee database.',
        'email.unique' => 'This email is already registered in your Marquee database.',
        'phone_number.regex' => 'Invalid Pakistan phone number format (e.g. 03001234567 or +923001234567).',
        'alternate_phone.regex' => 'Invalid Pakistan alternate phone number format.',
        'company_name.required_if' => 'The company name field is required for Corporate customers.',
    ];

    public function save()
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('create_bookings'), 403);

        $validatedData = $this->validate();

        // Handle photo upload
        $photoPath = $this->existingPhoto;
        if ($this->photo) {
            if ($this->existingPhoto) {
                Storage::disk('public')->delete($this->existingPhoto);
            }
            $photoPath = $this->photo->store('customers/photos', 'public');
        }

        $customerData = [
            'marquee_id' => auth()->user()->marquee_id,
            'customer_type' => $this->customer_type,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company_name' => $this->customer_type === 'Corporate' ? $this->company_name : null,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth ? date('Y-m-d', strtotime($this->date_of_birth)) : null,
            'cnic_national_id' => $this->cnic_national_id,
            'ntn_number' => $this->ntn_number,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'alternate_phone' => $this->alternate_phone,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'profile_photo' => $photoPath,
            'notes' => $this->notes,
            'status' => $this->status,
            'referred_by_type' => $this->referred_by_type,
            'referred_by_name' => $this->referred_by_name,
            'referred_by_contact' => $this->referred_by_contact,
        ];

        if ($this->isEditMode) {
            $customer = Customer::findOrFail($this->customerId);

            // Scope check
            if (!auth()->user()->isSuperAdmin() && $customer->marquee_id !== auth()->user()->marquee_id) {
                abort(403, 'Unauthorized.');
            }

            $customer->update($customerData);
            session()->flash('success', 'Customer updated successfully.');
        } else {
            Customer::create($customerData);
            session()->flash('success', 'Customer added successfully.');
        }

        return redirect()->route('customers.index');
    }

    public function render()
    {
        return view('livewire.customer-form');
    }
}
