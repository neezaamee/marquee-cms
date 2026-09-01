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
        Schema::create('customer_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('booking_payment_id')->nullable();
            $table->unsignedBigInteger('journal_voucher_id')->nullable();
            $table->date('transaction_date');
            $table->string('transaction_type', 50); // booking_invoice, advance_payment, revenue_recognition, receivable_payment, refund, cancellation_charge, security_deposit, adjustment
            $table->string('reference_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);
            $table->decimal('running_balance', 15, 2)->default(0.00);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('marquee_id');
            $table->index('branch_id');
            $table->index('customer_id');
            $table->index('booking_id');
            $table->index('booking_payment_id');
            $table->index('journal_voucher_id');
            $table->index('transaction_date');
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ledgers');
    }
};
