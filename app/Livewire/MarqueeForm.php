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
    public $tax_authority = 'FBR'; // Default to FBR to prevent select error
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
    
    // UI Presets and lists
    public $sub_ends_preset = '';
    public $citiesList = [];
    public $plans = [];
    public $businessOwnersList = [];

    protected $provincesAndCities = [
        'Punjab' => ['Lahore', 'Faisalabad', 'Rawalpindi', 'Multan', 'Gujranwala', 'Sialkot', 'Bahawalpur', 'Sargodha', 'Gujarat', 'Sahiwal', 'Jhelum', 'Sheikhupura', 'Rahim Yar Khan'],
        'Sindh' => ['Karachi', 'Hyderabad', 'Sukkur', 'Larkana', 'Mirpurkhas', 'Nawabshah', 'Jacobabad', 'Shikarpur', 'Thatta'],
        'Khyber Pakhtunkhwa' => ['Peshawar', 'Abbottabad', 'Mardan', 'Swat', 'Kohat', 'Dera Ismail Khan', 'Mansehra', 'Bannu'],
        'Balochistan' => ['Quetta', 'Gwadar', 'Turbat', 'Khuzdar', 'Sibi', 'Hub', 'Chaman'],
        'Islamabad Capital Territory' => ['Islamabad'],
        'Gilgit-Baltistan' => ['Gilgit', 'Skardu', 'Hunza'],
        'Azad Jammu & Kashmir' => ['Muzaffarabad', 'Mirpur', 'Kotli', 'Rawalakot']
    ];

    public function updatedProvince($value)
    {
        $this->citiesList = $this->provincesAndCities[$value] ?? [];
        $this->city = ''; // Reset city selection
    }

    public function updatedOwnerEmail($value)
    {
        if (!$this->isEditMode && empty($this->owner_username)) {
            $parts = explode('@', $value);
            $username = $parts[0] ?? '';
            $username = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $username);
            $this->owner_username = strtolower($username);
        }
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

    public function addOwner($ownerId)
    {
        if (!in_array($ownerId, $this->selectedOwners)) {
            $this->selectedOwners[] = (int)$ownerId;
        }
    }

    public function removeOwner($ownerId)
    {
        $this->selectedOwners = array_values(array_diff($this->selectedOwners, [(int)$ownerId]));
    }

    public function formatPhoneNumber($phone)
    {
        return \App\Services\PhoneNumberService::normalize($phone);
    }

    public function formatPhoneForUi($phone)
    {
        return \App\Services\PhoneNumberService::formatForDisplay($phone);
    }

    public function mount($marquee = null)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->plans = SubscriptionPlan::orderBy('name')->get();
        
        $ownerRoleIds = Role::whereIn('name', ['owner', 'business_owner'])->pluck('id');
        $this->businessOwnersList = User::whereIn('role_id', $ownerRoleIds)->orderBy('name')->get();

        if ($marquee) {
            $this->isEditMode = true;
            $this->marqueeId = $marquee->id;
            $this->name = $marquee->name;
            $this->existingLogo = $marquee->logo;
            $this->address = $marquee->address;
            $this->province = $marquee->province;
            $this->citiesList = $this->provincesAndCities[$this->province] ?? [];
            $this->city = $marquee->city;
            $this->phone = $this->formatPhoneForUi($marquee->phone);
            $this->email = $marquee->email;
            $this->ntn = $marquee->ntn;
            $this->strn = $marquee->strn;
            $this->tax_authority = $marquee->tax_authority;
            $this->status = $marquee->status;
            
            $this->selectedOwners = $marquee->owners()->pluck('users.id')->toArray();
        }
    }

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:4096', // 4MB max for upload, compressed server-side
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'
            ],
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
                $rules['owner_phone'] = [
                    'nullable',
                    'string',
                    'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'
                ];
                $rules['subscription_plan_id'] = 'required|exists:subscription_plans,id';
                $rules['subscription_ends_at'] = 'required|date|after_or_equal:today';
            } else {
                $rules['selectedOwners'] = 'required|array|min:1';
            }
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'owner_email.unique' => 'This email is already registered to a user account.',
            'owner_username.unique' => 'This username is already taken.',
            'selectedOwners.required' => 'Please select at least one Business Owner or create one inline.',
            'phone.regex' => 'The phone number must be a valid number (e.g. 0321-8611353).',
            'owner_phone.regex' => 'The owner phone number must be a valid number (e.g. 0321-8611353).',
        ];
    }

    private function optimizeAndSaveLogo($uploadedFile)
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return $uploadedFile->store('logos', 'public');
        }

        $tempPath = $uploadedFile->getRealPath();
        $info = getimagesize($tempPath);
        if (!$info) {
            return $uploadedFile->store('logos', 'public');
        }

        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = @imagecreatefromjpeg($tempPath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($tempPath);
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($tempPath);
                break;
            case 'image/webp':
                $image = @imagecreatefromwebp($tempPath);
                break;
            default:
                return $uploadedFile->store('logos', 'public');
        }

        if (!$image) {
            return $uploadedFile->store('logos', 'public');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $maxDimension = 500;
        
        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = (int)($height * ($maxDimension / $width));
            } else {
                $newHeight = $maxDimension;
                $newWidth = (int)($width * ($maxDimension / $height));
            }

            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            if ($mime === 'image/png' || $mime === 'image/webp' || $mime === 'image/gif') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $newImage;
        }

        $filename = 'logos/' . uniqid() . '.jpg';
        $storagePath = storage_path('app/public/' . $filename);
        
        if (!file_exists(dirname($storagePath))) {
            mkdir(dirname($storagePath), 0755, true);
        }

        imagejpeg($image, $storagePath, 75);
        imagedestroy($image);

        return $filename;
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $data = $this->all();
        if (!empty($this->phone)) {
            $data['phone'] = $this->formatPhoneNumber($this->phone);
        }
        if ($this->createOwnerInline && !empty($this->owner_phone)) {
            $data['owner_phone'] = $this->formatPhoneNumber($this->owner_phone);
        }

        // Run validation on data instead of mutating properties, preventing screen flicker
        $validator = \Illuminate\Support\Facades\Validator::make($data, $this->rules(), $this->messages());
        $validatedData = $validator->validate();

        // Handle logo upload and optimization
        if ($this->logo) {
            $path = $this->optimizeAndSaveLogo($this->logo);
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
            // Check plan limits
            if ($this->createOwnerInline) {
                $plan = SubscriptionPlan::find($this->subscription_plan_id);
                if ($plan) {
                    $limit = $plan->max_marquees;
                    if ($limit !== -1 && 1 > $limit) {
                        $this->addError('subscription_plan_id', 'The selected subscription plan does not allow registering any marquees.');
                        return;
                    }
                }
            } else {
                foreach ($this->selectedOwners as $ownerId) {
                    $ownerUser = User::find($ownerId);
                    if ($ownerUser && !$ownerUser->canCreateMarquee()) {
                        $this->addError('selectedOwners', "Business Owner '{$ownerUser->name}' has already reached the maximum number of marquees allowed by their subscription plan.");
                        return;
                    }
                }
            }

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
                        'phone' => $validatedData['owner_phone'] ?? null,
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
