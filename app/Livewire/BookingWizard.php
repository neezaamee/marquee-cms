<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Package;
use App\Models\Slot;
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
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $this->hallsList = Hall::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->orderBy('hall_name')
            ->get();

        $this->packagesList = Package::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('package_name')
            ->get();

        if ($this->hallsList->isNotEmpty() && empty($this->selectedHallId)) {
            $this->selectedHallId = (string) $this->hallsList->first()->id;
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
        $this->validate([
            'newFirstName' => 'required|string|max:255',
            'newLastName' => 'required|string|max:255',
            'newCustomerType' => 'required|in:Individual,Corporate',
            'newCompanyName' => 'required_if:newCustomerType,Corporate|nullable|string|max:255',
            'newPhone' => 'required|string|max:20',
            'newCNIC' => 'nullable|string|max:20',
            'newEmail' => 'nullable|email|max:255',
            'newCity' => 'required|string',
            'newProvince' => 'required|string',
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

    /**
     * Loads dynamic slot choices and performs initial analysis.
     */
    public function loadSlotsAndCheck()
    {
        if (empty($this->selectedHallId) || empty($this->selectedDate)) {
            return;
        }

        $service = new AvailabilityService();
        
        // Query active slots assigned to this marquee
        $marqueeId = auth()->user()->marquee_id;
        $slots = Slot::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->orderBy('start_time')
            ->get();

        $this->availableSlotsList = [];

        foreach ($slots as $slot) {
            $isSlotAvailable = $service->checkAvailability(
                $this->selectedHallId,
                $this->selectedDate,
                $slot->start_time,
                $slot->end_time
            );

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
        if (empty($this->selectedHallId) || empty($this->selectedDate) || empty($this->startTime) || empty($this->endTime)) {
            $this->availabilityChecked = false;
            return;
        }

        $service = new AvailabilityService();

        $conflicting = $service->getConflictingBooking(
            $this->selectedHallId,
            $this->selectedDate,
            Carbon::parse($this->startTime)->format('H:i:s'),
            Carbon::parse($this->endTime)->format('H:i:s')
        );

        if ($conflicting) {
            $this->isAvailable = false;
            $this->conflictDetails = $conflicting;
        } else {
            $this->isAvailable = true;
            $this->conflictDetails = null;
        }

        $this->availabilityChecked = true;
    }

    public function updatedSelectedPackageId()
    {
        if (empty($this->selectedPackageId)) {
            $this->perPlatePrice = 0.00;
            $this->recalculatePrices();
            return;
        }

        $package = Package::findOrFail($this->selectedPackageId);
        $this->perPlatePrice = $package->per_plate_price;
        $this->guestCount = $package->minimum_guests ?: 100;
        
        // Auto default security deposit to package flat base price or custom rate
        $this->securityDeposit = 15000.00; // Standard security deposit default
        $this->recalculatePrices();
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
                'selectedHallId' => 'required|exists:halls,id',
                'selectedDate' => 'required|date|after_or_equal:today',
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
            $this->validate([
                'selectedPackageId' => 'required|exists:packages,id',
                'guestCount' => 'required|integer|min:1',
                'perPlatePrice' => 'required|numeric|min:0',
                'hallCharges' => 'required|numeric|min:0',
                'extraCharges' => 'required|numeric|min:0',
                'discountAmount' => 'required|numeric|min:0',
                'securityDeposit' => 'required|numeric|min:0',
                'taxRate' => 'required|numeric|min:0',
            ]);

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
        $this->validate([
            'selectedCustomerId' => 'required|exists:customers,id',
            'selectedEventTypeId' => 'required|exists:event_types,id',
            'selectedHallId' => 'required|exists:halls,id',
            'selectedDate' => 'required|date',
            'startTime' => 'required',
            'endTime' => 'required',
            'selectedPackageId' => 'required|exists:packages,id',
            'guestCount' => 'required|integer|min:1',
            'perPlatePrice' => 'required|numeric|min:0',
            'hallCharges' => 'required|numeric|min:0',
            'extraCharges' => 'required|numeric|min:0',
            'discountAmount' => 'required|numeric|min:0',
            'securityDeposit' => 'required|numeric|min:0',
            'taxRate' => 'required|numeric|min:0',
            'bookingStatus' => 'required|in:Draft,Reserved,Confirmed',
        ]);

        $marqueeId = auth()->user()->marquee_id;
        $userId = auth()->id();

        // Perform transactional creation and final double-booking check
        try {
            $booking = DB::transaction(function () use ($marqueeId, $userId) {
                
                // 1. Lock existing bookings to prevent race conditions
                DB::table('bookings')
                    ->where('marquee_id', $marqueeId)
                    ->where('hall_id', $this->selectedHallId)
                    ->where('booking_date', $this->selectedDate)
                    ->lockForUpdate()
                    ->get();

                // 2. Final check inside transaction
                $service = new AvailabilityService();
                $isStillAvailable = $service->checkAvailability(
                    $this->selectedHallId,
                    $this->selectedDate,
                    Carbon::parse($this->startTime)->format('H:i:s'),
                    Carbon::parse($this->endTime)->format('H:i:s')
                );

                if (!$isStillAvailable) {
                    throw new \Exception("Double-booking prevented: The selected slot was just booked by another operator.");
                }

                // 3. Create the Booking
                $newBooking = Booking::create([
                    'marquee_id' => $marqueeId,
                    'customer_id' => $this->selectedCustomerId,
                    'event_type_id' => $this->selectedEventTypeId,
                    'hall_id' => $this->selectedHallId,
                    'slot_id' => $this->selectedSlotId ?: null,
                    'package_id' => $this->selectedPackageId,
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
                ]);

                // 4. Create history record
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
        return view('livewire.booking-wizard');
    }
}
