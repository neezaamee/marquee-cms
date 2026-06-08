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

class BookingEdit extends Component
{
    public Booking $booking;

    // Form inputs
    public $selectedCustomerId = '';
    public $selectedEventTypeId = '';
    public $selectedHallId = '';
    public $selectedDate = '';

    public $checkType = 'slot'; // slot, custom
    public $selectedSlotId = '';
    public $customStart = '';
    public $customEnd = '';
    public $startTime = '';
    public $endTime = '';

    public $selectedPackageId = '';
    public $guestCount = 0;
    public $perPlatePrice = 0.00;
    public $hallCharges = 0.00;
    public $extraCharges = 0.00;
    public $discountAmount = 0.00;
    public $securityDeposit = 0.00;
    public $taxRate = 13.00;

    public $packageAmount = 0.00;
    public $subtotal = 0.00;
    public $taxAmount = 0.00;
    public $grandTotal = 0.00;

    public $specialInstructions = '';
    public $bookingStatus = '';
    public $paymentStatus = '';

    // Cache dropdown lists
    public $customersList = [];
    public $eventTypesList = [];
    public $hallsList = [];
    public $packagesList = [];
    public $availableSlotsList = [];

    // Availability Checks
    public $isAvailable = true;
    public $availabilityChecked = false;
    public $conflictDetails = null;

    public function mount(Booking $booking)
    {
        $this->booking = $booking;

        $this->selectedCustomerId = $booking->customer_id;
        $this->selectedEventTypeId = $booking->event_type_id;
        $this->selectedHallId = $booking->hall_id;
        $this->selectedDate = $booking->booking_date->format('Y-m-d');
        
        $this->selectedSlotId = $booking->slot_id ?: '';
        $this->checkType = $booking->slot_id ? 'slot' : 'custom';
        
        $this->startTime = $booking->start_time->format('Y-m-d H:i:s');
        $this->endTime = $booking->end_time->format('Y-m-d H:i:s');

        $this->customStart = $booking->start_time->format('H:i');
        $this->customEnd = $booking->end_time->format('H:i');

        $this->selectedPackageId = $booking->package_id;
        $this->guestCount = $booking->guest_count;
        $this->perPlatePrice = $booking->per_plate_price ?? 0.00;
        $this->hallCharges = $booking->hall_charges ?? 0.00;
        $this->extraCharges = $booking->extra_charges ?? 0.00;
        $this->discountAmount = $booking->discount_amount ?? 0.00;
        $this->securityDeposit = $booking->security_deposit ?? 0.00;
        
        // Calculate original tax rate
        $this->packageAmount = $booking->package_amount ?? 0.00;
        $this->subtotal = $booking->subtotal ?? 0.00;
        $this->taxAmount = $booking->tax_amount ?? 0.00;
        $this->grandTotal = $booking->grand_total ?? 0.00;
        
        if ($this->subtotal > 0) {
            $this->taxRate = round(($this->taxAmount * 100) / $this->subtotal, 2);
        } else {
            $this->taxRate = 13.00;
        }

        $this->specialInstructions = $booking->special_instructions;
        $this->bookingStatus = $booking->booking_status ?? 'Draft';
        $this->paymentStatus = $booking->payment_status ?? 'Unpaid';

        $this->loadDropdowns();
        $this->loadSlotsAndCheck();
    }

    public function loadDropdowns()
    {
        $marqueeId = auth()->user()->marquee_id;

        $this->customersList = Customer::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('first_name')
            ->get();

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
    }

    public function updatedSelectedHallId()
    {
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
        $this->isAvailable = true;
        $this->availabilityChecked = false;
        $this->conflictDetails = null;
    }

    public function loadSlotsAndCheck()
    {
        if (empty($this->selectedHallId) || empty($this->selectedDate)) {
            return;
        }

        $service = new AvailabilityService();
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
                $slot->end_time,
                $this->booking->id // Exclude self
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
            Carbon::parse($this->endTime)->format('H:i:s'),
            $this->booking->id // Exclude self
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
        $this->recalculatePrices();
    }

    public function updatedGuestCount() { $this->recalculatePrices(); }
    public function updatedPerPlatePrice() { $this->recalculatePrices(); }
    public function updatedHallCharges() { $this->recalculatePrices(); }
    public function updatedExtraCharges() { $this->recalculatePrices(); }
    public function updatedDiscountAmount() { $this->recalculatePrices(); }
    public function updatedSecurityDeposit() { $this->recalculatePrices(); }
    public function updatedTaxRate() { $this->recalculatePrices(); }

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

    /**
     * Save updates to the Booking model.
     */
    public function save()
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
            'bookingStatus' => 'required|in:Draft,Reserved,Confirmed,Completed,Cancelled,Rejected',
            'paymentStatus' => 'required|in:Unpaid,Partially Paid,Paid,Refunded',
        ]);

        $marqueeId = auth()->user()->marquee_id;
        $userId = auth()->id();

        try {
            DB::transaction(function () use ($marqueeId, $userId) {
                // Shared lock checking
                DB::table('bookings')
                    ->where('marquee_id', $marqueeId)
                    ->where('hall_id', $this->selectedHallId)
                    ->where('booking_date', $this->selectedDate)
                    ->where('id', '!=', $this->booking->id)
                    ->lockForUpdate()
                    ->get();

                $service = new AvailabilityService();
                $isStillAvailable = $service->checkAvailability(
                    $this->selectedHallId,
                    $this->selectedDate,
                    Carbon::parse($this->startTime)->format('H:i:s'),
                    Carbon::parse($this->endTime)->format('H:i:s'),
                    $this->booking->id
                );

                // Check for double-booking unless we are saving as Draft or Cancelled/Rejected
                if (in_array($this->bookingStatus, ['Reserved', 'Confirmed']) && !$isStillAvailable) {
                    throw new \Exception("Double-booking clash: The selected slot overlaps with another reservation.");
                }

                $oldStatus = $this->booking->booking_status;
                $oldPaymentStatus = $this->booking->payment_status;

                // Update
                $this->booking->update([
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
                    'payment_status' => $this->paymentStatus,
                ]);

                // Create history record if anything changed
                if ($oldStatus !== $this->bookingStatus || $oldPaymentStatus !== $this->paymentStatus) {
                    BookingHistory::create([
                        'booking_id' => $this->booking->id,
                        'user_id' => $userId,
                        'status_from' => $oldStatus,
                        'status_to' => $this->bookingStatus,
                        'payment_status_from' => $oldPaymentStatus,
                        'payment_status_to' => $this->paymentStatus,
                        'notes' => 'Booking details updated by staff.',
                    ]);
                }
            });

            session()->flash('success', 'Booking #' . $this->booking->booking_number . ' has been updated successfully.');
            return redirect()->route('bookings.show', $this->booking->id);

        } catch (\Exception $e) {
            $this->addError('submission', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.booking-edit');
    }
}
