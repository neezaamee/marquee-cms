<?php

namespace App\Services;

use App\Models\Account;
use App\Models\InventoryItem;
use App\Models\InventorySetting;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Generate the next unique item code for a tenant.
     */
    public function generateNextItemCode(?int $marqueeId): string
    {
        $existingCodes = InventoryItem::withTrashed()
            ->where('marquee_id', $marqueeId)
            ->pluck('item_code');

        $maxNum = 0;
        foreach ($existingCodes as $c) {
            if (preg_match('/ITEM-(\d+)/', (string) $c, $m)) {
                $num = (int) $m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        do {
            $maxNum++;
            $code = 'ITEM-' . str_pad($maxNum, 5, '0', STR_PAD_LEFT);
        } while ($existingCodes->contains($code));

        return $code;
    }

    /**
     * Generate the next unique supplier code for a tenant.
     */
    public function generateNextSupplierCode(?int $marqueeId): string
    {
        $existingCodes = Supplier::withTrashed()
            ->where('marquee_id', $marqueeId)
            ->pluck('supplier_code');

        $maxNum = 0;
        foreach ($existingCodes as $c) {
            if (preg_match('/SUP-(\d+)/', (string) $c, $m)) {
                $num = (int) $m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        do {
            $maxNum++;
            $code = 'SUP-' . str_pad($maxNum, 5, '0', STR_PAD_LEFT);
        } while ($existingCodes->contains($code));

        return $code;
    }

    /**
     * Get or create default inventory settings for a tenant.
     */
    public function getOrCreateSettings(?int $marqueeId): InventorySetting
    {
        $settings = InventorySetting::where('marquee_id', $marqueeId)->first();

        if (!$settings) {
            // Find default pre-seeded accounts
            $inventoryAccount = Account::where('marquee_id', $marqueeId)
                ->where('account_code', '1004') // Pre-seeded Inventory Asset
                ->first();

            $payableAccount = Account::where('marquee_id', $marqueeId)
                ->where('account_code', '2001') // Pre-seeded Accounts Payable
                ->first();

            $settings = InventorySetting::create([
                'marquee_id' => $marqueeId,
                'inventory_asset_account_id' => $inventoryAccount?->id,
                'accounts_payable_account_id' => $payableAccount?->id,
            ]);
        }

        return $settings;
    }

    /**
     * Record a transaction in the supplier's ledger.
     */
    public function recordSupplierTransaction(
        ?int $marqueeId,
        int $supplierId,
        string $date,
        float $debit,
        float $credit,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $voucherNo = null,
        ?string $description = null
    ): SupplierLedger {
        return DB::transaction(function () use (
            $marqueeId,
            $supplierId,
            $date,
            $debit,
            $credit,
            $referenceType,
            $referenceId,
            $voucherNo,
            $description
        ) {
            // 1. Get supplier
            $supplier = Supplier::findOrFail($supplierId);

            // 2. Fetch last running balance
            $lastLedger = SupplierLedger::where('supplier_id', $supplierId)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $previousBalance = $lastLedger ? $lastLedger->running_balance : $supplier->opening_balance;

            // Credits increase payables, Debits decrease payables
            $newBalance = $previousBalance + $credit - $debit;

            // 3. Create entry
            return SupplierLedger::create([
                'marquee_id' => $marqueeId,
                'supplier_id' => $supplierId,
                'transaction_date' => $date,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'voucher_no' => $voucherNo,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $newBalance,
                'description' => $description,
            ]);
        });
    }

    /**
     * Recalculate supplier's ledger balances from scratch (optional utility for data integrity).
     */
    public function rebuildSupplierLedger(int $supplierId): void
    {
        DB::transaction(function () use ($supplierId) {
            $supplier = Supplier::findOrFail($supplierId);
            $ledgers = SupplierLedger::where('supplier_id', $supplierId)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $runningBalance = $supplier->opening_balance;

            foreach ($ledgers as $ledger) {
                $runningBalance = $runningBalance + $ledger->credit - $ledger->debit;
                $ledger->update(['running_balance' => $runningBalance]);
            }
        });
    }
}
