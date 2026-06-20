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
        // 1. Alter subscription_plans table to add new required fields
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('monthly_price', 10, 2)->default(0.00)->after('price');
            $table->decimal('quarterly_price', 10, 2)->default(0.00)->after('monthly_price');
            $table->decimal('semi_annual_price', 10, 2)->default(0.00)->after('quarterly_price');
            $table->decimal('annual_price', 10, 2)->default(0.00)->after('semi_annual_price');
            $table->string('currency', 10)->default('PKR')->after('annual_price');
            $table->integer('trial_days')->default(14)->after('trial_period_days');
            $table->integer('max_storage')->default(1024)->after('storage_limit_mb'); // limit in MB
            $table->integer('sort_order')->default(0)->after('status');
            $table->boolean('is_popular')->default(false)->after('sort_order');
            
            $table->foreignId('created_by')->nullable()->after('is_popular')->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
        });

        // 2. Create plan_features table
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->string('feature_name');
            $table->string('feature_key')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('Active'); // Active, Inactive
            $table->timestamps();
        });

        // 3. Create plan_feature_mappings pivot table (Many-to-Many Plan Feature)
        Schema::create('plan_feature_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('plan_features')->onDelete('cascade');
            $table->string('limit_value')->nullable(); // e.g. "10", "Unlimited", "Yes", etc.
            $table->timestamps();

            $table->unique(['plan_id', 'feature_id']);
        });

        // 4. Create billing_cycles table
        Schema::create('billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('cycle_name'); // e.g. Monthly, Quarterly, Semi Annual, Annual
            $table->integer('duration_in_months');
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->string('status')->default('Active'); // Active, Inactive
            $table->timestamps();
        });

        // 5. Create plan_billing_cycle pivot table
        Schema::create('plan_billing_cycle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->foreignId('billing_cycle_id')->constrained('billing_cycles')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['plan_id', 'billing_cycle_id']);
        });

        // 6. Create saas_invoices table
        Schema::create('saas_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('restrict');
            $table->foreignId('billing_cycle_id')->constrained('billing_cycles')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax', 15, 2)->default(0.00);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_status')->default('Unpaid'); // Unpaid, Partially Paid, Paid, Refunded
            $table->string('invoice_status')->default('Draft'); // Draft, Pending, Paid, Overdue, Cancelled
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['marquee_id', 'invoice_status']);
        });

        // 7. Create saas_payments table
        Schema::create('saas_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference')->unique();
            $table->foreignId('invoice_id')->constrained('saas_invoices')->onDelete('cascade');
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method'); // Cash, Bank Transfer, Easypaisa, JazzCash, Credit Card
            $table->string('transaction_id')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_payments');
        Schema::dropIfExists('saas_invoices');
        Schema::dropIfExists('plan_billing_cycle');
        Schema::dropIfExists('billing_cycles');
        Schema::dropIfExists('plan_feature_mappings');
        Schema::dropIfExists('plan_features');

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'monthly_price',
                'quarterly_price',
                'semi_annual_price',
                'annual_price',
                'currency',
                'trial_days',
                'max_storage',
                'sort_order',
                'is_popular',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
