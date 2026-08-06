<?php
 
namespace App\Livewire;
 
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Package;
use App\Models\Slot;
use App\Models\ExtraService;
use App\Models\MenuItem;
use App\Services\AvailabilityService;
use App\Services\BookingPricingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BookingWizard extends Component
{
    // Wizard State
    public $currentStep = 1;

    // Step 1: Customer Selection & Quick Add
    public $selectedCustomerId = '';
    public $customerSearch = '';
    public $showQuickCustomerModal = false;
    
    // Quick Customer Form Fields
    public $newCustomerType = 'Individual';
    public $newFirstName = '';
    public $newLastName = '';
    public $newCompanyName = '';
    public $newGender = '';
    public $newEmail = '';
    public $newPhone = '';
    public $newCNIC = '';
    public $newCity = 'Lahore';
    public $newProvince = 'Punjab';

    // Step 2: Event Details
    public $selectedEventTypeId = '';
    public $selectedHallId = '';
    public $selectedDate = '';

    // Step 3: Shift / Slot Selection
    public $checkType = 'slot'; // slot, custom
    public $selectedSlotId = '';
    public $customStart = '';
    public $customEnd = '';
    
    // Auto-loaded Time Bounds
    public $startTime = '';
    public $endTime = '';

    // Availability Result State
    public $isAvailable = false;
    public $availabilityChecked = false;
    public $conflictDetails = null;
    public $availableSlotsList = [];

    // Step 4: Package & Pricing
    public $selectedPackageId = '';
    public $guestCount = 100;
    public $perPlatePrice = 0.00;
    public $hallCharges = 0.00;
    public $extraCharges = 0.00;
    public $discountAmount = 0.00;
    public $securityDeposit = 0.00;
    public $taxRate = 13.00;

    // Add-on selection properties
    public $addonsList = [];
    public $selectedAddons = []; // extra_service_id => ['selected' => bool, 'price' => float, 'quantity' => int, 'name' => string]

    // Menu Customization selection
    public $bookingMenuItems = []; // array of ['id' => int, 'item_name' => string, 'custom_note' => string, 'managed_by_host' => bool]
    public $selectedMenuItemToAdd = ''; // dropdown selection to add menu item
    public $menuItemsAutocomplete = []; // list of all menu items for the dropdown
    public $menuItemSearch = '';

    // Search and Multi-select states
    public $eventTypeSearch = '';
    public $hallSearch = '';
    public $selectedHallIds = [];
    public $filteredEventTypes = [];
    public $filteredHalls = [];

    // Rent / Sitting Plan only state
    public $noFood = false;

    // Pricing calculation outputs
    public $packageAmount = 0.00;
    public $subtotal = 0.00;
    public $taxAmount = 0.00;
    public $grandTotal = 0.00;

    // Step 5: Review
    public $specialInstructions = '';
    public $bookingStatus = 'Reserved'; // Reserved, Confirmed, Draft

    // Cache lists
    public $customersList = [];
    public $eventTypesList = [];
    public $hallsList = [];
    public $packagesList = [];

    // Quick customer additional additions
    public $newNTN = '';
    public $newReferralName = '';
    public $newReferralContact = '';

    // Constants for Quick Customer
    public $cities = ['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta', 'Gujranwala', 'Sialkot'];
    public $provinces = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad Capital Territory'];

    public function mount()
    {
        $this->selectedDate = Carbon::today()->addDay()->format('Y-m-d');
        $this->loadDropdowns();
    }

    public function loadDropdowns()
    {
        $marqueeId = auth()->user()->marquee_id;

        $this->eventTypesList = EventType::where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('sort_order')
            ->get();

        $this->hallsList = Hall::where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('hall_name')
            ->get();

        $this->packagesList = Package::where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('package_name')
            ->get();

        // Load active extra services catalog
        $this->addonsList = ExtraService::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('service_name')
            ->get();

        // Initialize selectedAddons
        $this->selectedAddons = [];
        foreach ($this->addonsList as $addon) {
            $this->selectedAddons[$addon->id] = [
                'selected' => false,
                'price' => $addon->default_price,
                'quantity' => 1,
                'name' => $addon->service_name,
            ];
        }

        // Load all menu items for autocomplete/addition dropdown
        $this->menuItemsAutocomplete = MenuItem::where('marquee_id', $marqueeId)
            ->orderBy('item_name')
            ->get();

        $this->filteredEventTypes = $this->eventTypesList->toArray();
        $this->filteredHalls = $this->hallsList->toArray();

        if ($this->hallsList->isNotEmpty() && empty($this->selectedHallIds)) {
            $this->selectedHallIds = [(string)$this->hallsList->first()->id];
            $this->selectedHallId = $this->selectedHallIds[0];
        }

        $this->searchCustomers();
    }

    public function searchCustomers()
    {
        $marqueeId = auth()->user()->marquee_id;

        $query = Customer::where('marquee_id', $marqueeId)
            ->where('status', 'Active');

        if (!empty($this->customerSearch)) {
            $term = '%' . $this->customerSearch . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('phone_number', 'like', $term)
                  ->orWhere('customer_code', 'like', $term);
            });
        }

        $this->customersList = $query->orderBy('first_name')->limit(10)->get();
    }

    public function updatedCustomerSearch()
    {
        $this->searchCustomers();
    }

    public function selectCustomer($id)
    {
        $this->selectedCustomerId = $id;
        $customer = Customer::find($id);
        if ($customer) {
            $this->customerSearch = $customer->full_name . ' (' . $customer->customer_code . ')';
        }
    }

    /**
     * Create a customer quick-add flow.
     */
    public function createCustomer()
    {
        $this->newPhone = str_replace(['-', ' '], '', $this->newPhone);
        if ($this->newReferralContact) {
            $this->newReferralContact = str_replace(['-', ' '], '', $this->newReferralContact);
        }

        $marqueeId = auth()->user()->marquee_id;

        $this->validate([
            'newFirstName' => 'required|string|max:255',
            'newLastName' => 'required|string|max:255',
            'newCustomerType' => 'required|in:Individual,Corporate',
            'newCompanyName' => 'required_if:newCustomerType,Corporate|nullable|string|max:255',
            'newPhone' => 'required|string|max:20',
            'newCNIC' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\d{5}-\d{7}-\d{1}$/',
                \Illuminate\Validation\Rule::unique('customers', 'cnic_national_id')
                    ->where('marquee_id', $marqueeId)
                    ->whereNull('deleted_at'),
            ],
            'newEmail' => [
                'nullable',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('customers', 'email')
                    ->where('marquee_id', $marqueeId)
                    ->whereNull('deleted_at'),
            ],
            'newCity' => 'required|string',
            'newProvince' => 'required|string',
            'newNTN' => 'nullable|string|max:50',
            'newReferralName' => 'nullable|string|max:255',
            'newReferralContact' => 'nullable|string|max:50',
        ], [
            'newCNIC.regex' => 'The CNIC format must be XXXXX-XXXXXXX-X.',
            'newCNIC.unique' => 'This CNIC is already registered in your Marquee database.',
            'newEmail.unique' => 'This email is already registered in your Marquee database.',
            'newCompanyName.required_if' => 'The company name field is required for Corporate customers.',
        ]);

        $marqueeId = auth()->user()->marquee_id;

        $customer = Customer::create([
            'marquee_id' => $marqueeId,
            'customer_type' => $this->newCustomerType,
            'first_name' => $this->newFirstName,
            'last_name' => $this->newLastName,
            'company_name' => $this->newCustomerType === 'Corporate' ? $this->newCompanyName : null,
            'gender' => $this->newGender ?: null,
            'email' => $this->newEmail ?: null,
            'phone_number' => $this->newPhone,
            'cnic_national_id' => $this->newCNIC ?: null,
            'city' => $this->newCity,
            'province' => $this->newProvince,
            'status' => 'Active',
            'ntn_number' => $this->newNTN ?: null,
            'referred_by_name' => $this->newReferralName ?: null,
            'referred_by_contact' => $this->newReferralContact ?: null,
        ]);

        // Auto select
        $this->selectedCustomerId = $customer->id;
        $this->customerSearch = $customer->full_name . ' (' . $customer->customer_code . ')';
        
        // Reset fields
        $this->resetQuickCustomerForm();
        $this->showQuickCustomerModal = false;
        
        $this->searchCustomers();
        session()->flash('success', 'Customer profile created and selected successfully.');
    }

    private function resetQuickCustomerForm()
    {
        $this->newCustomerType = 'Individual';
        $this->newFirstName = '';
        $this->newLastName = '';
        $this->newCompanyName = '';
        $this->newGender = '';
        $this->newEmail = '';
        $this->newPhone = '';
        $this->newCNIC = '';
        $this->newCity = 'Lahore';
        $this->newNTN = '';
        $this->newReferralName = '';
        $this->newReferralContact = '';
        $this->newProvince = 'Punjab';
    }

    public function updatedSelectedHallId()
    {
        $this->resetSlotState();
    }

    public function updatedSelectedDate()
    {
        $this->resetSlotState();
    }

    public function updatedCheckType()
    {
        $this->resetSlotState();
    }

    private function resetSlotState()
    {
        $this->selectedSlotId = '';
        $this->customStart = '';
        $this->customEnd = '';
        $this->startTime = '';
        $this->endTime = '';
        $this->isAvailable = false;
        $this->availabilityChecked = false;
        $this->conflictDetails = null;
    }

    public function updatedEventTypeSearch()
    {
        if (empty($this->eventTypeSearch)) {
            $this->filteredEventTypes = $this->eventTypesList->toArray();
        } else {
            $term = '%' . $this->eventTypeSearch . '%';
            $this->filteredEventTypes = EventType::where('marquee_id', auth()->user()->marquee_id)
                ->whereIn('status', ['active', 'Active'])
                ->where('event_type_name', 'like', $term)
                ->orderBy('sort_order')
                ->get()
                ->toArray();
        }
    }

    public function selectEventType($id, $name)
    {
        $this->selectedEventTypeId = $id;
        $this->eventTypeSearch = $name;
    }

    public function updatedHallSearch()
    {
        if (empty($this->hallSearch)) {
            $this->filteredHalls = $this->hallsList->toArray();
        } else {
            $term = '%' . $this->hallSearch . '%';
            $this->filteredHalls = Hall::where('marquee_id', auth()->user()->marquee_id)
                ->whereIn('status', ['active', 'Active'])
                ->where('hall_name', 'like', $term)
                ->orderBy('hall_name')
                ->get()
                ->toArray();
        }
    }

    public function toggleHall($id)
    {
        $id = (string)$id;
        if (in_array($id, $this->selectedHallIds)) {
            $this->selectedHallIds = array_diff($this->selectedHallIds, [$id]);
        } else {
            $this->selectedHallIds[] = $id;
        }
        $this->selectedHallIds = array_values($this->selectedHallIds);
        $this->selectedHallId = reset($this->selectedHallIds) ?: '';
        
        $this->resetSlotState();
    }

    /**
     * Loads dynamic slot choices and performs initial analysis.
     */
    public function loadSlotsAndCheck()
    {
        if (empty($this->selectedHallIds) || empty($this->selectedDate)) {
            return;
        }

        $service = new AvailabilityService();
        
        // Query active slots assigned to this marquee
        $marqueeId = auth()->user()->marquee_id;
        $slots = Slot::where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('start_time')
            ->get();

        $this->availableSlotsList = [];

        foreach ($slots as $slot) {
            $isSlotAvailable = true;
            foreach ($this->selectedHallIds as $hallId) {
                if (!$service->checkAvailability($hallId, $this->selectedDate, $slot->start_time, $slot->end_time)) {
                    $isSlotAvailable = false;
                    break;
                }
            }

            $this->availableSlotsList[] = [
                'id' => $slot->id,
                'name' => $slot->slot_name,
                'start' => Carbon::parse($slot->start_time)->format('h:i A'),
                'end' => Carbon::parse($slot->end_time)->format('h:i A'),
                'is_available' => $isSlotAvailable,
            ];
        }
    }

    public function updatedSelectedSlotId()
    {
        if (empty($this->selectedSlotId)) {
            $this->resetSlotState();
            return;
        }

        $slot = Slot::findOrFail($this->selectedSlotId);
        $this->customStart = Carbon::parse($slot->start_time)->format('H:i');
        $this->customEnd = Carbon::parse($slot->end_time)->format('H:i');
        
        $this->calculateTimes();
        $this->runAvailabilityCheck();
    }

    public function updatedCustomStart()
    {
        $this->calculateTimes();
        $this->runAvailabilityCheck();
    }

    public function updatedCustomEnd()
    {
        $this->calculateTimes();
        $this->runAvailabilityCheck();
    }

    private function calculateTimes()
    {
        if (empty($this->selectedDate) || empty($this->customStart) || empty($this->customEnd)) {
            return;
        }

        $startStr = $this->selectedDate . ' ' . $this->customStart . ':00';
        $endStr = $this->selectedDate . ' ' . $this->customEnd . ':00';

        $start = Carbon::parse($startStr);
        $end = Carbon::parse($endStr);

        // Midnight crossing logic: if end time is earlier than start time, it ends next day
        if ($end->lt($start)) {
            $end = $end->addDay();
        }

        $this->startTime = $start->format('Y-m-d H:i:s');
        $this->endTime = $end->format('Y-m-d H:i:s');
    }

    private function runAvailabilityCheck()
    {
        if (empty($this->selectedHallIds) || empty($this->selectedDate) || empty($this->startTime) || empty($this->endTime)) {
            $this->availabilityChecked = false;
            return;
        }

        $service = new AvailabilityService();
        $this->isAvailable = true;
        $this->conflictDetails = null;

        foreach ($this->selectedHallIds as $hallId) {
            $conflicting = $service->getConflictingBooking(
                $hallId,
                $this->selectedDate,
                Carbon::parse($this->startTime)->format('H:i:s'),
                Carbon::parse($this->endTime)->format('H:i:s')
            );

            if ($conflicting) {
                $this->isAvailable = false;
                $this->conflictDetails = $conflicting;
                break;
            }
        }

        $this->availabilityChecked = true;
    }

    public function updatedSelectedPackageId()
    {
        if (empty($this->selectedPackageId)) {
            $this->perPlatePrice = 0.00;
            $this->bookingMenuItems = [];
            $this->recalculatePrices();
            return;
        }

        $package = Package::with('menuItems')->findOrFail($this->selectedPackageId);
        $this->perPlatePrice = $package->per_plate_price;
        $this->guestCount = $package->minimum_guests ?: 100;
        
        // Auto default security deposit to package flat base price or custom rate
        $this->securityDeposit = 15000.00; // Standard security deposit default

        // Copy package menu items to booking level for customization
        $this->bookingMenuItems = [];
        foreach ($package->menuItems as $item) {
            $this->bookingMenuItems[] = [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'urdu_name' => $item->urdu_name,
                'custom_note' => '',
                'managed_by_host' => false,
            ];
        }

        $this->recalculatePrices();
    }

    public function updatedSelectedAddons()
    {
        $this->recalculatePrices();
    }

    public function updatedMenuItemSearch()
    {
        $marqueeId = auth()->user()->marquee_id;
        if (empty($this->menuItemSearch)) {
            $this->menuItemsAutocomplete = MenuItem::with('category')->where('marquee_id', $marqueeId)
                ->orderBy('item_name')
                ->get();
        } else {
            $term = '%' . $this->menuItemSearch . '%';
            $this->menuItemsAutocomplete = MenuItem::with('category')->where('marquee_id', $marqueeId)
                ->where('item_name', 'like', $term)
                ->orderBy('item_name')
                ->get();
        }
    }

    public function selectMenuItem($id)
    {
        $item = MenuItem::find($id);
        if ($item) {
            // Check for duplicates
            $exists = false;
            foreach ($this->bookingMenuItems as $existing) {
                if ($existing['id'] == $item->id) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $this->bookingMenuItems[] = [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'urdu_name' => $item->urdu_name,
                    'custom_note' => '',
                    'managed_by_host' => $this->noFood ? true : false,
                ];
            }
        }
        $this->menuItemSearch = '';
        $this->updatedMenuItemSearch();
    }

    public function addMenuItem()
    {
        if (empty($this->selectedMenuItemToAdd)) {
            return;
        }

        $this->selectMenuItem($this->selectedMenuItemToAdd);
        $this->selectedMenuItemToAdd = '';
    }

    public function removeMenuItem($index)
    {
        if (isset($this->bookingMenuItems[$index])) {
            unset($this->bookingMenuItems[$index]);
            $this->bookingMenuItems = array_values($this->bookingMenuItems); // Reset keys
        }
    }

    public function moveMenuItemUp($index)
    {
        if ($index > 0 && isset($this->bookingMenuItems[$index])) {
            $temp = $this->bookingMenuItems[$index - 1];
            $this->bookingMenuItems[$index - 1] = $this->bookingMenuItems[$index];
            $this->bookingMenuItems[$index] = $temp;
        }
    }

    public function moveMenuItemDown($index)
    {
        if ($index < count($this->bookingMenuItems) - 1 && isset($this->bookingMenuItems[$index])) {
            $temp = $this->bookingMenuItems[$index + 1];
            $this->bookingMenuItems[$index + 1] = $this->bookingMenuItems[$index];
            $this->bookingMenuItems[$index] = $temp;
        }
    }

    public function updatedGuestCount()
    {
        $this->recalculatePrices();
    }

    public function updatedPerPlatePrice()
    {
        $this->recalculatePrices();
    }

    public function updatedHallCharges()
    {
        $this->recalculatePrices();
    }

    public function updatedExtraCharges()
    {
        $this->recalculatePrices();
    }

    public function updatedDiscountAmount()
    {
        $this->recalculatePrices();
    }

    public function updatedSecurityDeposit()
    {
        $this->recalculatePrices();
    }

    public function updatedTaxRate()
    {
        $this->recalculatePrices();
    }

    public function recalculatePrices()
    {
        if ($this->noFood) {
            $this->perPlatePrice = 0.00;
            $this->selectedPackageId = '';
            $this->bookingMenuItems = array_map(function($item) {
                $item['managed_by_host'] = true;
                return $item;
            }, $this->bookingMenuItems);
        }

        // Calculate the sum of selected extra services (add-ons)
        $addonsSum = 0.00;
        foreach ($this->selectedAddons as $addonId => $addon) {
            if (!empty($addon['selected'])) {
                $price = is_numeric($addon['price']) ? floatval($addon['price']) : 0.00;
                $quantity = is_numeric($addon['quantity']) ? intval($addon['quantity']) : 1;
                $addonsSum += $price * $quantity;
            }
        }
        $this->extraCharges = $addonsSum;

        // Typecast/sanitize inputs to numeric types to prevent TypeError in number_format()
        $this->guestCount = is_numeric($this->guestCount) ? intval($this->guestCount) : 0;
        $this->perPlatePrice = is_numeric($this->perPlatePrice) ? floatval($this->perPlatePrice) : 0.00;
        $this->hallCharges = is_numeric($this->hallCharges) ? floatval($this->hallCharges) : 0.00;
        $this->extraCharges = is_numeric($this->extraCharges) ? floatval($this->extraCharges) : 0.00;
        $this->discountAmount = is_numeric($this->discountAmount) ? floatval($this->discountAmount) : 0.00;
        $this->securityDeposit = is_numeric($this->securityDeposit) ? floatval($this->securityDeposit) : 0.00;
        $this->taxRate = is_numeric($this->taxRate) ? floatval($this->taxRate) : 0.00;

        $pricing = BookingPricingService::calculate([
            'guest_count' => $this->guestCount,
            'per_plate_price' => $this->perPlatePrice,
            'hall_charges' => $this->hallCharges,
            'extra_charges' => $this->extraCharges,
            'discount_amount' => $this->discountAmount,
            'security_deposit' => $this->securityDeposit,
            'tax_rate' => $this->taxRate,
        ]);

        $this->packageAmount = $pricing['package_amount'];
        $this->subtotal = $pricing['subtotal'];
        $this->taxAmount = $pricing['tax_amount'];
        $this->grandTotal = $pricing['grand_total'];
    }

    public function nextStep()
    {
        if ($this->currentStep === 1) {
            $this->validate([
                'selectedCustomerId' => 'required|exists:customers,id'
            ], [
                'selectedCustomerId.required' => 'You must select or add a customer to proceed.'
            ]);
        } elseif ($this->currentStep === 2) {
            $this->validate([
                'selectedEventTypeId' => 'required|exists:event_types,id',
                'selectedHallIds' => 'required|array|min:1',
                'selectedDate' => 'required|date|after_or_equal:today',
            ], [
                'selectedHallIds.required' => 'You must select at least one hall.',
                'selectedHallIds.min' => 'You must select at least one hall.',
            ]);
            $this->loadSlotsAndCheck();
        } elseif ($this->currentStep === 3) {
            $this->calculateTimes();
            $this->runAvailabilityCheck();

            $this->validate([
                'startTime' => 'required',
                'endTime' => 'required',
            ]);

            if (!$this->isAvailable) {
                $this->addError('availability', 'The selected hall is not available during this time. Please choose another slot or time range.');
                return;
            }
        } elseif ($this->currentStep === 4) {
            $rules = [
                'guestCount' => 'required|integer|min:1',
                'perPlatePrice' => 'required|numeric|min:0',
                'hallCharges' => 'required|numeric|min:0',
                'extraCharges' => 'required|numeric|min:0',
                'discountAmount' => 'required|numeric|min:0',
                'securityDeposit' => 'required|numeric|min:0',
                'taxRate' => 'required|numeric|min:0',
            ];

            if (!$this->noFood) {
                $rules['selectedPackageId'] = 'required|exists:packages,id';
            }

            $this->validate($rules);

            if (!$this->noFood) {
                // Package guest bounds warning check
                $package = Package::find($this->selectedPackageId);
                if ($package) {
                    if ($this->guestCount < $package->minimum_guests) {
                        session()->flash('warning', "Warning: Headcount ({$this->guestCount}) is below the minimum limit ({$package->minimum_guests}) of this package.");
                    } elseif ($package->maximum_guests && $this->guestCount > $package->maximum_guests) {
                        session()->flash('warning', "Warning: Headcount ({$this->guestCount}) exceeds the maximum limit ({$package->maximum_guests}) of this package.");
                    }
                }
            }
        }

        $this->currentStep++;
    }

    public function prevStep()
    {
        $this->currentStep--;
        if ($this->currentStep === 3) {
            $this->loadSlotsAndCheck();
        }
    }

    /**
     * Final submission: Create booking inside transaction.
     */
    public function submitBooking()
    {
        $rules = [
            'selectedCustomerId' => 'required|exists:customers,id',
            'selectedEventTypeId' => 'required|exists:event_types,id',
            'selectedHallIds' => 'required|array|min:1',
            'selectedDate' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
            'guestCount' => 'required|integer|min:1',
            'perPlatePrice' => 'required|numeric|min:0',
            'hallCharges' => 'required|numeric|min:0',
            'extraCharges' => 'required|numeric|min:0',
            'discountAmount' => 'required|numeric|min:0',
            'securityDeposit' => 'required|numeric|min:0',
            'taxRate' => 'required|numeric|min:0',
            'bookingStatus' => 'required|in:Draft,Reserved,Confirmed',
        ];

        if (!$this->noFood) {
            $rules['selectedPackageId'] = 'required|exists:packages,id';
        }

        $this->validate($rules);

        $marqueeId = auth()->user()->marquee_id;
        $userId = auth()->id();

        // Perform transactional creation and final double-booking check
        try {
            $booking = DB::transaction(function () use ($marqueeId, $userId) {
                $service = new AvailabilityService();
                
                // 1. Lock existing bookings to prevent race conditions & 2. Final check inside transaction
                foreach ($this->selectedHallIds as $hId) {
                    DB::table('bookings')
                        ->where('marquee_id', $marqueeId)
                        ->where('hall_id', $hId)
                        ->where('booking_date', $this->selectedDate)
                        ->lockForUpdate()
                        ->get();

                    $isStillAvailable = $service->checkAvailability(
                        $hId,
                        $this->selectedDate,
                        Carbon::parse($this->startTime)->format('H:i:s'),
                        Carbon::parse($this->endTime)->format('H:i:s')
                    );

                    if (!$isStillAvailable) {
                        $hallModel = Hall::find($hId);
                        $hallName = $hallModel ? $hallModel->hall_name : 'Hall';
                        throw new \Exception("Double-booking prevented: The selected hall ({$hallName}) was just booked by another operator.");
                    }
                }

                // 3. Create the Booking
                $newBooking = Booking::create([
                    'marquee_id' => $marqueeId,
                    'customer_id' => $this->selectedCustomerId,
                    'event_type_id' => $this->selectedEventTypeId,
                    'hall_id' => reset($this->selectedHallIds), // primary hall
                    'slot_id' => $this->selectedSlotId ?: null,
                    'package_id' => $this->noFood ? null : $this->selectedPackageId,
                    'booking_date' => $this->selectedDate,
                    'start_time' => $this->startTime,
                    'end_time' => $this->endTime,
                    'guest_count' => $this->guestCount,
                    'per_plate_price' => $this->perPlatePrice,
                    'package_amount' => $this->packageAmount,
                    'hall_charges' => $this->hallCharges,
                    'extra_charges' => $this->extraCharges,
                    'discount_amount' => $this->discountAmount,
                    'security_deposit' => $this->securityDeposit,
                    'tax_amount' => $this->taxAmount,
                    'subtotal' => $this->subtotal,
                    'grand_total' => $this->grandTotal,
                    'special_instructions' => $this->specialInstructions ?: null,
                    'booking_status' => $this->bookingStatus,
                    'payment_status' => 'Unpaid',
                    'created_by' => $userId,
                    'deposit_status' => 'Held',
                    'no_food' => $this->noFood,
                ]);

                // 3.5 Sync allocated halls
                $newBooking->halls()->sync($this->selectedHallIds);

                // 4. Save selected extra services (add-ons)
                foreach ($this->selectedAddons as $addonId => $addon) {
                    if (!empty($addon['selected'])) {
                        $price = floatval($addon['price']);
                        $qty = intval($addon['quantity']);
                        \App\Models\BookingExtraService::create([
                            'booking_id' => $newBooking->id,
                            'extra_service_id' => $addonId,
                            'service_name' => $addon['name'],
                            'unit_price' => $price,
                            'quantity' => $qty,
                            'total_price' => $price * $qty,
                        ]);
                    }
                }

                // 5. Save customized menu items
                foreach ($this->bookingMenuItems as $index => $menuItem) {
                    \App\Models\BookingMenuItem::create([
                        'booking_id' => $newBooking->id,
                        'menu_item_id' => $menuItem['id'],
                        'custom_note' => $menuItem['custom_note'] ?: null,
                        'managed_by_host' => !empty($menuItem['managed_by_host']),
                        'sort_order' => $index,
                    ]);
                }

                // 6. Create history record
                BookingHistory::create([
                    'booking_id' => $newBooking->id,
                    'user_id' => $userId,
                    'status_from' => null,
                    'status_to' => $this->bookingStatus,
                    'payment_status_from' => null,
                    'payment_status_to' => 'Unpaid',
                    'notes' => 'Booking created via Booking Wizard.',
                ]);

                return $newBooking;
            });

            session()->flash('success', 'Booking #' . $booking->booking_number . ' has been registered successfully.');
            return redirect()->route('bookings.show', $booking->id);

        } catch (\Exception $e) {
            $this->addError('submission', $e->getMessage());
            return;
        }
    }

    public function render()
    {
        $this->recalculatePrices();
        return view('livewire.booking-wizard');
    }
}
