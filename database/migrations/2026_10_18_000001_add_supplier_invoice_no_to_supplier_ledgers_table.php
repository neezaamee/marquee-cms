<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('supplier_ledgers') && !Schema::hasColumn('supplier_ledgers', 'supplier_invoice_no')) {
            Schema::table('supplier_ledgers', function (Blueprint $table) {
                $table->string('supplier_invoice_no')->nullable()->after('voucher_no');
            });

            // Backfill existing PurchaseInvoice records
            if (Schema::hasTable('purchase_invoices')) {
                $invoices = \App\Models\PurchaseInvoice::all(['id', 'reference_number', 'invoice_number']);
                foreach ($invoices as $inv) {
                    $invNo = !empty($inv->reference_number) ? $inv->reference_number : $inv->invoice_number;
                    \App\Models\SupplierLedger::where('reference_type', 'PurchaseInvoice')
                        ->where('reference_id', $inv->id)
                        ->update(['supplier_invoice_no' => $invNo]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('supplier_ledgers') && Schema::hasColumn('supplier_ledgers', 'supplier_invoice_no')) {
            Schema::table('supplier_ledgers', function (Blueprint $table) {
                $table->dropColumn('supplier_invoice_no');
            });
        }
    }
};
