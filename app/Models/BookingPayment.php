<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPayment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'payment_number',
        'booking_id',
        'vendor_sale_id',
        'account_id',
        'amount',
        'status', // received, pending_posting, posted, rejected, cancelled, reversed
        'payment_date',
        'payment_method',
        'payment_type', // advance, receivable_payment, refund, cancellation_fee, security_deposit
        'transaction_reference',
        'cheque_number',
        'bank_reference',
        'journal_voucher_id',
        'recorded_by',
        'notes',
        'posted_by',
        'posted_at',
        'accountant_notes',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
        'reversal_journal_voucher_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date',
        'account_id' => 'integer',
        'journal_voucher_id' => 'integer',
        'posted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    /**
     * Get the booking associated with this payment transaction.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the receiving/disbursing Chart of Accounts account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the Journal Voucher generated for this transaction.
     */
    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class, 'journal_voucher_id');
    }

    /**
     * Get the reversing Journal Voucher if this payment was reversed.
     */
    public function reversalJournalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class, 'reversal_journal_voucher_id');
    }

    /**
     * Get the vendor sale associated with this payment if it was a vendor service advance/installment.
     */
    public function vendorSale(): BelongsTo
    {
        return $this->belongsTo(VendorSale::class, 'vendor_sale_id');
    }

    /**
     * Get the customer ledger entry associated with this payment.
     */
    public function customerLedger()
    {
        return $this->hasOne(CustomerLedger::class, 'booking_payment_id');
    }

    /**
     * Get the user who recorded this payment transaction.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Get the accountant/user who verified & posted this transaction.
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Get the user who rejected this transaction.
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the user who reversed this transaction.
     */
    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * Status check helpers.
     */
    public function isPendingPosting(): bool
    {
        return in_array($this->status, ['pending_posting', 'received']);
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
