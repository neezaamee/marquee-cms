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
        // 1. Expense Categories
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('category_code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('default_account_id')->nullable(); // GL Account mapping
            $table->decimal('default_tax_rate', 5, 2)->default(0.00);
            $table->decimal('default_budget_amount', 15, 2)->default(0.00);
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('expense_categories')->onDelete('set null');
            $table->foreign('default_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->index(['marquee_id', 'category_code']);
            $table->index('is_active');
        });

        // 2. Expense Types (Configurable operational categories)
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id')->nullable(); // Null means global system-defined type
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->index(['marquee_id', 'code']);
        });

        // 3. Currencies
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id')->nullable();
            $table->string('code', 10);
            $table->string('name', 50);
            $table->string('symbol', 10);
            $table->boolean('is_base')->default(false);
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->index(['marquee_id', 'code']);
        });

        // 4. Petty Cash Accounts
        Schema::create('petty_cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('account_name');
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->unsignedBigInteger('custodian_id')->nullable(); // Employee / User managing drawer
            $table->decimal('limit_amount', 15, 2)->default(0.00);
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('gl_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('custodian_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['marquee_id', 'branch_id']);
        });

        // 5. Expenses (Header)
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('expense_number');
            $table->date('expense_date');
            $table->string('department')->nullable();
            $table->string('cost_center')->nullable();
            $table->unsignedBigInteger('expense_category_id')->nullable();
            $table->unsignedBigInteger('expense_type_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable(); // Vendor
            $table->unsignedBigInteger('employee_id')->nullable(); // Employee (Salary advance / reimbursement)
            $table->unsignedBigInteger('booking_id')->nullable(); // Associated event booking
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('purchase_invoice_id')->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000);
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();
            $table->decimal('amount', 15, 2)->default(0.00); // Subtotal
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00); // Transaction Currency Total
            $table->decimal('total_amount_base', 15, 2)->default(0.00); // Base Currency Total
            $table->string('payment_method'); // Cash, Bank, Accounts Payable, Petty Cash
            $table->unsignedBigInteger('cash_bank_account_id')->nullable(); // References CashBankAccount
            $table->unsignedBigInteger('petty_cash_account_id')->nullable();
            $table->string('payment_status')->default('Unpaid'); // Unpaid, Partially Paid, Paid
            $table->string('status')->default('Draft'); // Draft, Submitted, Pending Approval, Approved, Rejected, Paid, Posted, Cancelled, Closed
            $table->date('due_date')->nullable();
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('journal_voucher_id')->nullable(); // Linked voucher when posted
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->onDelete('set null');
            $table->foreign('expense_type_id')->references('id')->on('expense_types')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->onDelete('set null');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('restrict');
            $table->foreign('petty_cash_account_id')->references('id')->on('petty_cash_accounts')->onDelete('set null');
            $table->foreign('journal_voucher_id')->references('id')->on('journal_vouchers')->onDelete('set null');
            
            $table->index(['marquee_id', 'expense_number']);
            $table->index(['marquee_id', 'branch_id']);
            $table->index('status');
            $table->index('expense_date');
        });

        // 6. Expense Line Items (Multi-line entry)
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_id');
            $table->unsignedBigInteger('expense_category_id');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->onDelete('restrict');
        });

        // 7. Recurring Expenses (Templates)
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('expense_category_id');
            $table->unsignedBigInteger('expense_type_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('department')->nullable();
            $table->string('cost_center')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->string('frequency'); // Daily, Weekly, Monthly, Quarterly, Yearly
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('last_generated_date')->nullable();
            $table->date('next_generation_date');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->onDelete('restrict');
            $table->foreign('expense_type_id')->references('id')->on('expense_types')->onDelete('restrict');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            
            $table->index(['marquee_id', 'is_active', 'next_generation_date'], 're_marquee_active_next_gen_idx');
        });

        // 8. Utility Bills details
        Schema::create('expense_utility_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_id');
            $table->string('utility_type', 100); // Electricity, Gas, Water, Internet, Telephone
            $table->string('consumer_number', 100);
            $table->string('account_number')->nullable();
            $table->string('billing_period'); // e.g. July 2026
            $table->decimal('previous_reading', 15, 2)->nullable();
            $table->decimal('current_reading', 15, 2)->nullable();
            $table->decimal('late_charges', 15, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
            $table->index(['utility_type', 'consumer_number']);
        });

        // 9. Maintenance details
        Schema::create('expense_maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_id');
            $table->string('maintenance_type'); // Building, AC, Generator, Vehicle etc.
            $table->string('asset_name');
            $table->date('scheduled_date');
            $table->date('completion_date')->nullable();
            $table->integer('warranty_period_months')->default(0);
            $table->timestamps();

            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
        });

        // 10. Configurable Approval Rules
        Schema::create('expense_approval_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('department')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('min_amount', 15, 2)->default(0.00);
            $table->decimal('max_amount', 15, 2)->nullable();
            $table->unsignedBigInteger('approver_role_id');
            $table->integer('sequence')->default(1);
            $table->timestamps();

            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('expense_categories')->onDelete('set null');
            $table->foreign('approver_role_id')->references('id')->on('roles')->onDelete('cascade');
            
            $table->index(['marquee_id', 'min_amount']);
        });

        // 11. Approval Logs
        Schema::create('expense_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->string('action'); // Approved, Rejected, Clarified
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });

        // 12. Petty Cash Reconciliation
        Schema::create('petty_cash_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('petty_cash_account_id');
            $table->date('reconciliation_date');
            $table->decimal('system_balance', 15, 2);
            $table->decimal('physical_balance', 15, 2);
            $table->decimal('difference', 15, 2);
            $table->string('status')->default('Balanced'); // Balanced, Discrepancy
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('petty_cash_account_id')->references('id')->on('petty_cash_accounts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // 13. Expense Budgets
        Schema::create('expense_budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('department')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->integer('year');
            $table->integer('month')->nullable(); // Null means Annual Budget
            $table->decimal('allocated_amount', 15, 2)->default(0.00);
            $table->decimal('consumed_amount', 15, 2)->default(0.00);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('expense_categories')->onDelete('set null');
            
            $table->index(['marquee_id', 'year', 'month']);
        });

        // 14. Expense Attachments
        Schema::create('expense_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expense_id')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->integer('file_size');
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_attachments');
        Schema::dropIfExists('expense_budgets');
        Schema::dropIfExists('petty_cash_reconciliations');
        Schema::dropIfExists('expense_approvals');
        Schema::dropIfExists('expense_approval_rules');
        Schema::dropIfExists('expense_maintenances');
        Schema::dropIfExists('expense_utility_bills');
        Schema::dropIfExists('recurring_expenses');
        Schema::dropIfExists('expense_items');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('petty_cash_accounts');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('expense_types');
        Schema::dropIfExists('expense_categories');
    }
};
