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
        if (!Schema::hasTable('salary_advances')) {
            Schema::create('salary_advances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('marquee_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('employee_id');
                $table->date('advance_date');
                $table->decimal('amount', 12, 2);
                $table->string('deduction_month', 7); // e.g. '2026-11'
                $table->string('payment_method', 20)->default('cash'); // 'cash', 'bank'
                $table->unsignedBigInteger('cash_bank_account_id')->nullable();
                $table->unsignedBigInteger('payment_voucher_id')->nullable();
                $table->string('status', 20)->default('pending'); // 'pending', 'deducted', 'waived'
                $table->decimal('deducted_amount', 12, 2)->default(0.00);
                $table->unsignedBigInteger('deducted_payroll_id')->nullable();
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->index(['marquee_id', 'deduction_month', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_advances');
    }
};
