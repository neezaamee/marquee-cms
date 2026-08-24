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
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class SetupWizard extends Component
{
    use WithFileUploads;

    public $currentStep = 1;
    public $totalSteps = 5;

    // STEP 1: Marquee Information
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

    // STEP 2: Branch Configuration
    public $branch_name = 'Main Branch';
    public $branch_address = '';
    public $branch_city = '';
    public $branch_province = '';
    public $branch_phone = '';
    public $branch_manager = '';

    // STEP 3: Hall Configuration
    public $hall_name = 'Royal Banquet Hall';
    public $hall_code = 'ROY-HL';
    public $capacity = 500;
    public $hall_type = 'Banquet';
    public $default_booking_price = 100000;
    public $hall_description = 'Elegant banquet hall with luxury lighting and ventilation.';

    // STEP 4: Financial Year
    public $fy_name = '';
    public $fy_start_date = '';
    public $fy_end_date = '';

    // STEP 5: Operational Defaults & Tax
    public $tax_rate = 13.00;
    public $invoice_prefix = 'INV-';
    public $booking_prefix = 'BK-';
    public $default_payment_method = 'Cash';

    // Helper lists for selects
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

    public function updatedBranchProvince($value)
    {
        $this->branch_city = '';
    }

    public function mount()
    {
        $user = auth()->user();

        // If the user already has marquee details, pre-populate what we can
        if ($user->marquee_id && $user->marquee) {
            $marquee = $user->marquee;
            $this->marquee_name = $marquee->name;
            $this->business_type = $marquee->business_type ?: 'Single Marquee';
            $this->phone = $marquee->phone;
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
        } else {
            $this->email = $user->email;
            $this->marquee_name = $user->name . "'s Marquee";
        }

        // Set default financial year dates (calendar year)
        $currentYear = date('Y');
        $this->fy_name = "FY " . $currentYear;
        $this->fy_start_date = $currentYear . "-01-01";
        $this->fy_end_date = $currentYear . "-12-31";

        // Prepopulate step parameter if passed
        $step = request()->query('step');
        if ($step && is_numeric($step) && $step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = (int)$step;
        }
    }

    public function getBranchCitiesProperty()
    {
        return $this->getCitiesForProvince($this->branch_province);
    }

    public function getValidationRules()
    {
        if ($this->currentStep == 1) {
            return [
                'marquee_name' => 'required|string|max:255',
                'business_type' => 'required|string|in:Single Marquee,Banquet Hall Chain,Lawn/Catering,Hotel Event Center',
                'logo' => 'nullable|image|max:2048',
                'phone' => 'required|string|max:50',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:255',
                'province' => 'required|string',
                'city' => 'required|string',
                'country' => 'required|string|max:255',
                'timezone' => 'required|string|max:255',
                'currency' => 'required|string|max:10',
                'tax_authority' => 'required|string|max:50',
                'ntn' => 'nullable|string|max:50',
                'strn' => 'nullable|string|max:50',
            ];
        } elseif ($this->currentStep == 2) {
            return [
                'branch_name' => 'required|string|max:255',
                'branch_phone' => 'required|string|max:50',
                'branch_address' => 'required|string|max:255',
                'branch_province' => 'required|string',
                'branch_city' => 'required|string',
                'branch_manager' => 'nullable|string|max:255',
            ];
        } elseif ($this->currentStep == 3) {
            return [
                'hall_name' => 'required|string|max:255',
                'hall_code' => 'required|string|max:50',
                'capacity' => 'required|integer|min:10',
                'hall_type' => 'required|string|in:Marquee,Banquet,Lawn,Ballroom',
                'default_booking_price' => 'required|numeric|min:0',
                'hall_description' => 'nullable|string|max:500',
            ];
        } elseif ($this->currentStep == 4) {
            return [
                'fy_name' => 'required|string|max:255',
                'fy_start_date' => 'required|date',
                'fy_end_date' => 'required|date|after:fy_start_date',
            ];
        } elseif ($this->currentStep == 5) {
            return [
                'tax_rate' => 'required|numeric|min:0|max:100',
                'invoice_prefix' => 'nullable|string|max:20',
                'booking_prefix' => 'nullable|string|max:20',
                'default_payment_method' => 'required|string|in:Cash,Bank Transfer,Cheque,Card',
            ];
        }
        return [];
    }

    public function nextStep()
    {
        $this->validate($this->getValidationRules());
        
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
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
            // Validate current step before navigating to other steps to prevent skipping
            if ($step > $this->currentStep) {
                $this->validate($this->getValidationRules());
            }
            $this->currentStep = $step;
        }
    }

    public function finishSetup()
    {
        $this->validate($this->getValidationRules());

        $user = auth()->user();

        DB::transaction(function () use ($user) {
            // 1. Logo Upload
            $logoPath = null;
            if ($this->logo) {
                $logoPath = $this->logo->store('logos', 'public');
            }

            // Get or create subscription plan
            $plan = SubscriptionPlan::where('slug', 'basic')->first() ?? SubscriptionPlan::first();

            // 2. Create or Update Marquee Profile
            $marquee = Marquee::updateOrCreate(
                ['id' => $user->marquee_id],
                [
                    'name' => $this->marquee_name,
                    'business_type' => $this->business_type,
                    'logo' => $logoPath ?: ($user->marquee ? $user->marquee->logo : null),
                    'address' => $this->address,
                    'city' => $this->city,
                    'province' => $this->province,
                    'country' => $this->country,
                    'timezone' => $this->timezone,
                    'currency' => $this->currency,
                    'phone' => $this->phone,
                    'email' => $this->email,
                    'ntn' => $this->ntn,
                    'strn' => $this->strn,
                    'tax_authority' => $this->tax_authority,
                    'status' => 'active',
                    'is_setup_completed' => true,
                ]
            );

            // Update user subscription & pivot association
            $user->update([
                'marquee_id' => $marquee->id,
                'subscription_plan_id' => $plan ? $plan->id : 1,
                'subscription_ends_at' => now()->addYear(),
            ]);
            $user->ownedMarquees()->syncWithoutDetaching([$marquee->id]);

            // 3. Create Branch
            $branch = Branch::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'name' => $this->branch_name],
                [
                    'address' => $this->branch_address,
                    'city' => $this->branch_city,
                    'province' => $this->branch_province,
                    'phone' => $this->branch_phone,
                    'status' => 'active',
                    'is_head_office' => true,
                ]
            );

            // 4. Create Hall
            $hall = Hall::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'branch_id' => $branch->id, 'hall_name' => $this->hall_name],
                [
                    'hall_code' => $this->hall_code,
                    'capacity' => $this->capacity,
                    'hall_type' => $this->hall_type,
                    'default_booking_price' => $this->default_booking_price,
                    'description' => $this->hall_description,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]
            );

            // 5. Seed default shifts/slots & assign them to the new Hall
            $shifts = [
                [
                    'name' => 'Day Shift',
                    'start' => '13:00:00',
                    'end' => '16:00:00',
                    'desc' => 'Default Day Shift (01:00 PM - 04:00 PM)'
                ],
                [
                    'name' => 'Night Shift',
                    'start' => '18:00:00',
                    'end' => '21:00:00',
                    'desc' => 'Default Night Shift (06:00 PM - 09:00 PM)'
                ]
            ];

            foreach ($shifts as $shift) {
                $slot = Slot::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                    ['marquee_id' => $marquee->id, 'slot_name' => $shift['name']],
                    [
                        'start_time' => $shift['start'],
                        'end_time' => $shift['end'],
                        'description' => $shift['desc'],
                        'status' => 'active',
                        'created_by' => $user->id,
                    ]
                );

                // Sync slot with hall
                if (!$hall->slots()->where('slots.id', $slot->id)->exists()) {
                    $hall->slots()->attach($slot->id, [
                        'marquee_id' => $marquee->id,
                        'status' => 'active',
                        'created_by' => $user->id,
                    ]);
                }
            }

            // 6. Seed default system event types
            $eventTypes = [
                ['name' => 'Wedding', 'code' => 'WEDD', 'desc' => 'Traditional wedding reception ceremony.', 'order' => 1],
                ['name' => 'Barat', 'code' => 'BRAT', 'desc' => 'Wedding Barat arrival and main ceremony.', 'order' => 2],
                ['name' => 'Walima', 'code' => 'WALI', 'desc' => 'Wedding Walima reception dinner.', 'order' => 3],
                ['name' => 'Mehndi', 'code' => 'MEHN', 'desc' => 'Pre-wedding henna / musical ceremony.', 'order' => 4],
                ['name' => 'Nikah', 'code' => 'NIKA', 'desc' => 'Islamic marriage contract signing ceremony.', 'order' => 5],
                ['name' => 'Birthday', 'code' => 'BIRT', 'desc' => 'Birthday party celebration event.', 'order' => 6],
                ['name' => 'Corporate Event', 'code' => 'CORP', 'desc' => 'Corporate dinners, meetings or awards.', 'order' => 7],
                ['name' => 'Other', 'code' => 'OTHR', 'desc' => 'Custom event category.', 'order' => 8]
            ];

            foreach ($eventTypes as $et) {
                EventType::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                    ['marquee_id' => $marquee->id, 'event_type_code' => $et['code']],
                    [
                        'branch_id' => $branch->id,
                        'event_type_name' => $et['name'],
                        'description' => $et['desc'],
                        'default_duration_hours' => 4.00,
                        'base_price' => 50000.00,
                        'status' => 'Active',
                        'sort_order' => $et['order'],
                        'is_system_default' => true,
                        'created_by' => $user->id,
                    ]
                );
            }

            // 7. Seed Financial Year
            FinancialYear::withoutGlobalScope('tenant')->withTrashed()->firstOrCreate(
                ['marquee_id' => $marquee->id, 'name' => $this->fy_name],
                [
                    'start_date' => $this->fy_start_date,
                    'end_date' => $this->fy_end_date,
                    'status' => 'active',
                    'is_default' => true,
                    'created_by' => $user->id,
                ]
            );

            // 8. Seed default Chart of Accounts (COA)
            $accountTypes = AccountType::whereNull('marquee_id')->get();
            $seededTypes = [];
            foreach ($accountTypes as $type) {
                $seededTypes[$type->code] = $type;
            }

            // Fallback if not seeded
            if (empty($seededTypes)) {
                $typesData = [
                    ['name' => 'Current Assets', 'code' => 'CURRENT_ASSETS', 'nature' => 'Asset'],
                    ['name' => 'Fixed Assets', 'code' => 'FIXED_ASSETS', 'nature' => 'Asset'],
                    ['name' => 'Current Liabilities', 'code' => 'CURRENT_LIABILITIES', 'nature' => 'Liability'],
                    ['name' => 'Long-Term Liabilities', 'code' => 'LONG_TERM_LIABILITIES', 'nature' => 'Liability'],
                    ['name' => 'Owner Equity', 'code' => 'OWNER_EQUITY', 'nature' => 'Equity'],
                    ['name' => 'Retained Earnings', 'code' => 'RETAINED_EARNINGS', 'nature' => 'Equity'],
                    ['name' => 'Operating Revenue', 'code' => 'OPERATING_REVENUE', 'nature' => 'Income'],
                    ['name' => 'Other Income', 'code' => 'OTHER_INCOME', 'nature' => 'Income'],
                    ['name' => 'Direct Expenses', 'code' => 'DIRECT_EXPENSES', 'nature' => 'Expense'],
                    ['name' => 'Operating Expenses', 'code' => 'OPERATING_EXPENSES', 'nature' => 'Expense'],
                ];
                foreach ($typesData as $td) {
                    $seededTypes[$td['code']] = AccountType::firstOrCreate(
                        ['code' => $td['code'], 'marquee_id' => null],
                        ['name' => $td['name'], 'nature' => $td['nature']]
                    );
                }
            }

            // Seed root level accounts
            $topLevelAccounts = [
                '1000' => ['name' => 'Assets', 'nature' => 'Asset', 'code_type' => 'CURRENT_ASSETS'],
                '2000' => ['name' => 'Liabilities', 'nature' => 'Liability', 'code_type' => 'CURRENT_LIABILITIES'],
                '3000' => ['name' => 'Equity', 'nature' => 'Equity', 'code_type' => 'OWNER_EQUITY'],
                '4000' => ['name' => 'Income', 'nature' => 'Income', 'code_type' => 'OPERATING_REVENUE'],
                '5000' => ['name' => 'Expenses', 'nature' => 'Expense', 'code_type' => 'DIRECT_EXPENSES'],
            ];

            $topLevelInstances = [];
            foreach ($topLevelAccounts as $code => $data) {
                $topLevelInstances[$code] = Account::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                    ['marquee_id' => $marquee->id, 'account_code' => $code],
                    [
                        'name' => $data['name'],
                        'parent_id' => null,
                        'account_type_id' => $seededTypes[$data['code_type']]->id,
                        'nature' => $data['nature'],
                        'is_active' => true,
                        'system_generated' => true,
                        'description' => "Root account for {$data['name']}",
                    ]
                );
            }

            // Sub accounts
            $subAccounts = [
                ['parent' => '1000', 'code' => '1001', 'name' => 'Cash', 'type' => 'CURRENT_ASSETS', 'nature' => 'Asset', 'system' => true, 'desc' => 'General Cash Account'],
                ['parent' => '1000', 'code' => '1002', 'name' => 'Bank', 'type' => 'CURRENT_ASSETS', 'nature' => 'Asset', 'system' => true, 'desc' => 'Default Bank Account'],
                ['parent' => '1000', 'code' => '1003', 'name' => 'Accounts Receivable', 'type' => 'CURRENT_ASSETS', 'nature' => 'Asset', 'system' => true, 'desc' => 'Outstanding Customer Payments'],
                ['parent' => '1000', 'code' => '1004', 'name' => 'Inventory', 'type' => 'CURRENT_ASSETS', 'nature' => 'Asset', 'system' => true, 'desc' => 'Inventory Assets'],
                ['parent' => '2000', 'code' => '2001', 'name' => 'Accounts Payable', 'type' => 'CURRENT_LIABILITIES', 'nature' => 'Liability', 'system' => true, 'desc' => 'Outstanding Vendor Payments'],
                ['parent' => '2000', 'code' => '2002', 'name' => 'Security Deposits', 'type' => 'CURRENT_LIABILITIES', 'nature' => 'Liability', 'system' => true, 'desc' => 'Refundable Booking Security Deposits'],
                ['parent' => '3000', 'code' => '3001', 'name' => 'Owner\'s Capital', 'type' => 'OWNER_EQUITY', 'nature' => 'Equity', 'system' => true, 'desc' => 'Capital Invested by Owner'],
                ['parent' => '3000', 'code' => '3501', 'name' => 'Retained Earnings', 'type' => 'RETAINED_EARNINGS', 'nature' => 'Equity', 'system' => true, 'desc' => 'Accumulated Earnings'],
                ['parent' => '4000', 'code' => '4001', 'name' => 'Hall Booking Revenue', 'type' => 'OPERATING_REVENUE', 'nature' => 'Income', 'system' => true, 'desc' => 'Revenue from Hall Bookings'],
                ['parent' => '4000', 'code' => '4002', 'name' => 'Catering Revenue', 'type' => 'OPERATING_REVENUE', 'nature' => 'Income', 'system' => true, 'desc' => 'Revenue from Catering Services'],
                ['parent' => '5000', 'code' => '5501', 'name' => 'Salaries', 'type' => 'OPERATING_EXPENSES', 'nature' => 'Expense', 'system' => true, 'desc' => 'Employee Salaries'],
                ['parent' => '5000', 'code' => '5502', 'name' => 'Utilities', 'type' => 'OPERATING_EXPENSES', 'nature' => 'Expense', 'system' => true, 'desc' => 'Electricity, Gas, and Water Bills'],
                ['parent' => '5000', 'code' => '5503', 'name' => 'Maintenance', 'type' => 'OPERATING_EXPENSES', 'nature' => 'Expense', 'system' => true, 'desc' => 'Hall Repair & Maintenance Costs']
            ];

            foreach ($subAccounts as $sub) {
                $parentInstance = $topLevelInstances[$sub['parent']];
                Account::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                    ['marquee_id' => $marquee->id, 'account_code' => $sub['code']],
                    [
                        'name' => $sub['name'],
                        'parent_id' => $parentInstance->id,
                        'account_type_id' => $seededTypes[$sub['type']]->id,
                        'nature' => $sub['nature'],
                        'is_active' => true,
                        'system_generated' => $sub['system'],
                        'description' => $sub['desc'],
                    ]
                );
            }

            // 9. Seed Catering Menu Categories, Items, and Packages
            $catRice = \App\Models\MenuCategory::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'category_code' => 'RICE'],
                [
                    'category_name' => 'Rice Dishes',
                    'description' => 'Basmati rice delicacies',
                    'sort_order' => 1,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $catMain = \App\Models\MenuCategory::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'category_code' => 'MAIN'],
                [
                    'category_name' => 'Main Course',
                    'description' => 'Mutton, beef, and chicken gravies',
                    'sort_order' => 2,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $catBreads = \App\Models\MenuCategory::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'category_code' => 'BREAD'],
                [
                    'category_name' => 'Tandoori Breads',
                    'description' => 'Fresh tandoori rotis and naans',
                    'sort_order' => 3,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $catDesserts = \App\Models\MenuCategory::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'category_code' => 'DESS'],
                [
                    'category_name' => 'Desserts',
                    'description' => 'Traditional sweet dishes',
                    'sort_order' => 4,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $itemBiryani = \App\Models\MenuItem::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'item_code' => 'CH-BIR'],
                [
                    'category_id' => $catRice->id,
                    'item_name' => 'Chicken Special Biryani',
                    'base_cost' => 200.00,
                    'selling_price' => 350.00,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $itemKarahi = \App\Models\MenuItem::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'item_code' => 'CH-KAR'],
                [
                    'category_id' => $catMain->id,
                    'item_name' => 'Chicken Karahi (Premium)',
                    'base_cost' => 250.00,
                    'selling_price' => 450.00,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $itemNaan = \App\Models\MenuItem::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'item_code' => 'ROG-NAN'],
                [
                    'category_id' => $catBreads->id,
                    'item_name' => 'Roghni Naan',
                    'base_cost' => 20.00,
                    'selling_price' => 40.00,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $itemKheer = \App\Models\MenuItem::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'item_code' => 'SH-KHE'],
                [
                    'category_id' => $catDesserts->id,
                    'item_name' => 'Shahi Kheer',
                    'base_cost' => 80.00,
                    'selling_price' => 150.00,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $package = \App\Models\Package::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'package_code' => 'STD-CH'],
                [
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
                ]
            );

            // Sync menu items to package
            $package->menuItems()->sync([
                $itemBiryani->id => ['quantity' => 1, 'display_order' => 1],
                $itemKarahi->id => ['quantity' => 1, 'display_order' => 2],
                $itemNaan->id => ['quantity' => 2, 'display_order' => 3],
                $itemKheer->id => ['quantity' => 1, 'display_order' => 4],
            ]);

            // 10. Seed Inventory settings, units, categories, and supplier
            $inventoryGL = Account::where('marquee_id', $marquee->id)->where('account_code', '1004')->first();
            $payableGL = Account::where('marquee_id', $marquee->id)->where('account_code', '2001')->first();

            \App\Models\InventorySetting::withoutGlobalScope('tenant')->updateOrCreate(
                ['marquee_id' => $marquee->id],
                [
                    'inventory_asset_account_id' => $inventoryGL?->id,
                    'accounts_payable_account_id' => $payableGL?->id,
                    'created_by' => $user->id,
                ]
            );

            $unitPcs = \App\Models\InventoryUnit::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'name' => 'Piece'],
                [
                    'short_code' => 'Pcs',
                    'description' => 'Single item unit',
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $unitKg = \App\Models\InventoryUnit::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'name' => 'Kilogram'],
                [
                    'short_code' => 'Kg',
                    'description' => 'Weight unit',
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $unitLtr = \App\Models\InventoryUnit::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'name' => 'Litre'],
                [
                    'short_code' => 'Ltr',
                    'description' => 'Volume unit',
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $invCatFood = \App\Models\InventoryCategory::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'name' => 'Food Items'],
                [
                    'description' => 'Catering raw materials & foods',
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            $invCatCrockery = \App\Models\InventoryCategory::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'name' => 'Crockery'],
                [
                    'description' => 'Plates, cups, cutlery',
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            \App\Models\Supplier::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'name' => 'Al-Makkah Foods & Grains'],
                [
                    'supplier_code' => 'SUP-00001',
                    'contact_person' => 'Haji Muhammad Makkah',
                    'mobile_number' => '03001234511',
                    'city' => 'Lahore',
                    'opening_balance' => 0.00,
                    'status' => 'Active',
                    'created_by' => $user->id,
                ]
            );

            // 11. Seed Expense currencies, categories, types, petty cash drawer, and approval rules
            $currencyPkr = \App\Models\Currency::withoutGlobalScope('tenant')->firstOrCreate(
                ['marquee_id' => $marquee->id, 'code' => 'PKR'],
                [
                    'name' => 'Pakistani Rupee',
                    'symbol' => 'Rs.',
                    'is_base' => true,
                    'exchange_rate' => 1.000000,
                ]
            );

            $currencyUsd = \App\Models\Currency::withoutGlobalScope('tenant')->firstOrCreate(
                ['marquee_id' => $marquee->id, 'code' => 'USD'],
                [
                    'name' => 'US Dollar',
                    'symbol' => '$',
                    'is_base' => false,
                    'exchange_rate' => 0.003600,
                ]
            );

            $expTypes = [
                ['name' => 'Utility Bills', 'code' => 'utility_bills', 'desc' => 'Electric, gas, water, internet charges'],
                ['name' => 'Repairs & Maintenance', 'code' => 'maintenance', 'desc' => 'Repair and upkeep of assets'],
                ['name' => 'Staff Salaries & Welfare', 'code' => 'salaries', 'desc' => 'Salary disbursements and meals'],
                ['name' => 'Marketing & Sales', 'code' => 'marketing', 'desc' => 'Advertising and promo campaigns'],
                ['name' => 'Miscellaneous', 'code' => 'miscellaneous', 'desc' => 'Other overheads'],
            ];

            foreach ($expTypes as $et) {
                \App\Models\ExpenseType::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                    ['marquee_id' => $marquee->id, 'code' => $et['code']],
                    [
                        'name' => $et['name'],
                        'description' => $et['desc'],
                        'is_active' => true,
                    ]
                );
            }

            $salaryGL = Account::where('marquee_id', $marquee->id)->where('account_code', '5501')->first();
            $utilityGL = Account::where('marquee_id', $marquee->id)->where('account_code', '5502')->first();
            $maintenanceGL = Account::where('marquee_id', $marquee->id)->where('account_code', '5503')->first();

            $expCatSalaries = \App\Models\ExpenseCategory::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'category_code' => 'SAL'],
                [
                    'name' => 'Salaries & Advances',
                    'default_account_id' => $salaryGL?->id,
                    'default_tax_rate' => 0.00,
                    'default_budget_amount' => 500000.00,
                    'display_order' => 1,
                    'is_active' => true,
                ]
            );

            $expCatUtilities = \App\Models\ExpenseCategory::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'category_code' => 'UTL'],
                [
                    'name' => 'Utilities (Bills)',
                    'default_account_id' => $utilityGL?->id,
                    'default_tax_rate' => 15.00,
                    'default_budget_amount' => 150000.00,
                    'display_order' => 2,
                    'is_active' => true,
                ]
            );

            $expCatMaint = \App\Models\ExpenseCategory::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'category_code' => 'MNT'],
                [
                    'name' => 'Maintenance Repairs',
                    'default_account_id' => $maintenanceGL?->id,
                    'default_tax_rate' => 5.00,
                    'default_budget_amount' => 80000.00,
                    'display_order' => 3,
                    'is_active' => true,
                ]
            );

            // Petty CashGL GL account (General Cash gl account: 1001)
            $pettyGLAccount = Account::where('marquee_id', $marquee->id)->where('account_code', '1001')->first();
            \App\Models\PettyCashAccount::withoutGlobalScope('tenant')->withTrashed()->updateOrCreate(
                ['marquee_id' => $marquee->id, 'account_name' => 'Main Reception Cash Drawer'],
                [
                    'branch_id' => $branch->id,
                    'gl_account_id' => $pettyGLAccount?->id,
                    'custodian_id' => $user->id,
                    'limit_amount' => 50000.00,
                    'current_balance' => 0.00,
                    'is_active' => true,
                ]
            );

            // Approval Rules
            $roleManager = Role::where('name', 'branch_manager')->first();
            $roleOwner = Role::where('name', 'owner')->first();

            if ($roleManager) {
                \App\Models\ExpenseApprovalRule::withoutGlobalScope('tenant')->updateOrCreate(
                    ['marquee_id' => $marquee->id, 'branch_id' => $branch->id, 'approver_role_id' => $roleManager->id],
                    [
                        'min_amount' => 0.00,
                        'sequence' => 1,
                    ]
                );
            }

            if ($roleOwner) {
                \App\Models\ExpenseApprovalRule::withoutGlobalScope('tenant')->updateOrCreate(
                    ['marquee_id' => $marquee->id, 'branch_id' => $branch->id, 'approver_role_id' => $roleOwner->id],
                    [
                        'min_amount' => 100000.00,
                        'sequence' => 2,
                    ]
                );
            }

            // 12. Seed active global default masters into new tenant
            $this->seedGlobalDefaultsForTenant($marquee->id);

            // 13. Assign user marquee, branch, and role
            $ownerRole = Role::where('name', 'owner')->first();

            $user->marquee_id = $marquee->id;
            $user->branch_id = $branch->id;
            
            // Assign owner role if the user doesn't already have a manager/owner role
            if (!$user->role_id) {
                $user->role_id = $ownerRole ? $ownerRole->id : null;
            }
            $user->save();
        });

        session()->flash('success', 'Congratulations! Initial business setup completed successfully. Your CMS is ready.');
        return redirect()->route('dashboard');
    }

    /**
     * Clone active global default masters into new tenant setup.
     */
    protected function seedGlobalDefaultsForTenant($marqueeId)
    {
        $globalMasters = \App\Models\GlobalDefaultMaster::active()->get();
        foreach ($globalMasters as $gt) {
            if ($gt->category_type === 'event_type') {
                $extra = $gt->extra_attributes ?? [];
                \App\Models\EventType::withoutGlobalScope('tenant')->withTrashed()->firstOrCreate(
                    ['marquee_id' => $marqueeId, 'event_type_name' => $gt->name],
                    ['event_type_code' => $gt->code, 'description' => $gt->description, 'color_code' => $extra['color'] ?? '#3b82f6', 'status' => 'active']
                );
            } elseif ($gt->category_type === 'menu_category') {
                \App\Models\MenuCategory::withoutGlobalScope('tenant')->withTrashed()->firstOrCreate(
                    ['marquee_id' => $marqueeId, 'category_name' => $gt->name],
                    ['category_code' => $gt->code, 'description' => $gt->description, 'status' => 'Active']
                );
            } elseif ($gt->category_type === 'inventory_category') {
                \App\Models\InventoryCategory::withoutGlobalScope('tenant')->withTrashed()->firstOrCreate(
                    ['marquee_id' => $marqueeId, 'name' => $gt->name],
                    ['code' => $gt->code, 'description' => $gt->description, 'status' => 'Active']
                );
            } elseif ($gt->category_type === 'inventory_unit') {
                $extra = $gt->extra_attributes ?? [];
                $sCode = $extra['short_code'] ?? $gt->code;
                
                $exists = \App\Models\InventoryUnit::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $marqueeId)
                    ->where(function ($query) use ($gt, $sCode) {
                        $query->where('name', $gt->name)
                              ->orWhere('short_code', $sCode);
                    })
                    ->exists();

                if (!$exists) {
                    \App\Models\InventoryUnit::create([
                        'marquee_id' => $marqueeId,
                        'name' => $gt->name,
                        'short_code' => $sCode,
                        'description' => $gt->description,
                        'status' => 'Active'
                    ]);
                }
            } elseif ($gt->category_type === 'expense_category') {
                \App\Models\ExpenseCategory::withoutGlobalScope('tenant')->withTrashed()->firstOrCreate(
                    ['marquee_id' => $marqueeId, 'name' => $gt->name],
                    ['category_code' => $gt->code ?: ('EXP-' . strtoupper(substr(md5($gt->name), 0, 4))), 'description' => $gt->description, 'is_active' => true]
                );
            } elseif ($gt->category_type === 'department_type') {
                $branch = \App\Models\Branch::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $marqueeId)->first();
                if ($branch) {
                    \App\Models\Department::withoutGlobalScope('tenant')->withTrashed()->firstOrCreate(
                        ['marquee_id' => $marqueeId, 'name' => $gt->name],
                        [
                            'branch_id' => $branch->id,
                            'department_code' => $gt->code ?: ('DEP-' . strtoupper(substr(md5($gt->name), 0, 4))),
                            'department_type' => 'Operations',
                            'description' => $gt->description,
                            'status' => 'Active'
                        ]
                    );
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.setup-wizard')
            ->layout('layouts.wizard'); // use the clean, wide custom layout (without sidebars)
    }
}
