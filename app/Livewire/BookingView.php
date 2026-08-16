<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\BookingHistory;
use Livewire\Component;

class BookingView extends Component
{
    public Booking $booking;
    
    // Quick Payment Record Modal State
    public $showPaymentModal = false;
    public $amountPaid = 0.00;
    public $paymentDate = '';
    public $paymentMethod = 'Cash';
    public $transactionReference = '';
    public $paymentNote = '';

    // Deposit Processing Modal State
    public $showDepositModal = false;
    public $depositAction = 'refund_full';
    public $depositRefundedAmount = 0.00;
    public $depositDeductedAmount = 0.00;
    public $depositNotes = '';

    // Guest Confirmation Modal State
    public $showGuestModal = false;
    public $modalTentativeGuests = 100;
    public $modalConfirmedGuests = null;
    public $modalGuestStatus = 'Tentative';

    // Event Day Final Bill Modal State
    public $showFinalBillModal = false;
    public $fbGuestCount = 0;
    public $fbPerPlatePrice = 0.00;
    public $fbHallCharges = 0.00;
    public $fbExtraCharges = 0.00;
    public $fbDiscountAmount = 0.00;
    public $fbTaxAmount = 0.00;
    public $fbNotes = '';
    public $fbAddonsList = []; // Array of ['service_name' => string, 'unit_price' => float, 'quantity' => int, 'total_price' => float]
    public $newAddonName = '';
    public $newAddonPrice = 0.00;
    public $newAddonQty = 1;

    // Kitchen Slip Modal State
    public $showKitchenSlipModal = false;
    public $kitchenLang = 'bilingual';
    public $kitchenInstructions = '';

    // Vendor Service Sale Modal State
    public $showVendorSaleModal = false;
    public $vsVendorId = '';
    public $vsServiceId = '';
    public $vsSaleAmount = 0.00;
    public $vsCommissionRate = null;
    public $vsNotes = '';

    public function mount(Booking $booking)
    {
        $this->booking = $booking;
        $this->paymentDate = date('Y-m-d');
    }

    public function openVendorSaleModal()
    {
        $this->vsVendorId = '';
        $this->vsServiceId = '';
        $this->vsSaleAmount = 0.00;
        $this->vsCommissionRate = null;
        $this->vsNotes = '';
        $this->showVendorSaleModal = true;
    }

    public function saveBookingVendorSale()
    {
        $this->validate([
            'vsVendorId' => 'required|exists:vendors,id',
            'vsSaleAmount' => 'required|numeric|min:1',
        ]);

        $serviceEngine = app(\App\Services\VendorCommissionService::class);
        $serviceEngine->createVendorSale([
            'vendor_id' => $this->vsVendorId,
            'vendor_service_id' => $this->vsServiceId ?: null,
            'booking_id' => $this->booking->id,
            'customer_id' => $this->booking->customer_id,
            'event_date' => $this->booking->booking_date->format('Y-m-d'),
            'sale_date' => date('Y-m-d'),
            'quantity' => 1,
            'unit' => 'Event',
            'sale_amount' => floatval($this->vsSaleAmount),
            'commission_rate' => $this->vsCommissionRate !== null && $this->vsCommissionRate !== '' ? floatval($this->vsCommissionRate) : null,
            'notes' => $this->vsNotes,
        ]);

        $this->booking->refresh();
        $this->showVendorSaleModal = false;
        session()->flash('success', 'Vendor service linked to booking successfully.');
    }

    /**
     * Record a payment transaction and update payment status.
     */
    public function recordPayment()
    {
        $this->validate([
            'amountPaid' => 'required|numeric|min:1',
            'paymentDate' => 'required|date',
            'paymentMethod' => 'required|string',
            'transactionReference' => 'nullable|string|max:255',
            'paymentNote' => 'nullable|string|max:255',
        ]);

        $oldPaymentStatus = $this->booking->payment_status;
        
        // 1. Create payment transaction record
        \App\Models\BookingPayment::create([
            'booking_id' => $this->booking->id,
            'amount' => floatval($this->amountPaid),
            'payment_date' => $this->paymentDate,
            'payment_method' => $this->paymentMethod,
            'transaction_reference' => $this->transactionReference ?: null,
            'recorded_by' => auth()->id(),
            'notes' => $this->paymentNote ?: null,
        ]);

        // 2. Sum all payments to update status
        $totalPaid = $this->booking->payments()->sum('amount');
        
        $billingAmount = $this->booking->finalBill ? $this->booking->finalBill->grand_total : $this->booking->grand_total;
        $newPaymentStatus = 'Unpaid';
        if ($totalPaid > 0) {
            if ($totalPaid >= $billingAmount) {
                $newPaymentStatus = 'Paid';
            } else {
                $newPaymentStatus = 'Partially Paid';
            }
        }

        $this->booking->update([
            'payment_status' => $newPaymentStatus
        ]);

        // Add history log
        BookingHistory::create([
            'booking_id' => $this->booking->id,
            'user_id' => auth()->id(),
            'status_from' => $this->booking->booking_status,
            'status_to' => $this->booking->booking_status,
            'payment_status_from' => $oldPaymentStatus,
            'payment_status_to' => $newPaymentStatus,
            'notes' => 'Recorded ' . $this->paymentMethod . ' payment of Rs. ' . number_format($this->amountPaid, 2) . '. ' . $this->paymentNote,
        ]);

        $this->booking->refresh();
        $this->amountPaid = 0.00;
        $this->paymentNote = '';
        $this->transactionReference = '';
        $this->paymentDate = date('Y-m-d');
        $this->showPaymentModal = false;

        session()->flash('success', 'Payment recorded successfully in transactions ledger.');
    }

    /**
     * Open final bill modal and load original details as template.
     */
    public function openFinalBillModal()
    {
        $booking = $this->booking;
        
        if ($booking->finalBill) {
            $this->fbGuestCount = $booking->finalBill->guest_count;
            $this->fbPerPlatePrice = $booking->finalBill->per_plate_price;
            $this->fbHallCharges = $booking->finalBill->hall_charges;
            $this->fbDiscountAmount = $booking->finalBill->discount_amount;
            $this->fbNotes = $booking->finalBill->notes;
            
            $this->fbAddonsList = [];
            foreach ($booking->finalBill->extraServices as $addon) {
                $this->fbAddonsList[] = [
                    'service_name' => $addon->service_name,
                    'unit_price' => $addon->unit_price,
                    'quantity' => $addon->quantity,
                    'total_price' => $addon->total_price,
                ];
            }
        } else {
            $this->fbGuestCount = $booking->guest_count;
            $this->fbPerPlatePrice = $booking->per_plate_price;
            $this->fbHallCharges = $booking->hall_charges;
            $this->fbDiscountAmount = $booking->discount_amount;
            $this->fbNotes = '';
            
            $this->fbAddonsList = [];
            foreach ($booking->extraServices as $addon) {
                $this->fbAddonsList[] = [
                    'service_name' => $addon->service_name,
                    'unit_price' => $addon->unit_price,
                    'quantity' => $addon->quantity,
                    'total_price' => $addon->total_price,
                ];
            }
        }
        
        $this->recalculateFinalBill();
        $this->showFinalBillModal = true;
    }

    /**
     * Recalculate billing values of final bill.
     */
    public function recalculateFinalBill()
    {
        $this->fbGuestCount = is_numeric($this->fbGuestCount) ? intval($this->fbGuestCount) : 0;
        $this->fbPerPlatePrice = is_numeric($this->fbPerPlatePrice) ? floatval($this->fbPerPlatePrice) : 0.00;
        $this->fbHallCharges = is_numeric($this->fbHallCharges) ? floatval($this->fbHallCharges) : 0.00;
        $this->fbDiscountAmount = is_numeric($this->fbDiscountAmount) ? floatval($this->fbDiscountAmount) : 0.00;

        $packageAmount = $this->booking->no_food ? 0.00 : ($this->fbGuestCount * $this->fbPerPlatePrice);
        
        $addonsSum = 0.00;
        foreach ($this->fbAddonsList as &$addon) {
            $addon['total_price'] = floatval($addon['unit_price']) * intval($addon['quantity']);
            $addonsSum += $addon['total_price'];
        }
        $this->fbExtraCharges = $addonsSum;

        $subtotal = $packageAmount + $this->fbHallCharges + $this->fbExtraCharges - $this->fbDiscountAmount;
        
        // Calculate tax based on original tax rate
        $origSubtotal = $this->booking->subtotal;
        $taxRate = $origSubtotal > 0 ? ($this->booking->tax_amount / $origSubtotal) * 100 : 13.00;
        
        $this->fbTaxAmount = round(($subtotal * $taxRate) / 100, 2);
    }

    /**
     * Add new extra service addon inside final bill modal.
     */
    public function addFbAddon()
    {
        $this->validate([
            'newAddonName' => 'required|string|max:255',
            'newAddonPrice' => 'required|numeric|min:0',
            'newAddonQty' => 'required|integer|min:1',
        ]);

        $this->fbAddonsList[] = [
            'service_name' => $this->newAddonName,
            'unit_price' => floatval($this->newAddonPrice),
            'quantity' => intval($this->newAddonQty),
            'total_price' => floatval($this->newAddonPrice) * intval($this->newAddonQty),
        ];

        $this->newAddonName = '';
        $this->newAddonPrice = 0.00;
        $this->newAddonQty = 1;

        $this->recalculateFinalBill();
    }

    /**
     * Remove addon from final bill list.
     */
    public function removeFbAddon($index)
    {
        if (isset($this->fbAddonsList[$index])) {
            unset($this->fbAddonsList[$index]);
            $this->fbAddonsList = array_values($this->fbAddonsList);
        }
        $this->recalculateFinalBill();
    }

    /**
     * Save the prepared final bill adjustments record.
     */
    public function saveFinalBill()
    {
        $this->recalculateFinalBill();

        $this->validate([
            'fbGuestCount' => 'required|integer|min:1',
            'fbPerPlatePrice' => 'required|numeric|min:0',
            'fbHallCharges' => 'required|numeric|min:0',
            'fbDiscountAmount' => 'required|numeric|min:0',
            'fbNotes' => 'nullable|string',
        ]);

        $packageAmount = $this->booking->no_food ? 0.00 : ($this->fbGuestCount * $this->fbPerPlatePrice);
        $subtotal = $packageAmount + $this->fbHallCharges + $this->fbExtraCharges - $this->fbDiscountAmount;
        $grandTotal = $subtotal + $this->fbTaxAmount + $this->booking->security_deposit;

        $finalBill = \Illuminate\Support\Facades\DB::transaction(function () use ($packageAmount, $subtotal, $grandTotal) {
            // Remove existing final bill and its details if they exist
            if ($this->booking->finalBill) {
                $this->booking->finalBill->extraServices()->delete();
                $this->booking->finalBill->delete();
            }

            // Create new final bill
            $finalBill = \App\Models\BookingFinalBill::create([
                'booking_id' => $this->booking->id,
                'guest_count' => $this->fbGuestCount,
                'per_plate_price' => $this->fbPerPlatePrice,
                'package_amount' => $packageAmount,
                'hall_charges' => $this->fbHallCharges,
                'extra_charges' => $this->fbExtraCharges,
                'discount_amount' => $this->fbDiscountAmount,
                'tax_amount' => $this->fbTaxAmount,
                'subtotal' => $subtotal,
                'grand_total' => $grandTotal,
                'notes' => $this->fbNotes ?: null,
                'created_by' => auth()->id(),
            ]);

            // Save final bill addons
            foreach ($this->fbAddonsList as $addon) {
                \App\Models\BookingFinalBillExtraService::create([
                    'final_bill_id' => $finalBill->id,
                    'service_name' => $addon['service_name'],
                    'unit_price' => $addon['unit_price'],
                    'quantity' => $addon['quantity'],
                    'total_price' => $addon['total_price'],
                ]);
            }

            // Log history
            BookingHistory::create([
                'booking_id' => $this->booking->id,
                'user_id' => auth()->id(),
                'status_from' => $this->booking->booking_status,
                'status_to' => $this->booking->booking_status,
                'notes' => 'Prepared Event-Day Final Bill. Actual guests: ' . $this->fbGuestCount . '. Grand Total adjusted to Rs. ' . number_format($grandTotal, 2),
            ]);

            return $finalBill;
        });

        // Trigger FBR Sync if POS configuration exists
        if ($finalBill) {
            $fbrService = app(\App\Services\FbrPosService::class);
            $fbrService->syncFinalBill($finalBill);
        }

        // Recalculate and update the booking's payment status based on the new final bill amount
        $this->updatePaymentStatusBasedOnFinalBill();

        $this->booking->refresh();
        $this->showFinalBillModal = false;
        
        session()->flash('success', 'Event-day final bill has been generated and validated with FBR successfully.');
    }

    /**
     * Recalculate and update booking's payment status.
     */
    public function updatePaymentStatusBasedOnFinalBill()
    {
        $totalPaid = $this->booking->payments()->sum('amount');
        $billingAmount = $this->booking->finalBill ? $this->booking->finalBill->grand_total : $this->booking->grand_total;

        $newPaymentStatus = 'Unpaid';
        if ($totalPaid > 0) {
            if ($totalPaid >= $billingAmount) {
                $newPaymentStatus = 'Paid';
            } else {
                $newPaymentStatus = 'Partially Paid';
            }
        }

        $this->booking->update([
            'payment_status' => $newPaymentStatus
        ]);
    }

    /**
     * Process refundable security deposit.
     */
    public function processDeposit()
    {
        if ($this->depositAction === 'refund_full') {
            $this->depositRefundedAmount = $this->booking->security_deposit;
            $this->depositDeductedAmount = 0.00;
            $status = 'Refunded';
        } else {
            $this->validate([
                'depositRefundedAmount' => 'required|numeric|min:0',
                'depositDeductedAmount' => 'required|numeric|min:0',
                'depositNotes' => 'required|string|min:5',
            ]);

            // Verify totals
            $sum = floatval($this->depositRefundedAmount) + floatval($this->depositDeductedAmount);
            if (abs($sum - $this->booking->security_deposit) > 0.01) {
                $this->addError('depositSum', 'Refunded + Deducted amount must equal security deposit (Rs. ' . number_format($this->booking->security_deposit, 2) . ').');
                return;
            }

            $status = floatval($this->depositDeductedAmount) > 0 ? 'Deducted' : 'Refunded';
        }

        $this->booking->update([
            'deposit_status' => $status,
            'deposit_refunded_amount' => $this->depositRefundedAmount,
            'deposit_deducted_amount' => $this->depositDeductedAmount,
            'deposit_notes' => $this->depositNotes ?: null,
        ]);

        BookingHistory::create([
            'booking_id' => $this->booking->id,
            'user_id' => auth()->id(),
            'status_from' => $this->booking->booking_status,
            'status_to' => $this->booking->booking_status,
            'notes' => 'Security deposit processed: ' . $status . '. Refunded: Rs. ' . number_format($this->depositRefundedAmount, 2) . ', Deducted: Rs. ' . number_format($this->depositDeductedAmount, 2) . '. Notes: ' . $this->depositNotes,
        ]);

        $this->booking->refresh();
        $this->showDepositModal = false;
        $this->depositNotes = '';
        $this->depositRefundedAmount = 0.00;
        $this->depositDeductedAmount = 0.00;

        session()->flash('success', 'Refundable security deposit updated.');
    }

    /**
     * Transition booking status. Only owners/superadmins can unlock completed/cancelled events.
     */
    public function updateStatus($newStatus)
    {
        if (!in_array($newStatus, ['Draft', 'Reserved', 'Confirmed', 'Completed', 'Cancelled', 'Rejected'])) {
            return;
        }

        if ($newStatus === 'Completed' && \Carbon\Carbon::parse($this->booking->booking_date)->startOfDay()->gt(\Carbon\Carbon::today())) {
            session()->flash('error', 'Future bookings cannot be marked as Completed.');
            return;
        }

        $user = auth()->user();
        $isOwner = $user->role && in_array($user->role->name, ['owner', 'super_admin']);

        if ($this->booking->booking_status === 'Completed' && !$isOwner) {
            session()->flash('error', 'Completed bookings cannot be changed to another status.');
            return;
        }
        $isLocked = $this->booking->booking_status === 'Cancelled';

        if ($isLocked && !$isOwner) {
            session()->flash('error', 'Only owners or super admins can unlock or change status of Cancelled bookings.');
            return;
        }

        $oldStatus = $this->booking->booking_status;

        $this->booking->update([
            'booking_status' => $newStatus
        ]);

        BookingHistory::create([
            'booking_id' => $this->booking->id,
            'user_id' => auth()->id(),
            'status_from' => $oldStatus,
            'status_to' => $newStatus,
            'notes' => 'Booking status manually transitioned to ' . $newStatus,
        ]);

        $this->booking->refresh();
        session()->flash('success', 'Booking status updated to ' . $newStatus);
    }

    public function openGuestModal()
    {
        $this->modalTentativeGuests = $this->booking->tentative_guests ?? $this->booking->guest_count;
        $this->modalConfirmedGuests = $this->booking->confirmed_guests;
        $this->modalGuestStatus = $this->booking->guest_status ?? ($this->booking->confirmed_guests ? 'Confirmed' : 'Tentative');
        $this->showGuestModal = true;
    }

    public function confirmGuestCount()
    {
        $this->validate([
            'modalTentativeGuests' => 'required|integer|min:1',
            'modalConfirmedGuests' => 'nullable|integer|min:0',
        ]);

        $tentative = intval($this->modalTentativeGuests);
        $confirmed = (is_numeric($this->modalConfirmedGuests) && intval($this->modalConfirmedGuests) > 0) ? intval($this->modalConfirmedGuests) : null;
        
        $effectiveCount = $confirmed ?? $tentative;
        $status = !is_null($confirmed) ? 'Confirmed' : 'Tentative';

        // Recalculate package amount if applicable
        $perPlatePrice = $this->booking->per_plate_price ?? 0.00;
        $packageAmount = $this->booking->no_food ? 0.00 : ($effectiveCount * $perPlatePrice);

        $pricing = \App\Services\BookingPricingService::calculate([
            'guest_count' => $effectiveCount,
            'per_plate_price' => $perPlatePrice,
            'hall_charges' => $this->booking->hall_charges,
            'extra_charges' => $this->booking->extra_charges,
            'discount_amount' => $this->booking->discount_amount,
            'security_deposit' => $this->booking->security_deposit,
            'tax_rate' => $this->booking->subtotal > 0 ? round(($this->booking->tax_amount * 100) / $this->booking->subtotal, 2) : 13.00,
        ]);

        $this->booking->update([
            'tentative_guests' => $tentative,
            'confirmed_guests' => $confirmed,
            'guest_status' => $status,
            'guest_count' => $effectiveCount,
            'package_amount' => $pricing['package_amount'],
            'subtotal' => $pricing['subtotal'],
            'tax_amount' => $pricing['tax_amount'],
            'grand_total' => $pricing['grand_total'],
        ]);

        BookingHistory::create([
            'booking_id' => $this->booking->id,
            'user_id' => auth()->id(),
            'status_from' => $this->booking->booking_status,
            'status_to' => $this->booking->booking_status,
            'notes' => "Updated Guest Headcount: Tentative={$tentative}, Confirmed=" . ($confirmed ?? 'None') . ", Status={$status}. Effective Headcount: {$effectiveCount}.",
        ]);

        $this->booking->refresh();
        $this->showGuestModal = false;
        session()->flash('success', 'Guest headcount and status updated successfully.');
    }

    /**
     * Open Kitchen Slip printing options modal.
     */
    public function openKitchenSlipModal()
    {
        $this->kitchenInstructions = $this->booking->kitchen_special_instructions ?? $this->booking->special_instructions ?? '';
        $this->showKitchenSlipModal = true;
    }

    /**
     * Save special kitchen instructions and trigger print window redirect.
     */
    public function saveKitchenInstructionsAndPrint()
    {
        $this->booking->update([
            'kitchen_special_instructions' => $this->kitchenInstructions,
        ]);

        $this->showKitchenSlipModal = false;
        $url = route('bookings.kitchen-slip', [
            'booking' => $this->booking->id,
            'lang' => $this->kitchenLang,
            'kitchen_special_instructions' => $this->kitchenInstructions
        ]);

        $this->dispatch('open-print-window', url: $url);
    }

    public function render()
    {
        $histories = $this->booking->histories()->with('user')->get();
        $marqueeId = auth()->user()->marquee_id;

        $vendorSales = \App\Models\VendorSale::where('booking_id', $this->booking->id)
            ->with(['vendor', 'service'])
            ->get();

        $allVendors = \App\Models\Vendor::where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $vsVendorServices = $this->vsVendorId
            ? \App\Models\VendorService::where('marquee_id', $marqueeId)->where('vendor_id', $this->vsVendorId)->get()
            : collect();

        return view('livewire.booking-view', [
            'histories' => $histories,
            'vendorSales' => $vendorSales,
            'allVendors' => $allVendors,
            'vsVendorServices' => $vsVendorServices,
        ]);
    }
}
