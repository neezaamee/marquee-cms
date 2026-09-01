<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'vendor_sale_id',
        'account_id',
        'amount',
        'payment_date',
        'payment_method',
        'payment_type', // advance, receivable_payment, refund, cancellation_fee, security_deposit
        'transaction_reference',
        'journal_voucher_id',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date',
        'account_id' => 'integer',
        'journal_voucher_id' => 'integer',
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
}
