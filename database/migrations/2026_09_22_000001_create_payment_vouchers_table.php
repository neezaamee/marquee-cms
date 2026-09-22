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
        Schema::create('payment_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('financial_year_id')->nullable()->constrained('financial_years')->onDelete('set null');

            // Voucher Identification
            $table->string('voucher_no', 50);
            $table->string('voucher_type', 10); // 'CPV' (Cash Payment Voucher) or 'BPV' (Bank Payment Voucher)
            $table->date('voucher_date');

            // Payee Details
            $table->string('payee_type', 30)->default('general'); // 'supplier', 'vendor', 'expense', 'general'
            $table->unsignedBigInteger('payee_id')->nullable();
            $table->string('payee_name');
            $table->string('payee_cnic', 50)->nullable();
            $table->string('payee_phone', 50)->nullable();

            // Banking / Source Account
            $table->foreignId('cash_bank_account_id')->constrained('cash_bank_accounts')->onDelete('restrict');
            $table->foreignId('debit_account_id')->constrained('accounts')->onDelete('restrict');

            // Amounts
            $table->decimal('amount', 15, 2);
            $table->string('amount_in_words', 500)->nullable();

            // Payment instrument details
            $table->string('payment_method', 30)->default('Cash'); // Cash, Cheque, Bank Transfer, Online
            $table->string('cheque_no', 50)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('reference_no', 100)->nullable(); // External invoice/bill/challan ref
            $table->text('description')->nullable();

            // Workflow Status: draft, approved, posted, cancelled
            $table->string('status', 20)->default('approved'); // Default approved so ready to print voucher

            // Posting / Sub-ledger links
            $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->onDelete('set null');

            // Workflow Signatories & Audit
            $table->foreignId('prepared_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('checked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('disbursed_at')->nullable();
            $table->text('receiver_signature_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['marquee_id', 'voucher_no'], 'marquee_voucher_no_unique');
            $table->index(['marquee_id', 'voucher_type', 'status']);
            $table->index(['marquee_id', 'voucher_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_vouchers');
    }
};
