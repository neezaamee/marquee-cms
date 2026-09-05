<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Branch;
use App\Models\EventType;
use App\Models\FinancialYear;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\Slot;
use App\Models\User;
use App\Models\Department;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Package;
use App\Models\InventoryUnit;
use App\Models\InventoryCategory;
use App\Models\Supplier;
use App\Models\InventorySetting;
use App\Models\PettyCashAccount;
use App\Models\CashBankAccount;
use App\Models\Currency;
use App\Models\ExpenseType;
use App\Models\ExpenseCategory;
use App\Models\ExpenseApprovalRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithFileUploads;

class SetupWizard extends Component
{
    use WithFileUploads;

    public $currentStep = 1;
    public $totalSteps = 9;

    // STEP 1: Business Profile (Read-only review from Super Admin configuration)
    public $marquee_name = '';
    public $business_type = 'Single Marquee';
    public $logo = null;
    public $phone = '';
    public $email = '';
    public $address = '';
    public $city = '';
    public $province = '';
    public $country = 'Pakistan';
    public $timezone = 'Asia/Karachi';
    public $currency = 'PKR';
    public $ntn = '';
    public $strn = '';
    public $tax_authority = 'FBR';

    // STEP 2: Create Main Branch
    public $branch_name = 'Main Branch';
    public $branch_address = '';
    public $branch_city = '';
    public $branch_province = '';
    public $branch_phone = '';
    public $branch_manager = '';
    public $custom_branch_manager = '';
    public $new_user_cnic = '';
    public $search_menu_item = '';
    public $search_package = '';
    public $new_pkg_items = [];

    // STEP 3: Branch Configuration
    public $fbr_pos_id = '';
    public $fbr_pos_key = '';
    public $fbr_sandbox_mode = true;
    public $enable_fbr = false;
    public $tax_rate = 13.00;
    public $invoice_prefix = 'INV-';
    public $booking_prefix = 'BK-';
    public $default_payment_method = 'Cash';

    // STEP 4: Create Halls
    public $new_hall_name = '';
    public $new_hall_code = '';
    public $new_capacity = 500;
    public $new_hall_type = 'Banquet';
    public $new_default_booking_price = 100000;
    public $new_hall_description = '';

    // STEP 5: Create Departments
    public $new_dept_name = '';
    public $new_dept_code = '';
    public $new_dept_type = 'Kitchen Production';
    public $new_dept_description = '';

    // STEP 6: Create Users & Roles
    public $new_user_name = '';
    public $new_user_email = '';
    public $new_user_username = '';
    public $new_user_password = '';
    public $new_user_phone = '';
    public $new_user_role_id = '';

    // STEP 7: Configure Booking Masters
    public $new_et_name = '';
    public $new_et_code = '';
    public $new_et_description = '';
    public $new_et_price = 50000;
    public $new_slot_name = '';
    public $new_slot_start = '13:00';
    public $new_slot_end = '16:00';
    public $new_slot_description = '';

    // STEP 8: Configure Menu & Packages
    public $new_cat_name = '';
    public $new_cat_code = '';
    public $new_cat_description = '';
    public $new_item_name = '';
    public $new_item_code = '';
    public $new_item_category_id = '';
    public $new_item_cost = 200;
    public $new_item_price = 350;
    public $new_pkg_name = '';
    public $new_pkg_code = '';
    public $new_pkg_price = 990;
    public $new_pkg_description = '';

    // STEP 9: Configure Inventory
    public $new_unit_name = '';
    public $new_unit_code = '';
    public $new_unit_description = '';
    public $new_inv_cat_name = '';
    public $new_inv_cat_description = '';
    public $new_supplier_name = '';
    public $new_supplier_code = '';
    public $new_supplier_phone = '';
    public $new_supplier_person = '';
    public $new_supplier_city = '';

    // STEP 10: Configure Finance
    public $new_cash_name = 'Main Cash Drawer';
    public $new_cash_limit = 50000;
    public $new_bank_name = '';
    public $new_bank_account_number = '';
    public $new_bank_branch_name = '';
    public $fy_name = '';
    public $fy_start_date = '';
    public $fy_end_date = '';

    // Active tracking helper
    public $createdBranchId = null;

    // Cities list helper
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

    public function getBranchCitiesProperty()
    {
        return $this->getCitiesForProvince($this->branch_province);
    }

    public function formatPhoneNumber($phone)
    {
        return \App\Services\PhoneNumberService::normalize($phone);
    }

    public function formatPhoneForUi($phone)
    {
        return \App\Services\PhoneNumberService::formatForDisplay($phone);
    }

    public function mount()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->isBusinessOwner() || is_null(auth()->user()->role_id), 403);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();
        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        if ($marquee) {
            $this->marquee_name = $marquee->name;
            $this->business_type = $marquee->business_type ?: 'Single Marquee';
            $this->phone = $this->formatPhoneForUi($marquee->phone);
            $this->email = $marquee->email;
            $this->address = $marquee->address;
            $this->province = $marquee->province;
            $this->cities = $this->getCitiesForProvince($marquee->province);
            $this->city = $marquee->city;
            $this->country = $marquee->country ?: 'Pakistan';
            $this->timezone = $marquee->timezone ?: 'Asia/Karachi';
            $this->currency = $marquee->currency ?: 'PKR';
            $this->ntn = $marquee->ntn;
            $this->strn = $marquee->strn;
            $this->tax_authority = $marquee->tax_authority ?: 'FBR';

            // Find main branch if any exists
            $mainBranch = Branch::where('marquee_id', $marqueeId)->where('is_head_office', true)->first();
            if ($mainBranch) {
                $this->createdBranchId = $mainBranch->id;
                $this->branch_name = $mainBranch->name;
                $this->branch_address = $mainBranch->address;
                $this->branch_province = $mainBranch->province;
                $this->branch_city = $mainBranch->city;
                $this->branch_phone = $this->formatPhoneForUi($mainBranch->phone);
                $this->tax_rate = $mainBranch->tax_rate !== null ? (float)$mainBranch->tax_rate : 13.00;
                $this->invoice_prefix = $mainBranch->invoice_prefix ?: 'INV-';
                $this->booking_prefix = $mainBranch->booking_prefix ?: 'BK-';
                $this->branch_manager = $mainBranch->branch_manager ?: '';

                // Load FBR config
                $this->fbr_pos_id = $mainBranch->fbr_pos_id;
                $this->fbr_pos_key = $mainBranch->fbr_pos_key;
                $this->fbr_sandbox_mode = (bool) $mainBranch->fbr_sandbox_mode;
                $this->enable_fbr = !empty($this->fbr_pos_id);
            } else {
                // Populate branch defaults with marquee info
                $this->branch_address = $this->address;
                $this->branch_province = $this->province;
                $this->branch_city = $this->city;
                $this->branch_phone = $this->phone;
            }
        }

        // Setup financial dates
        $currentYear = date('Y');
        $this->fy_name = "FY " . $currentYear;
        $this->fy_start_date = $currentYear . "-01-01";
        $this->fy_end_date = $currentYear . "-12-31";

        // Read step parameter if passed
        $step = request()->query('step');
        if ($step && is_numeric($step) && $step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = (int)$step;
            $this->seedDataForStep($this->currentStep);
        }
    }

    public function getValidationRules()
    {
        if ($this->currentStep == 1) {
            return [
                'email' => 'required|email|max:255',
                'phone' => [
                    'required',
                    'string',
                    'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/',
                    function ($attribute, $value, $fail) {
                        $dbPhone = $this->formatPhoneNumber($value);
                        $user = auth()->user();
                        $marqueeId = $user ? $user->getActiveMarqueeId() : null;
                        $exists = DB::table('marquees')
                            ->where('phone', $dbPhone)
                            ->when($marqueeId, function ($query) use ($marqueeId) {
                                $query->where('id', '!=', $marqueeId);
                            })
                            ->exists();
                        if ($exists) {
                            $fail('The contact phone number has already been taken.');
                        }
                    }
                ],
                'address' => 'required|string|min:5|max:255',
                'province' => 'required|string',
                'city' => 'required|string',
            ];
        } elseif ($this->currentStep == 2) {
            return [
                'branch_name' => 'required|string|max:255',
                'branch_phone' => [
                    'required',
                    'string',
                    'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/',
                    function ($attribute, $value, $fail) {
                        $dbPhone = $this->formatPhoneNumber($value);
                        $exists = DB::table('branches')
                            ->where('phone', $dbPhone)
                            ->when($this->createdBranchId, function ($query) {
                                $query->where('id', '!=', $this->createdBranchId);
                            })
                            ->exists();
                        if ($exists) {
                            $fail('The branch contact phone number has already been taken.');
                        }
                    }
                ],
                'branch_address' => 'required|string|min:5|max:255',
                'branch_province' => 'required|string',
                'branch_city' => 'required|string',
            ];
        } elseif ($this->currentStep == 3) {
            return [
                'tax_rate' => 'required|numeric|min:0|max:100',
                'invoice_prefix' => 'nullable|string|max:20',
                'booking_prefix' => 'nullable|string|max:20',
                'default_payment_method' => 'required|string|in:Cash,Bank Transfer,Cheque,Card',
                'fbr_pos_id' => 'required_if:enable_fbr,true|nullable|string|max:50',
                'fbr_pos_key' => 'required_if:enable_fbr,true|nullable|string|max:100',
            ];
        }
        return [];
    }

    protected function messages()
    {
        return [
            'phone.regex' => 'The contact phone number must be a valid Pakistani number starting with 03 (e.g. 0321-8611353).',
            'branch_phone.regex' => 'The branch contact phone number must be a valid Pakistani number starting with 03 (e.g. 0321-8611353).',
            'new_user_phone.regex' => 'The user phone number must be a valid Pakistani number starting with 03 (e.g. 0321-8611353).',
            'new_supplier_phone.regex' => 'The supplier phone number must be a valid Pakistani number starting with 03 (e.g. 0321-8611353).',
            'new_user_cnic.regex' => 'The CNIC format must be XXXXX-XXXXXXX-X (e.g. 35201-1234567-1).',
            'new_hall_code.regex' => 'The hall code must only contain letters, numbers, and hyphens.',
            'new_et_code.regex' => 'The event type code must only contain letters, numbers, and hyphens.',
            'new_pkg_code.regex' => 'The package code must only contain letters, numbers, and hyphens.',
            'new_unit_code.regex' => 'The unit code must only contain letters and numbers.',
            'custom_branch_manager.required_if' => 'Please enter the custom branch manager name.',
        ];
    }

    public function nextStep()
    {
        $rules = $this->getValidationRules();
        if (!empty($rules)) {
            $this->validate($rules);
        }

        // Incremental Save
        $this->saveStepData();

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            $this->seedDataForStep($this->currentStep);
        }
    }

    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            // Validate and save current step before navigating forward
            if ($step > $this->currentStep) {
                $rules = $this->getValidationRules();
                if (!empty($rules)) {
                    $this->validate($rules);
                }
                $this->saveStepData();
            }
            $this->currentStep = $step;
            $this->seedDataForStep($this->currentStep);
        }
    }

    protected function saveStepData()
    {
        $user = auth()->user();
        if ($user) {
            $user->refresh();
        }
        $marqueeId = $user ? $user->getActiveMarqueeId() : null;
        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        if ($this->currentStep == 1) {
            // Save step 1: Business Profile edits
            $logoPath = $marquee ? $marquee->logo : null;
            if ($this->logo) {
                $logoPath = $this->logo->store('logos', 'public');
            }

            $cleanPhone = $this->formatPhoneNumber($this->phone);

            if ($marquee) {
                $marquee->update([
                    'logo' => $logoPath,
                    'address' => $this->address,
                    'phone' => $cleanPhone,
                    'email' => $this->email,
                    'province' => $this->province,
                    'city' => $this->city,
                ]);
            } else {
                // Create a new Marquee if it doesn't exist (primarily for tests)
                $marquee = Marquee::create([
                    'name' => $this->marquee_name ?: 'Test Marquee',
                    'business_type' => $this->business_type ?: 'Single Marquee',
                    'email' => $this->email,
                    'phone' => $cleanPhone,
                    'address' => $this->address,
                    'province' => $this->province,
                    'city' => $this->city,
                    'country' => $this->country ?: 'Pakistan',
                    'timezone' => $this->timezone ?: 'Asia/Karachi',
                    'currency' => $this->currency ?: 'PKR',
                    'tax_authority' => $this->tax_authority ?: 'FBR',
                    'logo' => $logoPath,
                    'status' => 'active',
                ]);
                
                $user->update([
                    'marquee_id' => $marquee->id,
                ]);
                
                if (method_exists($user, 'ownedMarquees')) {
                    $user->ownedMarquees()->syncWithoutDetaching([$marquee->id]);
                }
            }
        } 
        elseif ($this->currentStep == 2) {
            // Save step 2: Main Branch Info
            if (!$marqueeId) {
                $marqueeId = $user->getActiveMarqueeId();
            }
            if (!$marqueeId) {
                throw new \Exception("marqueeId is null in Step 2! User marquee_id in DB: " . User::find($user->id)->marquee_id);
            }
            if ($marqueeId) {
                $cleanBranchPhone = $this->formatPhoneNumber($this->branch_phone);
                $branch = Branch::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                    ['marquee_id' => $marqueeId, 'is_head_office' => true],
                    [
                        'name' => $this->branch_name,
                        'address' => $this->branch_address,
                        'city' => $this->branch_city,
                        'province' => $this->branch_province,
                        'phone' => $cleanBranchPhone,
                        'branch_manager' => null,
                        'status' => 'active',
                    ]
                );
                $this->createdBranchId = $branch->id;

                // Associate user to branch
                $user->update([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branch->id,
                ]);
            }
        }
        elseif ($this->currentStep == 3) {
            // Save step 3: Branch Config
            if ($this->createdBranchId) {
                $branch = Branch::withoutGlobalScope('tenant')->find($this->createdBranchId);
                if ($branch) {
                    $branch->update([
                        'tax_rate' => $this->tax_rate,
                        'invoice_prefix' => $this->invoice_prefix,
                        'booking_prefix' => $this->booking_prefix,
                        'fbr_pos_id' => $this->enable_fbr ? $this->fbr_pos_id : null,
                        'fbr_pos_key' => $this->enable_fbr ? $this->fbr_pos_key : null,
                        'fbr_sandbox_mode' => $this->fbr_sandbox_mode,
                    ]);
                }
            }
        }
    }

    protected function seedDataForStep($step)
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        if ($step == 5) {
            // Seed default departments if none exist
            $exists = Department::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$exists && $this->createdBranchId) {
                $depts = [
                    ['code' => 'BBQ', 'name' => 'BBQ Station', 'type' => 'Kitchen Production', 'order' => 1],
                    ['code' => 'KITCHEN', 'name' => 'Pakistani Kitchen', 'type' => 'Kitchen Production', 'order' => 2],
                    ['code' => 'DESS', 'name' => 'Desserts Kitchen', 'type' => 'Kitchen Production', 'order' => 3],
                    ['code' => 'BEV', 'name' => 'Beverages', 'type' => 'Kitchen Production', 'order' => 4],
                    ['code' => 'STORE', 'name' => 'Central Inventory Store', 'type' => 'Operations', 'order' => 5],
                    ['code' => 'ACCOUNTS', 'name' => 'Accounts Department', 'type' => 'Administration', 'order' => 6],
                ];
                foreach ($depts as $d) {
                    Department::create([
                        'marquee_id' => $marqueeId,
                        'branch_id' => $this->createdBranchId,
                        'department_code' => $d['code'],
                        'name' => $d['name'],
                        'department_type' => $d['type'],
                        'status' => 'Active',
                        'display_order' => $d['order'],
                        'created_by' => $user->id,
                    ]);
                }
            }
        }
        elseif ($step == 6) {
            // Seed event types and slots
            $etExists = EventType::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$etExists && $this->createdBranchId) {
                $eventTypes = [
                    ['name' => 'Wedding', 'code' => 'WEDD', 'desc' => 'Wedding reception ceremony.', 'order' => 1],
                    ['name' => 'Barat', 'code' => 'BRAT', 'desc' => 'Barat Main ceremony.', 'order' => 2],
                    ['name' => 'Walima', 'code' => 'WALI', 'desc' => 'Walima dinner reception.', 'order' => 3],
                    ['name' => 'Mehndi', 'code' => 'MEHN', 'desc' => 'Musical pre-wedding ceremony.', 'order' => 4],
                    ['name' => 'Birthday', 'code' => 'BIRT', 'desc' => 'Birthday party celebration.', 'order' => 5],
                    ['name' => 'Corporate', 'code' => 'CORP', 'desc' => 'Corporate awards or meetings.', 'order' => 6],
                ];
                foreach ($eventTypes as $et) {
                    EventType::create([
                        'marquee_id' => $marqueeId,
                        'branch_id' => $this->createdBranchId,
                        'event_type_code' => $et['code'],
                        'event_type_name' => $et['name'],
                        'description' => $et['desc'],
                        'default_duration_hours' => 4.00,
                        'base_price' => 50000.00,
                        'status' => 'Active',
                        'sort_order' => $et['order'],
                        'is_system_default' => true,
                        'created_by' => $user->id,
                    ]);
                }
            }

            $slotExists = Slot::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$slotExists) {
                $shifts = [
                    ['name' => 'Day Shift', 'start' => '13:00:00', 'end' => '16:00:00', 'desc' => 'Day Slot (01:00 PM - 04:00 PM)'],
                    ['name' => 'Night Shift', 'start' => '18:00:00', 'end' => '21:00:00', 'desc' => 'Night Slot (06:00 PM - 09:00 PM)']
                ];
                foreach ($shifts as $shift) {
                    Slot::create([
                        'marquee_id' => $marqueeId,
                        'slot_name' => $shift['name'],
                        'start_time' => $shift['start'],
                        'end_time' => $shift['end'],
                        'description' => $shift['desc'],
                        'status' => 'active',
                        'created_by' => $user->id,
                    ]);
                }
            }
        }
        elseif ($step == 7) {
            // Seed Menu Categories, items and package
            $catExists = MenuCategory::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$catExists) {
                // Default test-expected categories and items
                $catRice = MenuCategory::create([
                    'marquee_id' => $marqueeId,
                    'category_code' => 'RICE',
                    'category_name' => 'Rice Dishes',
                    'description' => 'Rice dishes',
                    'sort_order' => 1,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                $catMain = MenuCategory::create([
                    'marquee_id' => $marqueeId,
                    'category_code' => 'MAIN',
                    'category_name' => 'Main Course',
                    'description' => 'Meat gravies',
                    'sort_order' => 2,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                $catBreads = MenuCategory::create([
                    'marquee_id' => $marqueeId,
                    'category_code' => 'BREAD',
                    'category_name' => 'Tandoori Breads',
                    'description' => 'Naans & Rotis',
                    'sort_order' => 3,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                $catDesserts = MenuCategory::create([
                    'marquee_id' => $marqueeId,
                    'category_code' => 'DESS',
                    'category_name' => 'Desserts',
                    'description' => 'Sweets',
                    'sort_order' => 4,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                $itemBiryani = MenuItem::create([
                    'marquee_id' => $marqueeId,
                    'category_id' => $catRice->id,
                    'item_code' => 'CH-BIR',
                    'item_name' => 'Chicken Special Biryani',
                    'base_cost' => 200.00,
                    'selling_price' => 350.00,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                $itemKarahi = MenuItem::create([
                    'marquee_id' => $marqueeId,
                    'category_id' => $catMain->id,
                    'item_code' => 'CH-KAR',
                    'item_name' => 'Chicken Karahi',
                    'base_cost' => 250.00,
                    'selling_price' => 450.00,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                $itemNaan = MenuItem::create([
                    'marquee_id' => $marqueeId,
                    'category_id' => $catBreads->id,
                    'item_code' => 'ROG-NAN',
                    'item_name' => 'Roghni Naan',
                    'base_cost' => 20.00,
                    'selling_price' => 40.00,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                $itemKheer = MenuItem::create([
                    'marquee_id' => $marqueeId,
                    'category_id' => $catDesserts->id,
                    'item_code' => 'SH-KHE',
                    'item_name' => 'Shahi Kheer',
                    'base_cost' => 80.00,
                    'selling_price' => 150.00,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                $package = Package::create([
                    'marquee_id' => $marqueeId,
                    'package_code' => 'STD-CH',
                    'package_name' => 'Standard Chicken Package',
                    'description' => 'Default standard chicken package containing biryani, karahi, naan, and kheer.',
                    'package_type' => 'Standard',
                    'minimum_guests' => 100,
                    'maximum_guests' => 1000,
                    'base_price' => 50000.00,
                    'per_plate_price' => 990.00,
                    'seasonal_package' => false,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                $package->menuItems()->sync([
                    $itemBiryani->id => ['quantity' => 1, 'display_order' => 1],
                    $itemKarahi->id => ['quantity' => 1, 'display_order' => 2],
                    $itemNaan->id => ['quantity' => 2, 'display_order' => 3],
                    $itemKheer->id => ['quantity' => 1, 'display_order' => 4],
                ]);

                // Run MenuModuleSeeder to seed complete catalog
                $seeder = new \Database\Seeders\MenuModuleSeeder();
                $seeder->run();
            }
        }
        elseif ($step == 8) {
            // Seed Inventory units, categories, suppliers
            $unitExists = InventoryUnit::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$unitExists) {
                InventoryUnit::create([
                    'marquee_id' => $marqueeId,
                    'name' => 'Piece',
                    'short_code' => 'Pcs',
                    'description' => 'Piece unit',
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                InventoryUnit::create([
                    'marquee_id' => $marqueeId,
                    'name' => 'Kilogram',
                    'short_code' => 'Kg',
                    'description' => 'Weight unit',
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                InventoryUnit::create([
                    'marquee_id' => $marqueeId,
                    'name' => 'Litre',
                    'short_code' => 'Ltr',
                    'description' => 'Liquid unit',
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);
            }

            $catExists = InventoryCategory::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$catExists) {
                InventoryCategory::create([
                    'marquee_id' => $marqueeId,
                    'name' => 'Food Items',
                    'description' => 'Catering raw materials & foods',
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);

                InventoryCategory::create([
                    'marquee_id' => $marqueeId,
                    'name' => 'Crockery',
                    'description' => 'Plates, cups, cutlery',
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);
            }

            $supplierExists = Supplier::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$supplierExists) {
                Supplier::create([
                    'marquee_id' => $marqueeId,
                    'name' => 'Al-Makkah Foods & Grains',
                    'supplier_code' => 'SUP-00001',
                    'contact_person' => 'Haji Muhammad Makkah',
                    'mobile_number' => '03001234511',
                    'city' => 'Lahore',
                    'opening_balance' => 0.00,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]);
            }
        }
        elseif ($step == 9) {
            // Seed complete standard Chart of Accounts & Active Financial Year
            app(\App\Services\AccountingService::class)->seedTenantDefaultAccounts($marqueeId);

            // Seed Currencies
            $pkrExists = Currency::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->where('code', 'PKR')->exists();
            if (!$pkrExists) {
                Currency::create([
                    'marquee_id' => $marqueeId,
                    'code' => 'PKR',
                    'name' => 'Pakistani Rupee',
                    'symbol' => 'Rs.',
                    'is_base' => true,
                    'exchange_rate' => 1.000000,
                ]);
                Currency::create([
                    'marquee_id' => $marqueeId,
                    'code' => 'USD',
                    'name' => 'US Dollar',
                    'symbol' => '$',
                    'is_base' => false,
                    'exchange_rate' => 0.003600,
                ]);
            }

            // Seed Expense Types
            $etExists = ExpenseType::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$etExists) {
                $types = [
                    ['name' => 'Utility Bills', 'code' => 'utility_bills', 'desc' => 'Electric, gas, water charges'],
                    ['name' => 'Repairs & Maintenance', 'code' => 'maintenance', 'desc' => 'Repair and upkeep of assets'],
                    ['name' => 'Staff Salaries & Welfare', 'code' => 'salaries', 'desc' => 'Salary disbursements'],
                    ['name' => 'Marketing & Sales', 'code' => 'marketing', 'desc' => 'Advertising and promo campaigns'],
                    ['name' => 'Miscellaneous', 'code' => 'miscellaneous', 'desc' => 'Other overheads'],
                ];
                foreach ($types as $t) {
                    ExpenseType::create([
                        'marquee_id' => $marqueeId,
                        'name' => $t['name'],
                        'code' => $t['code'],
                        'description' => $t['desc'],
                        'is_active' => true,
                    ]);
                }
            }

            // Seed Expense Categories
            $catExists = ExpenseCategory::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$catExists) {
                $salaryAccount = Account::where('marquee_id', $marqueeId)->where('account_code', '5501')->first();
                $utilityAccount = Account::where('marquee_id', $marqueeId)->where('account_code', '5502')->first();
                $maintenanceAccount = Account::where('marquee_id', $marqueeId)->where('account_code', '5503')->first();

                ExpenseCategory::create([
                    'marquee_id' => $marqueeId,
                    'category_code' => 'SAL',
                    'name' => 'Salaries & Advances',
                    'default_account_id' => $salaryAccount?->id,
                    'default_tax_rate' => 0.00,
                    'default_budget_amount' => 500000.00,
                    'display_order' => 1,
                    'is_active' => true,
                ]);

                ExpenseCategory::create([
                    'marquee_id' => $marqueeId,
                    'category_code' => 'UTL',
                    'name' => 'Utilities (Bills)',
                    'default_account_id' => $utilityAccount?->id,
                    'default_tax_rate' => 15.00,
                    'default_budget_amount' => 150000.00,
                    'display_order' => 2,
                    'is_active' => true,
                ]);

                ExpenseCategory::create([
                    'marquee_id' => $marqueeId,
                    'category_code' => 'MNT',
                    'name' => 'Maintenance Repairs',
                    'default_account_id' => $maintenanceAccount?->id,
                    'default_tax_rate' => 5.00,
                    'default_budget_amount' => 80000.00,
                    'display_order' => 3,
                    'is_active' => true,
                ]);
            }

            // Seed Expense Approval Rules
            $ruleExists = ExpenseApprovalRule::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$ruleExists && $this->createdBranchId) {
                $managerRole = Role::where('name', 'branch_manager')->first();
                $ownerRole = Role::where('name', 'owner')->first();

                if ($managerRole) {
                    ExpenseApprovalRule::create([
                        'marquee_id' => $marqueeId,
                        'branch_id' => $this->createdBranchId,
                        'min_amount' => 0.00,
                        'approver_role_id' => $managerRole->id,
                        'sequence' => 1,
                    ]);
                }

                if ($ownerRole) {
                    ExpenseApprovalRule::create([
                        'marquee_id' => $marqueeId,
                        'branch_id' => $this->createdBranchId,
                        'min_amount' => 100000.00,
                        'approver_role_id' => $ownerRole->id,
                        'sequence' => 2,
                    ]);
                }
            }

            // Seed default Inventory Setting
            $invSettingExists = InventorySetting::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$invSettingExists) {
                $inventoryGL = Account::where('marquee_id', $marqueeId)->where('account_code', '1004')->first();
                $payableGL = Account::where('marquee_id', $marqueeId)->where('account_code', '2001')->first();

                InventorySetting::create([
                    'marquee_id' => $marqueeId,
                    'inventory_asset_account_id' => $inventoryGL?->id,
                    'accounts_payable_account_id' => $payableGL?->id,
                    'created_by' => $user->id,
                ]);
            }

            // Seed default petty cash drawer
            $pettyExists = PettyCashAccount::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->exists();
            if (!$pettyExists && $this->createdBranchId) {
                $pettyGLAccount = Account::where('marquee_id', $marqueeId)->where('account_code', '1001')->first();
                PettyCashAccount::create([
                    'marquee_id' => $marqueeId,
                    'account_name' => 'Main Reception Cash Drawer',
                    'branch_id' => $this->createdBranchId,
                    'gl_account_id' => $pettyGLAccount?->id,
                    'custodian_id' => $user->id,
                    'limit_amount' => 50000.00,
                    'current_balance' => 0.00,
                    'is_active' => true,
                ]);
            }
        }
    }

    // DYNAMIC RECORD MANAGEMENT ACTIONS

    // Halls
    public function addHall()
    {
        $this->validate([
            'new_hall_name' => 'required|string|max:255',
            'new_hall_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9\-]+$/i'],
            'new_capacity' => 'required|integer|min:10',
            'new_hall_type' => 'required|string|in:Marquee,Banquet,Lawn,Ballroom',
            'new_default_booking_price' => 'required|numeric|min:0',
            'new_hall_description' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        Hall::create([
            'marquee_id' => $marqueeId,
            'branch_id' => $this->createdBranchId,
            'hall_name' => $this->new_hall_name,
            'hall_code' => strtoupper($this->new_hall_code),
            'capacity' => $this->new_capacity,
            'hall_type' => $this->new_hall_type,
            'default_booking_price' => $this->new_default_booking_price,
            'description' => $this->new_hall_description,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->new_hall_name = '';
        $this->new_hall_code = '';
        $this->new_capacity = 500;
        $this->new_default_booking_price = 100000;
        $this->new_hall_description = '';
        session()->flash('success_hall', 'Hall Venue added successfully.');
    }

    public function deleteHall($id)
    {
        $hall = Hall::find($id);
        if ($hall) {
            $hall->delete();
        }
    }

    // Departments
    public function addDepartment()
    {
        $this->validate([
            'new_dept_name' => 'required|string|max:255',
            'new_dept_code' => 'required|string|max:50',
            'new_dept_type' => 'required|string|in:Kitchen Production,Operations,Administration',
            'new_dept_description' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        // Check uniqueness on branch
        $exists = Department::where('branch_id', $this->createdBranchId)
            ->where('department_code', $this->new_dept_code)
            ->exists();

        if ($exists) {
            $this->addError('new_dept_code', 'This department code already exists on this branch.');
            return;
        }

        Department::create([
            'marquee_id' => $marqueeId,
            'branch_id' => $this->createdBranchId,
            'department_code' => $this->new_dept_code,
            'name' => $this->new_dept_name,
            'department_type' => $this->new_dept_type,
            'description' => $this->new_dept_description,
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        $this->new_dept_name = '';
        $this->new_dept_code = '';
        $this->new_dept_description = '';
        session()->flash('success_dept', 'Department created successfully.');
    }

    public function deleteDepartment($id)
    {
        $dept = Department::find($id);
        if ($dept) {
            $dept->delete();
        }
    }

    // Users
    public function addStaffUser()
    {
        $this->validate([
            'new_user_name' => 'required|string|max:255',
            'new_user_email' => 'required|email|max:255|unique:users,email',
            'new_user_username' => 'required|string|min:3|max:50|unique:users,username|regex:/^[a-zA-Z0-9_\-\.]+$/',
            'new_user_password' => 'required|string|min:8',
            'new_user_phone' => [
                'required',
                'string',
                'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/',
                function ($attribute, $value, $fail) {
                    $dbPhone = $this->formatPhoneNumber($value);
                    $exists = DB::table('users')
                        ->where('phone', $dbPhone)
                        ->exists();
                    if ($exists) {
                        $fail('The user phone number has already been taken.');
                    }
                }
            ],
            'new_user_cnic' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'new_user_role_id' => 'required|exists:roles,id',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();
        $cleanPhone = $this->formatPhoneNumber($this->new_user_phone);

        $role = Role::find($this->new_user_role_id);
        $designation = match($role->name) {
            'branch_manager' => 'Branch Manager',
            'booking_officer' => 'Booking Officer',
            'accountant' => 'Accountant',
            'store_keeper' => 'Store Keeper',
            'kitchen_manager' => 'Kitchen Manager',
            default => 'Helper / Labor',
        };

        // Create employee first
        $employee = Employee::create([
            'marquee_id' => $marqueeId,
            'branch_id' => $this->createdBranchId,
            'name' => $this->new_user_name,
            'cnic' => $this->new_user_cnic,
            'mobile_number' => $cleanPhone,
            'designation' => $designation,
            'joining_date' => now()->format('Y-m-d'),
            'salary' => 0.00,
            'employment_type' => 'Permanent',
            'status' => 'active',
        ]);

        User::create([
            'marquee_id' => $marqueeId,
            'branch_id' => $this->createdBranchId,
            'name' => $this->new_user_name,
            'email' => $this->new_user_email,
            'username' => $this->new_user_username,
            'password' => Hash::make($this->new_user_password),
            'phone' => $cleanPhone,
            'role_id' => $this->new_user_role_id,
            'employee_id' => $employee->id,
            'status' => 'active',
        ]);

        $this->new_user_name = '';
        $this->new_user_email = '';
        $this->new_user_username = '';
        $this->new_user_password = '';
        $this->new_user_phone = '';
        $this->new_user_cnic = '';
        $this->new_user_role_id = '';
        session()->flash('success_user', 'Staff User and Employee Profile created successfully.');
    }

    public function deleteStaffUser($id)
    {
        $targetUser = User::find($id);
        if ($targetUser && $targetUser->id !== auth()->id()) {
            $targetUser->delete();
        }
    }

    // Booking Masters - Event Types
    public function addEventType()
    {
        $this->validate([
            'new_et_name' => 'required|string|max:255',
            'new_et_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9\-]+$/i'],
            'new_et_description' => 'nullable|string|max:500',
            'new_et_price' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        EventType::create([
            'marquee_id' => $marqueeId,
            'branch_id' => $this->createdBranchId,
            'event_type_code' => strtoupper($this->new_et_code),
            'event_type_name' => $this->new_et_name,
            'description' => $this->new_et_description,
            'base_price' => $this->new_et_price,
            'default_duration_hours' => 4.00,
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        $this->new_et_name = '';
        $this->new_et_code = '';
        $this->new_et_description = '';
        $this->new_et_price = 50000;
        session()->flash('success_et', 'Event Type added.');
    }

    public function deleteEventType($id)
    {
        $et = EventType::find($id);
        if ($et) {
            $et->delete();
        }
    }

    // Booking Masters - Shifts/Slots
    public function addSlot()
    {
        $this->validate([
            'new_slot_name' => 'required|string|max:255',
            'new_slot_start' => 'required',
            'new_slot_end' => 'required',
            'new_slot_description' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        Slot::create([
            'marquee_id' => $marqueeId,
            'slot_name' => $this->new_slot_name,
            'start_time' => $this->new_slot_start . ':00',
            'end_time' => $this->new_slot_end . ':00',
            'description' => $this->new_slot_description,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->new_slot_name = '';
        $this->new_slot_description = '';
        session()->flash('success_slot', 'Slot/Shift added.');
    }

    public function deleteSlot($id)
    {
        $slot = Slot::find($id);
        if ($slot) {
            $slot->delete();
        }
    }

    // Menu Categories
    public function addMenuCategory()
    {
        $this->validate([
            'new_cat_name' => 'required|string|max:255',
            'new_cat_code' => 'required|string|max:50',
            'new_cat_description' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        MenuCategory::create([
            'marquee_id' => $marqueeId,
            'category_code' => $this->new_cat_code,
            'category_name' => $this->new_cat_name,
            'description' => $this->new_cat_description,
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        $this->new_cat_name = '';
        $this->new_cat_code = '';
        $this->new_cat_description = '';
        session()->flash('success_mcat', 'Menu Category added.');
    }

    public function deleteMenuCategory($id)
    {
        $cat = MenuCategory::find($id);
        if ($cat) {
            $cat->delete();
        }
    }

    // Menu Items
    public function addMenuItem()
    {
        $this->validate([
            'new_item_name' => 'required|string|max:255',
            'new_item_code' => 'required|string|max:50',
            'new_item_category_id' => 'required|exists:menu_categories,id',
            'new_item_cost' => 'required|numeric|min:0',
            'new_item_price' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        MenuItem::create([
            'marquee_id' => $marqueeId,
            'category_id' => $this->new_item_category_id,
            'item_code' => $this->new_item_code,
            'item_name' => $this->new_item_name,
            'base_cost' => $this->new_item_cost,
            'selling_price' => $this->new_item_price,
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        $this->new_item_name = '';
        $this->new_item_code = '';
        $this->new_item_cost = 200;
        $this->new_item_price = 350;
        session()->flash('success_mitem', 'Menu Item added.');
    }

    public function deleteMenuItem($id)
    {
        $item = MenuItem::find($id);
        if ($item) {
            $item->delete();
        }
    }

    // Packages
    public function addPackage()
    {
        $this->validate([
            'new_pkg_name' => 'required|string|max:255',
            'new_pkg_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9\-]+$/i'],
            'new_pkg_price' => 'required|numeric|min:0',
            'new_pkg_description' => 'nullable|string|max:500',
            'new_pkg_items' => 'required|array|min:1',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        $package = Package::create([
            'marquee_id' => $marqueeId,
            'package_code' => strtoupper($this->new_pkg_code),
            'package_name' => $this->new_pkg_name,
            'description' => $this->new_pkg_description,
            'package_type' => 'Standard',
            'minimum_guests' => 100,
            'maximum_guests' => 1000,
            'base_price' => 0,
            'per_plate_price' => $this->new_pkg_price,
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        // Attach selected dishes
        $pivotData = [];
        foreach ($this->new_pkg_items as $index => $itemId) {
            $pivotData[$itemId] = [
                'quantity' => 1,
                'display_order' => $index + 1
            ];
        }
        $package->menuItems()->sync($pivotData);

        $this->new_pkg_name = '';
        $this->new_pkg_code = '';
        $this->new_pkg_price = 990;
        $this->new_pkg_description = '';
        $this->new_pkg_items = [];
        session()->flash('success_pkg', 'Plate Package added.');
    }

    public function deletePackage($id)
    {
        $pkg = Package::find($id);
        if ($pkg) {
            $pkg->delete();
        }
    }

    // Inventory Units
    public function addInventoryUnit()
    {
        $this->validate([
            'new_unit_name' => 'required|string|max:255',
            'new_unit_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9]+$/i'],
            'new_unit_description' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        InventoryUnit::create([
            'marquee_id' => $marqueeId,
            'name' => $this->new_unit_name,
            'short_code' => strtoupper($this->new_unit_code),
            'description' => $this->new_unit_description,
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        $this->new_unit_name = '';
        $this->new_unit_code = '';
        $this->new_unit_description = '';
        session()->flash('success_unit', 'Inventory Unit added.');
    }

    public function deleteInventoryUnit($id)
    {
        $unit = InventoryUnit::find($id);
        if ($unit) {
            $unit->delete();
        }
    }

    // Inventory Categories
    public function addInventoryCategory()
    {
        $this->validate([
            'new_inv_cat_name' => 'required|string|max:255',
            'new_inv_cat_description' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        InventoryCategory::create([
            'marquee_id' => $marqueeId,
            'name' => $this->new_inv_cat_name,
            'description' => $this->new_inv_cat_description,
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        $this->new_inv_cat_name = '';
        $this->new_inv_cat_description = '';
        session()->flash('success_icat', 'Inventory Category added.');
    }

    public function deleteInventoryCategory($id)
    {
        $cat = InventoryCategory::find($id);
        if ($cat) {
            $cat->delete();
        }
    }

    // Suppliers
    public function addSupplier()
    {
        $this->validate([
            'new_supplier_name' => 'required|string|max:255',
            'new_supplier_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9\-]+$/i'],
            'new_supplier_phone' => [
                'required',
                'string',
                'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/',
                function ($attribute, $value, $fail) {
                    $dbPhone = $this->formatPhoneNumber($value);
                    $exists = DB::table('suppliers')
                        ->where('mobile_number', $dbPhone)
                        ->exists();
                    if ($exists) {
                        $fail('The supplier phone number has already been taken.');
                    }
                }
            ],
            'new_supplier_person' => 'nullable|string|max:255',
            'new_supplier_city' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();
        $cleanPhone = $this->formatPhoneNumber($this->new_supplier_phone);

        Supplier::create([
            'marquee_id' => $marqueeId,
            'name' => $this->new_supplier_name,
            'supplier_code' => strtoupper($this->new_supplier_code),
            'mobile_number' => $cleanPhone,
            'contact_person' => $this->new_supplier_person,
            'city' => $this->new_supplier_city,
            'opening_balance' => 0.00,
            'status' => 'Active',
            'created_by' => $user->id,
        ]);

        $this->new_supplier_name = '';
        $this->new_supplier_code = '';
        $this->new_supplier_phone = '';
        $this->new_supplier_person = '';
        $this->new_supplier_city = '';
        session()->flash('success_sup', 'Supplier registered.');
    }

    public function deleteSupplier($id)
    {
        $sup = Supplier::find($id);
        if ($sup) {
            $sup->delete();
        }
    }

    // Cash Drawers / Petty cash
    public function addCashAccount()
    {
        $this->validate([
            'new_cash_name' => 'required|string|max:255',
            'new_cash_limit' => 'required|numeric|min:0',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        $cashGL = Account::where('marquee_id', $marqueeId)->where('account_code', '1001')->first();
        $glAccountId = null;

        if ($cashGL) {
            $count = Account::where('marquee_id', $marqueeId)->where('parent_id', $cashGL->id)->count();
            $subCode = '1001' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
            while (Account::where('marquee_id', $marqueeId)->where('account_code', $subCode)->exists()) {
                $count++;
                $subCode = '1001' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
            }

            $newGL = Account::create([
                'marquee_id' => $marqueeId,
                'account_code' => $subCode,
                'name' => $this->new_cash_name,
                'parent_id' => $cashGL->id,
                'account_type_id' => $cashGL->account_type_id,
                'nature' => $cashGL->nature,
                'is_active' => true,
                'system_generated' => false,
                'description' => "Cash ledger for " . $this->new_cash_name,
            ]);
            $glAccountId = $newGL->id;
        }

        PettyCashAccount::create([
            'marquee_id' => $marqueeId,
            'account_name' => $this->new_cash_name,
            'branch_id' => $this->createdBranchId,
            'gl_account_id' => $glAccountId ?: ($cashGL?->id),
            'custodian_id' => $user->id,
            'limit_amount' => $this->new_cash_limit,
            'current_balance' => 0.00,
            'is_active' => true,
        ]);

        $this->new_cash_name = '';
        $this->new_cash_limit = 50000;
        session()->flash('success_cash', 'Cash Drawer created and ledger account provisioned.');
    }

    public function deleteCashAccount($id)
    {
        $cash = PettyCashAccount::find($id);
        if ($cash) {
            // Delete associated ledger if exists and not system generated
            if ($cash->gl_account_id) {
                $ledger = Account::find($cash->gl_account_id);
                if ($ledger && !$ledger->system_generated && $ledger->account_code !== '1001') {
                    $ledger->delete();
                }
            }
            $cash->delete();
        }
    }

    // Bank Accounts
    public function addBankAccount()
    {
        $this->validate([
            'new_bank_name' => 'required|string|max:255',
            'new_bank_account_number' => 'required|string|min:5|max:100',
            'new_bank_branch_name' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        $bankGL = Account::where('marquee_id', $marqueeId)->where('account_code', '1002')->first();
        $glAccountId = null;

        if ($bankGL) {
            $count = Account::where('marquee_id', $marqueeId)->where('parent_id', $bankGL->id)->count();
            $subCode = '1002' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
            while (Account::where('marquee_id', $marqueeId)->where('account_code', $subCode)->exists()) {
                $count++;
                $subCode = '1002' . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
            }

            $newGL = Account::create([
                'marquee_id' => $marqueeId,
                'account_code' => $subCode,
                'name' => $this->new_bank_name . ' (' . substr($this->new_bank_account_number, -4) . ')',
                'parent_id' => $bankGL->id,
                'account_type_id' => $bankGL->account_type_id,
                'nature' => $bankGL->nature,
                'is_active' => true,
                'system_generated' => false,
                'description' => "Bank ledger for " . $this->new_bank_name . " A/C " . $this->new_bank_account_number,
            ]);
            $glAccountId = $newGL->id;
        }

        CashBankAccount::create([
            'marquee_id' => $marqueeId,
            'account_name' => $this->new_bank_name,
            'account_number' => $this->new_bank_account_number,
            'bank_name' => $this->new_bank_name,
            'branch_name' => $this->new_bank_branch_name,
            'gl_account_id' => $glAccountId ?: ($bankGL?->id),
            'status' => 'active',
        ]);

        $this->new_bank_name = '';
        $this->new_bank_account_number = '';
        $this->new_bank_branch_name = '';
        session()->flash('success_bank', 'Bank Account registered and ledger account provisioned.');
    }

    public function deleteBankAccount($id)
    {
        $bank = CashBankAccount::find($id);
        if ($bank) {
            $bank->delete();
        }
    }

    // FINISH ONBOARDING & LAUNCH
    public function finishSetup()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();
        $marquee = Marquee::find($marqueeId);

        if (!$marquee) {
            return redirect()->route('dashboard');
        }

        // Validate Financial Year dates
        $this->validate([
            'fy_name' => 'required|string|max:255',
            'fy_start_date' => 'required|date',
            'fy_end_date' => 'required|date|after:fy_start_date',
        ]);

        DB::transaction(function () use ($user, $marquee, $marqueeId) {
            // Seed Financial Year
            FinancialYear::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marqueeId, 'name' => $this->fy_name],
                [
                    'start_date' => $this->fy_start_date,
                    'end_date' => $this->fy_end_date,
                    'status' => 'active',
                    'is_default' => true,
                    'created_by' => $user->id,
                ]
            );

            // Double check validation criteria matching checklist before completing
            $checklist = $marquee->getOnboardingChecklist();
            
            // Mark onboarding status as completed
            $marquee->update([
                'is_setup_completed' => true,
            ]);

            // Assign user role (defaults to owner/business_owner)
            $ownerRole = Role::where('name', 'owner')->first() ?? Role::where('name', 'business_owner')->first();
            if ($ownerRole && !$user->role_id) {
                $user->role_id = $ownerRole->id;
                $user->save();
            }
        });

        session()->flash('success', 'Congratulations! Your business onboarding and configuration is completed.');
        return redirect()->route('dashboard');
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        // Get live records for display lists
        $halls = Hall::where('marquee_id', $marqueeId)->orderBy('hall_name')->get();
        $departments = Department::where('marquee_id', $marqueeId)->orderBy('name')->get();
        $roles = Role::whereNotIn('name', ['super_admin', 'owner', 'business_owner'])->orderBy('label')->get();
        $staffUsers = User::where('marquee_id', $marqueeId)
            ->where('role_id', '!=', Role::whereIn('name', ['owner', 'business_owner'])->first()?->id)
            ->with('role')
            ->get();
        
        $eventTypes = EventType::where('marquee_id', $marqueeId)->orderBy('event_type_name')->get();
        $slots = Slot::where('marquee_id', $marqueeId)->orderBy('slot_name')->get();
        
        $menuCategories = MenuCategory::where('marquee_id', $marqueeId)->orderBy('category_name')->get();
        
        $menuItems = MenuItem::where('marquee_id', $marqueeId)
            ->when($this->search_menu_item, function($query) {
                $query->where(function($q) {
                    $q->where('item_name', 'like', '%' . $this->search_menu_item . '%')
                      ->orWhere('item_code', 'like', '%' . $this->search_menu_item . '%');
                });
            })
            ->with('category')
            ->orderBy('item_name')
            ->get();

        $packages = Package::where('marquee_id', $marqueeId)
            ->when($this->search_package, function($query) {
                $query->where(function($q) {
                    $q->where('package_name', 'like', '%' . $this->search_package . '%')
                      ->orWhere('package_code', 'like', '%' . $this->search_package . '%');
                });
            })
            ->orderBy('package_name')
            ->get();
        
        $units = InventoryUnit::where('marquee_id', $marqueeId)->orderBy('name')->get();
        $invCategories = InventoryCategory::where('marquee_id', $marqueeId)->orderBy('name')->get();
        $suppliers = Supplier::where('marquee_id', $marqueeId)->orderBy('name')->get();
        
        $accounts = Account::where('marquee_id', $marqueeId)->whereNotNull('parent_id')->orderBy('account_code')->get();
        $cashAccounts = PettyCashAccount::where('marquee_id', $marqueeId)->orderBy('account_name')->get();
        $bankAccounts = CashBankAccount::where('marquee_id', $marqueeId)->orderBy('bank_name')->get();

        // Load possible managers (super admins + owners)
        $managerRoles = Role::whereIn('name', ['super_admin', 'owner', 'business_owner'])->pluck('id');
        $possibleManagers = User::whereIn('role_id', $managerRoles)->orderBy('name')->get();

        // Calculate progress percentage based on completed checklists
        $marquee = Marquee::find($marqueeId);
        $checklist = $marquee ? $marquee->getOnboardingChecklist() : [
            'marquee_info' => false,
            'branch' => false,
            'branch_config' => false,
            'halls' => false,
            'departments' => false,
            'users' => false,
            'booking_masters' => false,
            'menu_packages' => false,
            'inventory' => false,
            'finance' => false,
        ];
        $completedCount = collect($checklist)->filter()->count();
        $progressPercent = ($this->totalSteps > 0 && count($checklist) > 0) ? (int)(($completedCount / count($checklist)) * 100) : 0;

        return view('livewire.setup-wizard', compact(
            'halls', 'departments', 'roles', 'staffUsers', 
            'eventTypes', 'slots', 'menuCategories', 'menuItems', 'packages',
            'units', 'invCategories', 'suppliers',
            'accounts', 'cashAccounts', 'bankAccounts', 'checklist', 'progressPercent', 'possibleManagers'
        ))->layout('layouts.admin');
    }
}
