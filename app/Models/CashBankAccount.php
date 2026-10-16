<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasAuditColumns;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashBankAccount extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditColumns, LogsActivity;

    protected $fillable = [
        'marquee_id',
        'account_id',
        'type', // cash, bank
        'bank_name',
        'account_number',
        'iban',
        'branch_name',
        'status', // active, inactive
    ];

    protected $appends = [
        'account_name',
        'display_name',
    ];

    /**
     * Get the associated Chart of Accounts account.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Accessor for account_name (used across multiple views and forms).
     */
    public function getAccountNameAttribute(): string
    {
        if ($this->type === 'bank' && !empty($this->bank_name)) {
            return $this->bank_name;
        }

        if ($this->relationLoaded('account') && $this->account && !empty($this->account->name)) {
            return $this->account->name;
        }

        if ($this->account_id) {
            $account = Account::withoutGlobalScope('tenant')->find($this->account_id);
            if ($account && !empty($account->name)) {
                return $account->name;
            }
        }

        return !empty($this->bank_name) ? $this->bank_name : ucfirst($this->type) . ' Account';
    }

    /**
     * Accessor for a rich display name with account number / details.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->account_name;
        if (!empty($this->account_number)) {
            $name .= ' (' . $this->account_number . ')';
        }
        return $name;
    }

    /**
     * Accessor for current balance derived from GL opening balance + posted journal vouchers.
     */
    public function getCurrentBalanceAttribute(): float
    {
        if (isset($this->attributes['current_balance'])) {
            return (float) $this->attributes['current_balance'];
        }

        if (!$this->account_id) {
            return 0.00;
        }

        $opening = (float) \Illuminate\Support\Facades\DB::table('account_opening_balances')
            ->where('account_id', $this->account_id)
            ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));

        $movements = (float) \Illuminate\Support\Facades\DB::table('journal_voucher_items')
            ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
            ->whereNull('journal_vouchers.deleted_at')
            ->where('journal_vouchers.status', 'posted')
            ->where('journal_voucher_items.account_id', $this->account_id)
            ->sum(\Illuminate\Support\Facades\DB::raw('journal_voucher_items.debit - journal_voucher_items.credit'));

        return round($opening + $movements, 2);
    }

    /**
     * Ensure default Cash (1001) and Bank (1002) accounts are registered in cash_bank_accounts for a marquee.
     */
    public static function ensureDefaultAccountsForMarquee($marqueeId): void
    {
        if (!$marqueeId) {
            return;
        }

        // Cash account 1001
        $cashAcc = Account::withoutGlobalScope('tenant')
            ->where('marquee_id', $marqueeId)
            ->where('account_code', '1001')
            ->first();

        if ($cashAcc) {
            static::withoutGlobalScope('tenant')->firstOrCreate(
                [
                    'marquee_id' => $marqueeId,
                    'account_id' => $cashAcc->id,
                ],
                [
                    'type' => 'cash',
                    'status' => 'active',
                ]
            );
        }

        // Bank account 1002
        $bankAcc = Account::withoutGlobalScope('tenant')
            ->where('marquee_id', $marqueeId)
            ->where('account_code', '1002')
            ->first();

        if ($bankAcc) {
            static::withoutGlobalScope('tenant')->firstOrCreate(
                [
                    'marquee_id' => $marqueeId,
                    'account_id' => $bankAcc->id,
                ],
                [
                    'type' => 'bank',
                    'bank_name' => $bankAcc->name ?: 'General Bank Account',
                    'status' => 'active',
                ]
            );
        }

        // Also check any child/custom accounts under nature 'Asset' where account_type_id == 3 (Current Assets / Cash & Bank)
        $otherCashBankAccs = Account::withoutGlobalScope('tenant')
            ->where('marquee_id', $marqueeId)
            ->where('nature', 'Asset')
            ->where(function ($q) {
                $q->where('account_type_id', 3)
                  ->orWhereIn('account_code', ['1001', '1002']);
            })
            ->whereNotIn('account_code', ['1000', '1003', '1004']) // Exclude Group Header, Accounts Receivable, Inventory
            ->get();

        foreach ($otherCashBankAccs as $cAcc) {
            $isBank = stripos($cAcc->name, 'bank') !== false;
            static::withoutGlobalScope('tenant')->firstOrCreate(
                [
                    'marquee_id' => $marqueeId,
                    'account_id' => $cAcc->id,
                ],
                [
                    'type' => $isBank ? 'bank' : 'cash',
                    'bank_name' => $isBank ? $cAcc->name : null,
                    'status' => 'active',
                ]
            );
        }
    }

    /**
     * Retrieve all active Cash & Bank accounts for a marquee with accurately computed GL balances.
     */
    public static function getAccountsWithBalances($marqueeId): \Illuminate\Support\Collection
    {
        if (!$marqueeId) {
            return collect();
        }

        static::ensureDefaultAccountsForMarquee($marqueeId);

        $accounts = static::withoutGlobalScope('tenant')
            ->where('marquee_id', $marqueeId)
            ->where('status', 'active')
            ->with('account')
            ->orderBy('type', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($accounts->isEmpty()) {
            return collect();
        }

        $accountIds = $accounts->pluck('account_id')->filter()->unique()->toArray();

        if (!empty($accountIds)) {
            // Bulk compute opening balances
            $openingBalances = \Illuminate\Support\Facades\DB::table('account_opening_balances')
                ->whereIn('account_id', $accountIds)
                ->select('account_id', \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(debit - credit), 0) as balance'))
                ->groupBy('account_id')
                ->pluck('balance', 'account_id');

            // Bulk compute posted journal movements
            $journalMovements = \Illuminate\Support\Facades\DB::table('journal_voucher_items')
                ->join('journal_vouchers', 'journal_voucher_items.journal_voucher_id', '=', 'journal_vouchers.id')
                ->whereNull('journal_vouchers.deleted_at')
                ->where('journal_vouchers.status', 'posted')
                ->where('journal_vouchers.marquee_id', $marqueeId)
                ->whereIn('journal_voucher_items.account_id', $accountIds)
                ->select('journal_voucher_items.account_id', \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(journal_voucher_items.debit - journal_voucher_items.credit), 0) as balance'))
                ->groupBy('journal_voucher_items.account_id')
                ->pluck('balance', 'account_id');

            foreach ($accounts as $acc) {
                $coaId = $acc->account_id;
                $bal = (float) (($openingBalances[$coaId] ?? 0.0) + ($journalMovements[$coaId] ?? 0.0));
                $acc->current_balance = round($bal, 2);
            }
        }

        return $accounts;
    }
}
