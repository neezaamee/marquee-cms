<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Console\Command;

class RebuildSupplierLedgersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'suppliers:rebuild-ledgers {--supplier= : Specific supplier ID to recalculate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate running balances for supplier ledgers chronologically';

    /**
     * Execute the console command.
     */
    public function handle(InventoryService $inventoryService)
    {
        $supplierId = $this->option('supplier');

        if ($supplierId) {
            $suppliers = Supplier::where('id', $supplierId)->get();
            if ($suppliers->isEmpty()) {
                $this->error("Supplier with ID {$supplierId} not found.");
                return 1;
            }
        } else {
            $suppliers = Supplier::all();
        }

        $this->info("Recalculating ledger balances for {$suppliers->count()} supplier(s)...");

        $updatedCount = 0;
        foreach ($suppliers as $supplier) {
            $oldBalance = $supplier->current_balance;
            $inventoryService->rebuildSupplierLedger($supplier->id);
            $newBalance = $supplier->fresh()->current_balance;

            $diff = $oldBalance - $newBalance;
            if (abs($diff) > 0.001) {
                $this->line("Supplier [{$supplier->id}] '{$supplier->name}': Old Bal = {$oldBalance}, New Bal = {$newBalance} (Adjusted: {$diff})");
                $updatedCount++;
            }
        }

        $this->info("Completed! Adjusted {$updatedCount} supplier(s) that had out-of-sync ledger balances.");
        return 0;
    }
}
