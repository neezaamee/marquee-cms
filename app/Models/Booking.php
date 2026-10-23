<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, BelongsToBranch, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'branch_id',
        'booking_number',
        'customer_id',
        'event_type_id',
        'hall_id',
        'slot_id',
        'package_id',
        'booking_date',
        'start_time',
        'end_time',
        'guest_count',
        'tentative_guests',
        'confirmed_guests',
        'guest_status', // Tentative, Confirmed
        'per_plate_price',
        'package_amount',
        'hall_charges',
        'extra_charges',
        'discount_amount',
        'security_deposit',
        'tax_amount',
        'tax_rate',
        'subtotal',
        'grand_total',
        'advance_received',
        'revenue_recognized',
        'receivable_amount',
        'is_revenue_recognized',
        'revenue_recognized_at',
        'special_instructions',
        'booking_status', // Draft, Reserved, Confirmed, Completed, Cancelled, Rejected
        'payment_status', // Unpaid, Partially Paid, Paid, Refunded
        'financial_status', // Pending, Partially Paid, Fully Paid, Settled, Refunded, Cancelled
        'created_by',
        'deposit_status', // Held, Refunded, Deducted
        'deposit_refunded_amount',
        'deposit_deducted_amount',
        'deposit_notes',
        'no_food',
        'kitchen_printed_at',
        'kitchen_print_version',
        'kitchen_menu_hash',
        'kitchen_special_instructions',
        'privacy_required',
        'privacy_ladies_percentage',
        'privacy_gents_percentage',
    ];

    protected $attributes = [
        'is_revenue_recognized' => false,
        'advance_received' => 0.00,
        'revenue_recognized' => 0.00,
        'receivable_amount' => 0.00,
        'financial_status' => 'Pending',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'guest_count' => 'integer',
        'tentative_guests' => 'integer',
        'confirmed_guests' => 'integer',
        'per_plate_price' => 'float',
        'package_amount' => 'float',
        'hall_charges' => 'float',
        'extra_charges' => 'float',
        'discount_amount' => 'float',
        'security_deposit' => 'float',
        'tax_amount' => 'float',
        'tax_rate' => 'float',
        'subtotal' => 'float',
        'grand_total' => 'float',
        'advance_received' => 'float',
        'revenue_recognized' => 'float',
        'receivable_amount' => 'float',
        'is_revenue_recognized' => 'boolean',
        'revenue_recognized_at' => 'datetime',
        'deposit_refunded_amount' => 'float',
        'deposit_deducted_amount' => 'float',
        'no_food' => 'boolean',
        'kitchen_printed_at' => 'datetime',
        'kitchen_print_version' => 'integer',
        'privacy_required' => 'boolean',
        'privacy_ladies_percentage' => 'integer',
        'privacy_gents_percentage' => 'integer',
    ];

    /**
     * Get the effective guest headcount used for pricing and operations.
     */
    public function getEffectiveGuestCountAttribute(): int
    {
        if ($this->finalBill) {
            return (int) $this->finalBill->guest_count;
        }
        return $this->confirmed_guests ?? $this->tentative_guests ?? $this->guest_count ?? 0;
    }

    /**
     * Determine if guest headcount is confirmed.
     */
    public function getIsGuestConfirmedAttribute(): bool
    {
        return $this->guest_status === 'Confirmed' || !is_null($this->confirmed_guests);
    }

    /**
     * Get the effective branch for this booking (direct or via hall).
     */
    public function getEffectiveBranchAttribute(): ?Branch
    {
        return $this->branch ?? $this->hall?->branch;
    }

    /**
     * Get the effective marquee (tenant) for this booking.
     */
    public function getEffectiveMarqueeAttribute(): ?Marquee
    {
        return $this->marquee ?? ($this->branch?->marquee ?? ($this->hall?->branch?->marquee ?? auth()->user()?->marquee));
    }

    /**
     * Alias for booking_date to safely support event_date references.
     */
    public function getEventDateAttribute(): ?Carbon
    {
        return $this->booking_date;
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Booking $model) {
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }

            // Auto-populate branch_id if empty
            if (empty($model->branch_id)) {
                if (!empty($model->hall_id)) {
                    $hall = \App\Models\Hall::find($model->hall_id);
                    if ($hall && $hall->branch_id) {
                        $model->branch_id = $hall->branch_id;
                    }
                }
                if (empty($model->branch_id) && auth()->check() && auth()->user()->branch_id) {
                    $model->branch_id = auth()->user()->branch_id;
                }
            }

            // Auto-generate booking_number if empty
            if (empty($model->booking_number)) {
                $marqueeId = $model->marquee_id;
                if (empty($marqueeId) && auth()->check()) {
                    $marqueeId = auth()->user()->getActiveMarqueeId();
                }

                $prefix = Carbon::now()->format('dmY');

                // Determine the highest existing sequential number for this prefix and tenant (ignoring ALL scopes and including soft deletes)
                $existingNumbers = static::withoutGlobalScopes()
                    ->withTrashed()
                    ->where('marquee_id', $marqueeId)
                    ->where('booking_number', 'like', "{$prefix}-%")
                    ->pluck('booking_number');

                $maxSeq = 0;
                foreach ($existingNumbers as $num) {
                    $parts = explode('-', $num);
                    $seq = (int) end($parts);
                    if ($seq > $maxSeq) {
                        $maxSeq = $seq;
                    }
                }

                $nextSeq = $maxSeq + 1;

                // Loop to guarantee absolute uniqueness in case of race condition
                do {
                    $candidateNumber = $prefix . '-' . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);
                    $exists = static::withoutGlobalScopes()
                        ->withTrashed()
                        ->where('marquee_id', $marqueeId)
                        ->where('booking_number', $candidateNumber)
                        ->exists();
                    if ($exists) {
                        $nextSeq++;
                    }
                } while ($exists);

                $model->booking_number = $candidateNumber;
            }
        });

        static::updated(function ($booking) {
            if ($booking->isDirty('booking_status')) {
                $oldStatus = $booking->getOriginal('booking_status');
                $newStatus = $booking->booking_status;

                $customer = $booking->customer;
                if ($customer) {
                    // Send Email Notification dynamically to customer email
                    if ($customer->email) {
                        \Illuminate\Support\Facades\Notification::route('mail', $customer->email)
                            ->notify(new \App\Notifications\BookingStatusNotification($booking, $oldStatus, $newStatus));
                    }

                    $rawPhone = $customer->getRawOriginal('phone_number') ?? $customer->phone_number;
                    if ($rawPhone) {
                        \Illuminate\Support\Facades\Log::info("SMS ALERT Sent to {$rawPhone}: Booking #{$booking->booking_number} status updated from {$oldStatus} to {$newStatus}.");
                    }
                }
            }
        });
    }

    /**
     * Get the tenant marquee associated with this booking.
     */
    public function marquee(): BelongsTo
    {
        return $this->belongsTo(Marquee::class);
    }

    /**
     * Get the branch associated with this booking.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the customer associated with this booking.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the event type associated with this booking.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * Get the hall associated with this booking.
     */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    /**
     * Get the predefined shift slot associated with this booking.
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /**
     * Get the package associated with this booking.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the user who created this booking.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the history trail/audit log entries for this booking.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(BookingHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the payment ledger transactions for this booking.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class)->orderBy('payment_date', 'asc');
    }

    /**
     * Get the customer ledger entries for this booking.
     */
    public function customerLedgers(): HasMany
    {
        return $this->hasMany(CustomerLedger::class)->orderBy('transaction_date', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Get the effective commercial gross invoice amount.
     */
    public function getEffectiveInvoiceAmountAttribute(): float
    {
        $eventGrandTotal = (float) ($this->finalBill ? $this->finalBill->grand_total : $this->grand_total);
        $vendorCharges = (float) ($this->finalBill && $this->finalBill->vendor_charges !== null 
            ? $this->finalBill->vendor_charges 
            : ($this->relationLoaded('vendorSales') 
                ? $this->vendorSales->where('include_in_invoice', true)->whereIn('status', ['confirmed', 'settled'])->sum('sale_amount') 
                : $this->vendorSales()->whereIn('status', ['confirmed', 'settled'])->where('include_in_invoice', true)->sum('sale_amount')
            )
        );

        return $eventGrandTotal + $vendorCharges;
    }

    /**
     * Get total POSTED payments received from customer (excluding refunds).
     */
    public function getTotalPaidAttribute(): float
    {
        $received = (float) $this->payments()
            ->where('status', 'posted')
            ->whereIn('payment_type', ['advance', 'receivable_payment', 'security_deposit'])
            ->sum('amount');

        $refunded = (float) $this->payments()
            ->where('status', 'posted')
            ->where('payment_type', 'refund')
            ->sum('amount');

        return max(0.00, $received - $refunded);
    }

    /**
     * Get total payments recorded by booking staff (pending + posted).
     */
    public function getTotalReceivedPaymentsAttribute(): float
    {
        $received = (float) $this->payments()
            ->whereIn('status', ['pending_posting', 'received', 'posted'])
            ->whereIn('payment_type', ['advance', 'receivable_payment', 'security_deposit'])
            ->sum('amount');

        $refunded = (float) $this->payments()
            ->whereIn('status', ['pending_posting', 'received', 'posted'])
            ->where('payment_type', 'refund')
            ->sum('amount');

        return max(0.00, $received - $refunded);
    }

    /**
     * Get advance payments awaiting accountant posting.
     */
    public function getAdvancePendingPostingAttribute(): float
    {
        return (float) $this->payments()
            ->whereIn('status', ['pending_posting', 'received'])
            ->where('payment_type', 'advance')
            ->sum('amount');
    }

    /**
     * Get advance payments successfully posted by accountant.
     */
    public function getAdvancePostedAttribute(): float
    {
        return (float) $this->advance_received;
    }

    /**
     * Get remaining customer balance (Effective Invoice Amount - Posted Payments).
     */
    public function getRemainingCustomerBalanceAttribute(): float
    {
        if (in_array($this->booking_status, ['Cancelled', 'Rejected'])) {
            return 0.00;
        }

        return max(0.00, (float) $this->effective_invoice_amount - (float) $this->total_paid);
    }

    /**
     * Get current Customer Advance Liability (Contract Liability).
     * Strictly 0 if revenue is recognized or event cancelled.
     */
    public function getEffectiveAdvanceLiabilityAttribute(): float
    {
        if ($this->is_revenue_recognized || in_array($this->booking_status, ['Completed', 'Cancelled', 'Rejected'])) {
            return 0.00;
        }

        return (float) $this->advance_received;
    }

    /**
     * Get current Accounts Receivable (amount owed by customer after revenue is recognized).
     */
    public function getEffectiveReceivableAttribute(): float
    {
        if (!$this->is_revenue_recognized) {
            return 0.00;
        }

        return (float) $this->receivable_amount;
    }

    /**
     * Check if the booking is financially settled.
     */
    public function getIsFinanciallySettledAttribute(): bool
    {
        return $this->is_revenue_recognized && $this->effective_receivable <= 0.01;
    }

    /**
     * Get the customized extra services (add-ons) for this booking.
     */
    public function extraServices(): HasMany
    {
        return $this->hasMany(BookingExtraService::class);
    }

    /**
     * Get the vendor / partner services assigned to this booking.
     */
    public function vendorSales(): HasMany
    {
        return $this->hasMany(VendorSale::class)->whereIn('status', ['confirmed', 'settled'])->orderBy('id', 'asc');
    }

    /**
     * Get the customized menu item selections for this booking.
     */
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'booking_menu_items')
                    ->withPivot(['id', 'custom_note', 'managed_by_host', 'sort_order'])
                    ->withTimestamps()
                    ->orderBy('booking_menu_items.sort_order', 'asc')
                    ->orderBy('booking_menu_items.id', 'asc');
    }

    /**
     * Get the allocated halls for this booking.
     */
    public function halls(): BelongsToMany
    {
        return $this->belongsToMany(Hall::class, 'booking_halls')
                    ->withTimestamps();
    }

    /**
     * Get the final bill adjustments record for this booking.
     */
    public function finalBill(): HasOne
    {
        return $this->hasOne(BookingFinalBill::class);
    }

    /**
     * Get the checklists (operational tasks) for this booking.
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(EventChecklist::class);
    }

    /**
     * Scope query for today's events.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('booking_date', Carbon::today());
    }

    /**
     * Scope query for upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->whereDate('booking_date', '>=', Carbon::today())
                     ->whereNotIn('booking_status', ['Cancelled', 'Rejected', 'Completed']);
    }

    /**
     * Scope query for events in the next 7 days.
     */
    public function scopeNext7Days($query)
    {
        return $query->whereBetween('booking_date', [Carbon::today(), Carbon::today()->addDays(7)])
                     ->whereNotIn('booking_status', ['Cancelled', 'Rejected']);
    }

    /**
     * Scope query for pending approval bookings.
     */
    public function scopePendingApproval($query)
    {
        return $query->whereIn('booking_status', ['Draft', 'Pending']);
    }

    /**
     * Get the vendor commissions booked against this event.
     */
    public function vendorBookings(): HasMany
    {
        return $this->hasMany(VendorBooking::class);
    }

    /**
     * Get the print audit logs for kitchen slips.
     */
    public function kitchenPrintLogs(): HasMany
    {
        return $this->hasMany(KitchenPrintLog::class)->orderBy('printed_at', 'desc');
    }

    /**
     * Compute a hash of booked menu items and effective guest count.
     */
    public function computeMenuHash(): string
    {
        $items = $this->menuItems()
            ->select('menu_items.id')
            ->orderBy('menu_items.id')
            ->get()
            ->pluck('id')
            ->toArray();

        return md5(implode('-', $items) . ':' . $this->effective_guest_count . ':' . ($this->kitchen_special_instructions ?? ''));
    }

    /**
     * Determine if the booking menu was modified after the last kitchen print.
     */
    public function getIsKitchenMenuModifiedAttribute(): bool
    {
        if (empty($this->kitchen_printed_at) || empty($this->kitchen_menu_hash)) {
            return false;
        }

        return $this->computeMenuHash() !== $this->kitchen_menu_hash;
    }

    /**
     * Normalize date string into Y-m-d format for database queries.
     */
    public static function normalizeDateForFilter(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }
        try {
            $trimmed = trim($date);
            if (preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{4}$/', $trimmed)) {
                $parts = preg_split('/[-\/]/', $trimmed);
                return Carbon::createFromDate((int) $parts[2], (int) $parts[1], (int) $parts[0])->format('Y-m-d');
            }
            return Carbon::parse($trimmed)->format('Y-m-d');
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Scope a query to apply all operational booking dashboard filters.
     * Used uniformly by Livewire BookingList, CSV Export, and Printable PDF Reports.
     */
    public function scopeApplyBookingFilters($query, array $filters = [], $user = null)
    {
        $user = $user ?? auth()->user();
        $marqueeId = $user ? $user->getActiveMarqueeId() : null;

        $paymentsSubqueries = [
            'payments as posted_received' => function ($q) {
                $q->where('status', 'posted')
                  ->whereIn('payment_type', ['advance', 'receivable_payment', 'security_deposit']);
            },
            'payments as posted_refunds' => function ($q) {
                $q->where('status', 'posted')
                  ->where('payment_type', 'refund');
            }
        ];

        // Ensure soft-deleted bookings (e.g. Cancelled) are queryable if looking up all or cancelled
        $query->withTrashed()->with([
            'customer',
            'hall',
            'hall.branch',
            'branch',
            'halls',
            'slot',
            'package',
            'payments',
            'eventType',
            'creator',
            'finalBill',
            'vendorSales',
            'marquee',
        ])
        ->withSum($paymentsSubqueries, 'amount');

        if ($marqueeId) {
            $query->where('marquee_id', $marqueeId);
        }

        // Branch restriction: lock if user is non-owner/non-superadmin restricted to a branch
        $filterBranch = $filters['filterBranch'] ?? null;
        if ($user && $user->branch_id && !$user->isBusinessOwner() && !$user->isSuperAdmin()) {
            $filterBranch = $user->branch_id;
        }

        if (!empty($filterBranch)) {
            $query->where(function ($q) use ($filterBranch) {
                $q->where('branch_id', $filterBranch)
                  ->orWhereHas('hall', fn($hq) => $hq->where('branch_id', $filterBranch))
                  ->orWhereHas('halls', fn($hq) => $hq->where('branch_id', $filterBranch));
            });
        }

        // Search text
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $searchTerm = '%' . $search . '%';
            $cleanDigits = preg_replace('/[^0-9]/', '', $search);
            $isSqlite = DB::connection()->getDriverName() === 'sqlite';
            $fullNameSql = $isSqlite
                ? "(COALESCE(first_name, '') || ' ' || COALESCE(last_name, ''))"
                : "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))";

            $query->where(function ($q) use ($searchTerm, $cleanDigits, $fullNameSql) {
                $q->where('booking_number', 'like', $searchTerm)
                  ->orWhereHas('customer', function ($cq) use ($searchTerm, $cleanDigits, $fullNameSql) {
                      $cq->where('first_name', 'like', $searchTerm)
                        ->orWhere('last_name', 'like', $searchTerm)
                        ->orWhereRaw("{$fullNameSql} LIKE ?", [$searchTerm])
                        ->orWhere('company_name', 'like', $searchTerm)
                        ->orWhere('customer_code', 'like', $searchTerm)
                        ->orWhere('referred_by_name', 'like', $searchTerm);

                      if (!empty($cleanDigits)) {
                          $cq->orWhere('phone_number', 'like', '%' . $cleanDigits . '%')
                            ->orWhere('alternate_phone', 'like', '%' . $cleanDigits . '%');
                      }
                  });
            });
        }

        // Booking Status
        if (!empty($filters['filterStatus'])) {
            $query->where('booking_status', $filters['filterStatus']);
        }

        // Payment Status
        if (!empty($filters['filterPaymentStatus'])) {
            $query->where('payment_status', $filters['filterPaymentStatus']);
        }

        // Hall / Venue (checks both primary hall_id and multi-hall pivot)
        if (!empty($filters['filterHall'])) {
            $hallId = $filters['filterHall'];
            $query->where(function ($q) use ($hallId) {
                $q->where('hall_id', $hallId)
                  ->orWhereHas('halls', fn($hq) => $hq->where('halls.id', $hallId));
            });
        }

        // Event Type
        if (!empty($filters['filterEventType'])) {
            $query->where('event_type_id', $filters['filterEventType']);
        }

        // Guest Status
        if (!empty($filters['filterGuestStatus'])) {
            $query->where('guest_status', $filters['filterGuestStatus']);
        }

        // Created By (Operator)
        if (!empty($filters['filterCreatedBy'])) {
            $query->where('created_by', $filters['filterCreatedBy']);
        }

        // Dates
        $startDate = static::normalizeDateForFilter($filters['filterDateStart'] ?? null);
        $endDate = static::normalizeDateForFilter($filters['filterDateEnd'] ?? null);

        if (!empty($startDate)) {
            $query->where('booking_date', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $query->where('booking_date', '<=', $endDate);
        }

        // Care Of
        if (!empty($filters['filterCareOf'])) {
            $careOf = $filters['filterCareOf'];
            $query->whereHas('customer', function ($cq) use ($careOf) {
                $cq->where('referred_by_name', $careOf);
            });
        }

        // Per Head Price
        $perHead = $filters['filterPerHead'] ?? null;
        $perHeadAmount = $filters['filterPerHeadAmount'] ?? null;
        if ($perHead === 'zero') {
            $query->where(function ($q) {
                $q->whereNull('per_plate_price')
                  ->orWhere('per_plate_price', '<=', 0)
                  ->orWhere('no_food', true);
            });
        } elseif ($perHead === 'has_rate') {
            $query->where('per_plate_price', '>', 0);
        } elseif ($perHead === 'specific' && is_numeric($perHeadAmount)) {
            $query->where('per_plate_price', (float) $perHeadAmount);
        } elseif (is_numeric($perHead)) {
            $query->where('per_plate_price', (float) $perHead);
        }

        // Balance Status
        $balanceStatus = $filters['filterBalanceStatus'] ?? null;
        $quickShortcut = $filters['filterQuickShortcut'] ?? null;
        if ($balanceStatus === 'outstanding' || $quickShortcut === 'outstanding') {
            $todayStr = Carbon::today()->format('Y-m-d');
            $query->whereNotIn('booking_status', ['Cancelled', 'Rejected'])
                  ->where(function ($q) use ($todayStr) {
                      $q->where('booking_status', 'Completed')
                        ->orWhere('is_revenue_recognized', true)
                        ->orWhereDate('booking_date', '<', $todayStr);
                  })
                  ->where('payment_status', '!=', 'Paid');
        } elseif ($balanceStatus === 'fully_paid') {
            $query->where('payment_status', 'Paid');
        }

        // Quick Shortcuts
        if ($quickShortcut === 'today') {
            $query->whereDate('booking_date', Carbon::today())->whereNotIn('booking_status', ['Cancelled', 'Rejected']);
        } elseif ($quickShortcut === 'upcoming') {
            $query->whereDate('booking_date', '>=', Carbon::today())->whereNotIn('booking_status', ['Cancelled', 'Rejected', 'Completed']);
        } elseif ($quickShortcut === 'next_7_days') {
            $query->whereBetween('booking_date', [Carbon::today(), Carbon::today()->addDays(7)])->whereNotIn('booking_status', ['Cancelled', 'Rejected']);
        } elseif ($quickShortcut === 'this_month') {
            $query->whereBetween('booking_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->whereNotIn('booking_status', ['Cancelled', 'Rejected']);
        } elseif ($quickShortcut === 'pending') {
            $query->whereIn('booking_status', ['Draft', 'Pending']);
        } elseif ($quickShortcut === 'confirmed') {
            $query->where('booking_status', 'Confirmed');
        } elseif ($quickShortcut === 'tentative') {
            $query->whereIn('booking_status', ['Reserved', 'Draft']);
        }

        return $query;
    }
}
