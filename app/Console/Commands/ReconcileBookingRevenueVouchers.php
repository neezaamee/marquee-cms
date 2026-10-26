<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\RevenueRecognitionService;
use Illuminate\Console\Command;

class ReconcileBookingRevenueVouchers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:reconcile-booking-revenue {--booking= : Specific booking ID or booking number to reconcile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile and sync revenue recognition journal vouchers for completed bookings with vendor services';

    /**
     * Execute the console command.
     */
    public function handle(RevenueRecognitionService $recService): int
    {
        $this->info("Starting Revenue Recognition & Vendor Receivable Reconciliation...");

        $query = Booking::withoutGlobalScopes()
            ->where('is_revenue_recognized', true)
            ->whereHas('vendorSales', function ($q) {
                $q->whereIn('status', ['confirmed', 'settled'])
                  ->where('include_in_invoice', true);
            });

        if ($bookingIdentifier = $this->option('booking')) {
            $query->where(function ($q) use ($bookingIdentifier) {
                $q->where('id', $bookingIdentifier)
                  ->orWhere('booking_number', $bookingIdentifier);
            });
        }

        $bookings = $query->get();

        if ($bookings->isEmpty()) {
            $this->comment("No completed bookings with vendor services found requiring reconciliation.");
            return 0;
        }

        $count = 0;
        foreach ($bookings as $booking) {
            $this->line("Reconciling Booking #{$booking->booking_number} (ID: {$booking->id})...");
            try {
                $jv = $recService->syncRevenueRecognitionVoucher($booking);
                $booking->refresh();
                $this->info("  -> Reconciled! Voucher #{$jv->voucher_no}. Receivable: Rs. " . number_format($booking->receivable_amount, 2));
                $count++;
            } catch (\Exception $e) {
                $this->error("  -> Failed to reconcile Booking #{$booking->booking_number}: " . $e->getMessage());
            }
        }

        $this->info("Completed reconciliation for {$count} booking(s). Accounts Receivable (1003) properly cleared.");
        return 0;
    }
}
