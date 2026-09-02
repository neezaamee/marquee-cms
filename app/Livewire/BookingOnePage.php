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

class BookingOnePage extends Component
{
    public $marquee_id = null;

    // Branch state
    public $selectedBranchId = '';
    public $branchesList = [];
    public $isMultiBranchUser = false;
    public $autoSelectedBranch = null;

    // Search & Selection state
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
    public $newNTN = '';
    public $newReferralName = '';
    public $newReferralContact = '';

    // Event Details
    public $selectedEventTypeId = '';
    public $selectedHallId = '';
    public $selectedDate = '';
    public $selectedHallIds = [];

    // Shift / Slot Selection
    public $checkType = 'slot'; // slot, custom
    public $selectedSlotId = '';
    public $customStart = '';
    public $customEnd = '';
    public $startTime = '';
    public $endTime = '';

    // Availability Result State
    public $isAvailable = false;
    public $availabilityChecked = false;
    public $conflictDetails = null;
    public $availableSlotsList = [];

    // Package & Pricing
    public $selectedPackageId = '';
    public $guestCount = 100;
    public $tentativeGuests = 100;
    public $confirmedGuests = null;
    public $guestStatus = 'Tentative';
    public $perPlatePrice = 0.00;
    public $hallCharges = 0.00;
    public $extraCharges = 0.00;
    public $discountAmount = 0.00;
    public $securityDeposit = 0.00;
    public $taxRate = 13.00;

    // Add-ons list
    public $addonsList = [];
    public $selectedAddons = []; // extra_service_id => ['selected' => bool, 'price' => float, 'quantity' => int, 'name' => string]

    // Menu Customization selection
    public $bookingMenuItems = [];
    public $selectedMenuItemToAdd = '';
    public $menuItemsAutocomplete = [];
    public $menuItemSearch = '';

    public $noFood = false;

    // Privacy / Partition properties
    public $privacyRequired = false;
    public $privacyLadiesPercentage = '';
    public $privacyGentsPercentage = '';

    public function updatedPrivacyRequired($value)
    {
        if (!$value) {
            $this->privacyLadiesPercentage = '';
            $this->privacyGentsPercentage = '';
        }
    }

    // Pricing calculation outputs
    public $packageAmount = 0.00;
    public $subtotal = 0.00;
    public $taxAmount = 0.00;
    public $grandTotal = 0.00;

    public $specialInstructions = '';
    public $bookingStatus = 'Reserved'; // Reserved, Confirmed, Draft

    // Cache lists
    public $customersList = [];
    public $eventTypesList = [];
    public $hallsList = [];
    public $packagesList = [];

    public $cities = ['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta', 'Gujranwala', 'Sialkot'];
    public $provinces = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad Capital Territory'];

    public function mount()
    {
        $this->selectedDate = Carbon::today()->addDay()->format('Y-m-d');
        
        $user = auth()->user();
        $marqueeId = $user ? $user->getActiveMarqueeId() : null;
        $this->marquee_id = $marqueeId;
        $accessibleBranches = $user ? $user->getAccessibleBranches($marqueeId) : collect();
        $this->branchesList = $accessibleBranches;

        if ($accessibleBranches->count() === 1) {
            $this->selectedBranchId = (string) $accessibleBranches->first()->id;
            $this->autoSelectedBranch = $accessibleBranches->first();
            $this->isMultiBranchUser = false;
        } elseif ($accessibleBranches->count() > 1) {
            $this->isMultiBranchUser = true;
            if ($user && $user->branch_id && $user->hasAccessToBranch($user->branch_id, $marqueeId)) {
                $this->selectedBranchId = (string) $user->branch_id;
            } else {
                $headOffice = $accessibleBranches->firstWhere('is_head_office', true);
                $this->selectedBranchId = (string) ($headOffice ? $headOffice->id : $accessibleBranches->first()->id);
            }
        }

        // Load dynamic tax rate from selected branch
        if ($this->selectedBranchId) {
            $branch = \App\Models\Branch::find($this->selectedBranchId);
            if ($branch && $branch->tax_rate !== null) {
                $this->taxRate = (float) $branch->tax_rate;
            }
        }

        $this->loadDropdowns();
    }

    public function updatedSelectedBranchId($value)
    {
        $marqueeId = auth()->user()->getActiveMarqueeId();
        $this->selectedHallIds = [];
        $this->selectedHallId = '';

        if (!empty($value)) {
            $branch = \App\Models\Branch::find($value);
            if ($branch && $branch->tax_rate !== null) {
                $this->taxRate = (float) $branch->tax_rate;
            }

            $this->hallsList = Hall::where('marquee_id', $marqueeId)
                ->where('branch_id', $value)
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('hall_name')
                ->get();

            if ($this->hallsList->isNotEmpty()) {
                $this->selectedHallIds = [(string) $this->hallsList->first()->id];
                $this->selectedHallId = $this->selectedHallIds[0];
                $this->hallCharges = (float) $this->hallsList->first()->default_booking_price;
            } else {
                $this->hallCharges = 0.00;
            }
        } else {
            $this->hallsList = collect();
            $this->hallCharges = 0.00;
        }

        $this->loadSlotsAndCheck();
        $this->recalculatePrices();
    }

    public function loadDropdowns()
    {
        $marqueeId = auth()->user()->getActiveMarqueeId();

        $this->eventTypesList = EventType::where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('sort_order')
            ->get();

        if (!empty($this->selectedBranchId)) {
            $this->hallsList = Hall::where('marquee_id', $marqueeId)
                ->where('branch_id', $this->selectedBranchId)
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('hall_name')
                ->get();
        } else {
            $this->hallsList = collect();
        }

        $this->packagesList = Package::where('marquee_id', $marqueeId)
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('package_name')
            ->get();

        $this->addonsList = ExtraService::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('service_name')
            ->get();

        $this->selectedAddons = [];
        foreach ($this->addonsList as $addon) {
            $this->selectedAddons[$addon->id] = [
                'selected' => false,
                'price' => $addon->default_price,
                'quantity' => 1,
                'name' => $addon->service_name,
            ];
        }

        $this->menuItemsAutocomplete = MenuItem::where('marquee_id', $marqueeId)
            ->orderBy('item_name')
            ->get();

        if ($this->hallsList->isNotEmpty() && empty($this->selectedHallIds)) {
            $this->selectedHallIds = [(string)$this->hallsList->first()->id];
            $this->selectedHallId = $this->selectedHallIds[0];
            $this->hallCharges = (float) $this->hallsList->first()->default_booking_price;
        }

        $this->searchCustomers();
        $this->loadSlotsAndCheck();
    }

    public function searchCustomers()
    {
        $marqueeId = auth()->user()->getActiveMarqueeId();

        $query = Customer::where('marquee_id', $marqueeId)
            ->where('status', 'Active');

        if (!empty($this->customerSearch)) {
            $term = '%' . $this->customerSearch . '%';
            $cleanDigits = preg_replace('/[^0-9]/', '', $this->customerSearch);
            $query->where(function ($q) use ($term, $cleanDigits) {
                $q->where('first_name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('customer_code', 'like', $term);
                  
                if (!empty($cleanDigits)) {
                    $q->orWhere('phone_number', 'like', '%' . $cleanDigits . '%');
                }
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

    public function createCustomer()
    {
        $this->newPhone = str_replace(['-', ' '], '', $this->newPhone);
        if ($this->newReferralContact) {
            $this->newReferralContact = str_replace(['-', ' '], '', $this->newReferralContact);
        }

        $user = auth()->user();
        $marqueeId = $this->marquee_id ?: ($user ? $user->getActiveMarqueeId() : null);

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
            'newCNIC.unique' => 'This CNIC is already registered.',
            'newEmail.unique' => 'This email is already registered.',
        ]);

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

        $this->selectedCustomerId = $customer->id;
        $this->customerSearch = $customer->full_name . ' (' . $customer->customer_code . ')';
        
        $this->resetQuickCustomerForm();
        $this->showQuickCustomerModal = false;
        
        $this->searchCustomers();
        session()->flash('success', 'Customer profile created and selected.');
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
        $this->loadSlotsAndCheck();
    }

    public function updatedSelectedDate()
    {
        $this->resetSlotState();
        $this->loadSlotsAndCheck();
    }

    public function updatedCheckType()
    {
        $this->resetSlotState();
        $this->loadSlotsAndCheck();
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

    public function loadSlotsAndCheck()
    {
        if (empty($this->selectedHallIds) || empty($this->selectedDate)) {
            $this->availableSlotsList = [];
            return;
        }

        $service = new AvailabilityService();
        $user = auth()->user();

        $selectedHalls = Hall::with(['slots' => function ($q) {
            $q->whereIn('slots.status', ['active', 'Active']);
        }])->whereIn('id', $this->selectedHallIds)->get();

        $marqueeId = $selectedHalls->first()?->marquee_id ?: ($this->marquee_id ?: ($user ? $user->getActiveMarqueeId() : null));

        // Check if selected halls have specific assigned slots configured
        $assignedSlotIds = collect();
        $hasSpecificAssignments = false;

        foreach ($selectedHalls as $hall) {
            $hallSlotIds = $hall->slots->pluck('id');
            if ($hallSlotIds->isNotEmpty()) {
                $hasSpecificAssignments = true;
                if ($assignedSlotIds->isEmpty()) {
                    $assignedSlotIds = $hallSlotIds;
                } else {
                    $assignedSlotIds = $assignedSlotIds->intersect($hallSlotIds);
                }
            }
        }

        if ($hasSpecificAssignments && $assignedSlotIds->isNotEmpty()) {
            $slots = Slot::whereIn('id', $assignedSlotIds)
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('start_time')
                ->get();
        } else {
            $slots = Slot::where('marquee_id', $marqueeId)
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('start_time')
                ->get();
        }

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
        if (empty($this->selectedDate) || empty($this->customStart) || empty($this->customEnd)) return;

        $startStr = $this->selectedDate . ' ' . $this->customStart . ':00';
        $endStr = $this->selectedDate . ' ' . $this->customEnd . ':00';

        $start = Carbon::parse($startStr);
        $end = Carbon::parse($endStr);

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
        $this->securityDeposit = 15000.00;

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
        $user = auth()->user();
        $marqueeId = $this->marquee_id ?: ($user ? $user->getActiveMarqueeId() : null);
        if (empty($this->menuItemSearch)) {
            $this->menuItemsAutocomplete = MenuItem::with('category')->where('marquee_id', $marqueeId)->orderBy('item_name')->get();
        } else {
            $term = '%' . $this->menuItemSearch . '%';
            $this->menuItemsAutocomplete = MenuItem::with('category')->where('marquee_id', $marqueeId)
                ->where('item_name', 'like', $term)->orderBy('item_name')->get();
        }
    }

    public function selectMenuItem($id)
    {
        $item = MenuItem::find($id);
        if ($item) {
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

    public function removeMenuItem($index)
    {
        if (isset($this->bookingMenuItems[$index])) {
            unset($this->bookingMenuItems[$index]);
            $this->bookingMenuItems = array_values($this->bookingMenuItems);
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

    public function updatedTentativeGuests() { $this->syncGuestCounts(); $this->recalculatePrices(); }
    public function updatedConfirmedGuests() { $this->syncGuestCounts(); $this->recalculatePrices(); }

    public function syncGuestCounts()
    {
        $tentative = is_numeric($this->tentativeGuests) && intval($this->tentativeGuests) > 0
            ? intval($this->tentativeGuests)
            : (is_numeric($this->guestCount) && intval($this->guestCount) > 0 ? intval($this->guestCount) : 100);

        $confirmed = (is_numeric($this->confirmedGuests) && intval($this->confirmedGuests) > 0) ? intval($this->confirmedGuests) : null;

        $this->tentativeGuests = $tentative;
        $this->confirmedGuests = $confirmed;

        if (!is_null($confirmed)) {
            $this->guestCount = $confirmed;
            $this->guestStatus = 'Confirmed';

            if ($confirmed > $tentative) {
                session()->flash('warning', "Notice: Confirmed guests ({$confirmed}) exceed tentative guest estimate ({$tentative}).");
            }
        } else {
            $this->guestCount = $tentative;
            $this->guestStatus = 'Tentative';
        }
    }

    public function updatedGuestCount() { $this->tentativeGuests = is_numeric($this->guestCount) ? intval($this->guestCount) : 100; $this->syncGuestCounts(); $this->recalculatePrices(); }
    public function updatedPerPlatePrice() { $this->recalculatePrices(); }
    public function updatedHallCharges() { $this->recalculatePrices(); }
    public function updatedExtraCharges() { $this->recalculatePrices(); }
    public function updatedDiscountAmount() { $this->recalculatePrices(); }
    public function updatedSecurityDeposit() { $this->recalculatePrices(); }
    public function updatedTaxRate() { $this->recalculatePrices(); }

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

        $addonsSum = 0.00;
        foreach ($this->selectedAddons as $addon) {
            if (!empty($addon['selected'])) {
                $price = is_numeric($addon['price']) ? floatval($addon['price']) : 0.00;
                $quantity = is_numeric($addon['quantity']) ? intval($addon['quantity']) : 1;
                $addonsSum += $price * $quantity;
            }
        }
        $this->extraCharges = $addonsSum;

        if (is_numeric($this->guestCount) && intval($this->guestCount) > 0 && is_null($this->confirmedGuests)) {
            $this->tentativeGuests = intval($this->guestCount);
        }

        $this->syncGuestCounts();

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

    public function submitBooking()
    {
        $rules = [
            'selectedBranchId' => 'required|exists:branches,id',
            'selectedCustomerId' => 'required|exists:customers,id',
            'selectedEventTypeId' => 'required|exists:event_types,id',
            'selectedHallIds' => 'required|array|min:1',
            'selectedDate' => 'required|date|after_or_equal:today',
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

        if ($this->privacyRequired) {
            $rules['privacyLadiesPercentage'] = 'required|integer|min:0|max:100';
            $rules['privacyGentsPercentage'] = 'required|integer|min:0|max:100';
        }

        $this->validate($rules, [
            'selectedBranchId.required' => 'Please select a branch for this booking.',
            'selectedBranchId.exists' => 'The selected branch is invalid.',
        ]);

        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();
        $userId = $user->id;

        // Security check: User must have access to the selected branch
        if (!$user->hasAccessToBranch($this->selectedBranchId, $marqueeId)) {
            $this->addError('selectedBranchId', 'You are not authorized to create bookings for this branch.');
            return;
        }

        // Security check: Verify all selected halls belong to the chosen branch and tenant
        $validHallCount = Hall::where('marquee_id', $marqueeId)
            ->where('branch_id', $this->selectedBranchId)
            ->whereIn('id', $this->selectedHallIds)
            ->count();

        if ($validHallCount !== count($this->selectedHallIds)) {
            $this->addError('selectedHallIds', 'One or more selected halls do not belong to the chosen branch.');
            return;
        }

        if ($this->privacyRequired) {
            $ladies = intval($this->privacyLadiesPercentage);
            $gents = intval($this->privacyGentsPercentage);
            if (($ladies + $gents) !== 100) {
                $this->addError('privacyLadiesPercentage', 'Ladies and Gents percentages must total exactly 100%.');
                $this->addError('privacyGentsPercentage', 'Ladies and Gents percentages must total exactly 100%.');
                return;
            }
        }

        if (!$this->isAvailable) {
            $this->addError('availability', 'The selected hall schedule is not available. Please verify conflicts.');
            return;
        }

        $branchId = (int) $this->selectedBranchId;

        try {
            $booking = DB::transaction(function () use ($marqueeId, $userId, $branchId) {
                $service = new AvailabilityService();
                
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
                        throw new \Exception("Conflict detected for hall ID: {$hId}");
                    }
                }

                $newBooking = Booking::create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branchId,
                    'customer_id' => $this->selectedCustomerId,
                    'event_type_id' => $this->selectedEventTypeId,
                    'hall_id' => $this->selectedHallId,
                    'slot_id' => $this->checkType === 'slot' ? $this->selectedSlotId : null,
                    'package_id' => $this->noFood ? null : $this->selectedPackageId,
                    'booking_date' => $this->selectedDate,
                    'start_time' => $this->startTime,
                    'end_time' => $this->endTime,
                    'guest_count' => $this->guestCount,
                    'tentative_guests' => is_numeric($this->tentativeGuests) ? intval($this->tentativeGuests) : $this->guestCount,
                    'confirmed_guests' => (is_numeric($this->confirmedGuests) && intval($this->confirmedGuests) > 0) ? intval($this->confirmedGuests) : null,
                    'guest_status' => $this->guestStatus ?: 'Tentative',
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
                    'deposit_status' => 'Held',
                    'created_by' => $userId,
                    'no_food' => $this->noFood,
                    'privacy_required' => $this->privacyRequired,
                    'privacy_ladies_percentage' => $this->privacyRequired ? intval($this->privacyLadiesPercentage) : null,
                    'privacy_gents_percentage' => $this->privacyRequired ? intval($this->privacyGentsPercentage) : null,
                ]);

                // Pivot halls
                $newBooking->halls()->sync($this->selectedHallIds);

                // Addons sync
                foreach ($this->selectedAddons as $addonId => $addon) {
                    if (!empty($addon['selected'])) {
                        \App\Models\BookingExtraService::create([
                            'booking_id' => $newBooking->id,
                            'extra_service_id' => $addonId,
                            'service_name' => $addon['name'],
                            'unit_price' => $addon['price'],
                            'quantity' => $addon['quantity'],
                            'total_price' => $addon['price'] * $addon['quantity'],
                        ]);
                    }
                }

                // Menu items
                foreach ($this->bookingMenuItems as $index => $menuItem) {
                    $newBooking->menuItems()->attach($menuItem['id'], [
                        'custom_note' => $menuItem['custom_note'] ?: null,
                        'managed_by_host' => !empty($menuItem['managed_by_host']),
                        'sort_order' => $index,
                    ]);
                }

                BookingHistory::create([
                    'booking_id' => $newBooking->id,
                    'user_id' => $userId,
                    'status_from' => 'Draft',
                    'status_to' => $this->bookingStatus,
                    'notes' => 'Booking created successfully via One-Page form.',
                ]);

                return $newBooking;
            });

            session()->flash('success', "Booking #{$booking->booking_number} has been created successfully.");
            return redirect()->route('bookings.show', $booking->id);

        } catch (\Exception $e) {
            $this->addError('availability', 'An error occurred during save. Schedule may have been reserved by another agent: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.booking-one-page');
    }
}
