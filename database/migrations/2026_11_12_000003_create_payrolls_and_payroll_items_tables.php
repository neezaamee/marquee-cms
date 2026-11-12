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
        if (!Schema::hasTable('payrolls')) {
            Schema::create('payrolls', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('marquee_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('payroll_month', 7); // e.g. '2026-11'
                $table->string('batch_number', 50)->unique();
                $table->integer('total_employees')->default(0);
                $table->decimal('total_basic_salary', 14, 2)->default(0.00);
                $table->decimal('total_allowances', 14, 2)->default(0.00);
                $table->decimal('total_overtime_amount', 14, 2)->default(0.00);
                $table->decimal('total_gross_salary', 14, 2)->default(0.00);
                $table->decimal('total_advance_deductions', 14, 2)->default(0.00);
                $table->decimal('total_absence_deductions', 14, 2)->default(0.00);
                $table->decimal('total_other_deductions', 14, 2)->default(0.00);
                $table->decimal('total_net_payable', 14, 2)->default(0.00);
                $table->decimal('total_paid', 14, 2)->default(0.00);
                $table->string('status', 30)->default('draft'); // 'draft', 'generated', 'approved', 'paid', 'partially_paid'
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->date('payment_date')->nullable();
                $table->string('payment_method', 30)->default('cash'); // 'cash', 'bank', 'mixed'
                $table->unsignedBigInteger('cash_bank_account_id')->nullable();
                $table->unsignedBigInteger('payment_voucher_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
                $table->index(['marquee_id', 'payroll_month']);
            });
        }

        if (!Schema::hasTable('payroll_items')) {
            Schema::create('payroll_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payroll_id');
                $table->unsignedBigInteger('marquee_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('employee_id');
                $table->string('salary_slip_number', 50)->unique();
                
                // Earnings
                $table->decimal('basic_salary', 12, 2)->default(0.00);
                $table->integer('total_working_days')->default(30);
                $table->integer('present_days')->default(0);
                $table->integer('absent_days')->default(0);
                $table->integer('late_days')->default(0);
                $table->integer('half_days')->default(0);
                $table->integer('paid_leaves')->default(0);
                $table->decimal('overtime_hours', 6, 2)->default(0.00);
                $table->decimal('overtime_rate', 10, 2)->default(0.00);
                $table->decimal('overtime_amount', 12, 2)->default(0.00);
                $table->json('allowances')->nullable();
                $table->decimal('total_allowances', 12, 2)->default(0.00);
                $table->decimal('gross_salary', 12, 2)->default(0.00);
                
                // Deductions
                $table->decimal('absence_deduction', 12, 2)->default(0.00);
                $table->decimal('late_deduction', 12, 2)->default(0.00);
                $table->decimal('advance_deduction', 12, 2)->default(0.00);
                $table->json('other_deductions')->nullable();
                $table->decimal('total_other_deductions', 12, 2)->default(0.00);
                $table->decimal('total_deductions', 12, 2)->default(0.00);
                
                // Net Pay & Status
                $table->decimal('net_salary', 12, 2)->default(0.00);
                $table->decimal('paid_amount', 12, 2)->default(0.00);
                $table->string('payment_status', 30)->default('unpaid'); // 'unpaid', 'paid', 'partially_paid'
                $table->string('payment_method', 30)->default('cash');
                $table->unsignedBigInteger('cash_bank_account_id')->nullable();
                $table->unsignedBigInteger('payment_voucher_id')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->index(['marquee_id', 'employee_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
    }
};
