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
        // 1. Alter vendors table
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'vendor_code')) {
                $table->string('vendor_code')->nullable()->after('marquee_id');
            }
            if (!Schema::hasColumn('vendors', 'alternate_phone')) {
                $table->string('alternate_phone')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('vendors', 'address')) {
                $table->text('address')->nullable()->after('email');
            }
            if (!Schema::hasColumn('vendors', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('vendors', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('city')->constrained('branches')->onDelete('set null');
            }
            if (!Schema::hasColumn('vendors', 'tax_ntn')) {
                $table->string('tax_ntn')->nullable()->after('branch_id');
            }
            if (!Schema::hasColumn('vendors', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('tax_ntn');
            }
            if (!Schema::hasColumn('vendors', 'account_title')) {
                $table->string('account_title')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('vendors', 'account_number_iban')) {
                $table->string('account_number_iban')->nullable()->after('account_title');
            }
            if (!Schema::hasColumn('vendors', 'payment_terms')) {
                $table->string('payment_terms')->default('Net 30')->after('account_number_iban');
            }
            if (!Schema::hasColumn('vendors', 'notes')) {
                $table->text('notes')->nullable()->after('payment_terms');
            }
            if (!Schema::hasColumn('vendors', 'opening_balance')) {
                $table->decimal('opening_balance', 12, 2)->default(0.00)->after('notes');
            }
            if (!Schema::hasColumn('vendors', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 2. Create vendor_services table
        if (!Schema::hasTable('vendor_services')) {
            Schema::create('vendor_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
                $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
                $table->string('service_name');
                $table->string('service_code');
                $table->text('description')->nullable();
                $table->string('unit')->default('Event'); // Event, Day, Session, Hour
                $table->decimal('default_sale_price', 12, 2)->default(0.00);
                $table->string('status')->default('active'); // active, inactive
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['vendor_id', 'service_code']);
            });
        }

        // 3. Create vendor_commission_agreements table
        if (!Schema::hasTable('vendor_commission_agreements')) {
            Schema::create('vendor_commission_agreements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
                $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
                $table->foreignId('vendor_service_id')->nullable()->constrained('vendor_services')->onDelete('cascade');
                $table->string('agreement_number');
                $table->string('commission_type')->default('percentage'); // percentage, fixed_per_event, fixed_monthly, hybrid
                $table->decimal('commission_percentage', 5, 2)->default(0.00);
                $table->decimal('fixed_commission_amount', 12, 2)->default(0.00);
                $table->decimal('monthly_fixed_amount', 12, 2)->default(0.00);
                $table->decimal('minimum_commission', 12, 2)->default(0.00);
                $table->decimal('maximum_commission', 12, 2)->default(0.00);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->text('settlement_terms')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('active'); // active, expired, draft, terminated
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Create vendor_sales table
        if (!Schema::hasTable('vendor_sales')) {
            Schema::create('vendor_sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->string('vendor_sale_number');
                $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
                $table->foreignId('vendor_service_id')->nullable()->constrained('vendor_services')->onDelete('set null');
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $table->foreignId('agreement_id')->nullable()->constrained('vendor_commission_agreements')->onDelete('set null');
                $table->date('event_date');
                $table->date('sale_date');
                $table->decimal('quantity', 10, 2)->default(1.00);
                $table->string('unit')->default('Event');
                $table->decimal('sale_amount', 12, 2)->default(0.00);
                $table->string('commission_type')->default('percentage');
                $table->decimal('commission_rate', 5, 2)->default(0.00);
                $table->decimal('commission_amount', 12, 2)->default(0.00);
                $table->decimal('vendor_net_amount', 12, 2)->default(0.00);
                $table->string('status')->default('confirmed'); // draft, confirmed, settled, cancelled
                $table->text('override_reason')->nullable();
                $table->foreignId('override_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 5. Create vendor_settlements table
        if (!Schema::hasTable('vendor_settlements')) {
            Schema::create('vendor_settlements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->string('settlement_number');
                $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
                $table->date('settlement_date');
                $table->decimal('total_sales_amount', 12, 2)->default(0.00);
                $table->decimal('total_commission_amount', 12, 2)->default(0.00);
                $table->decimal('net_payable_amount', 12, 2)->default(0.00);
                $table->decimal('paid_amount', 12, 2)->default(0.00);
                $table->decimal('remaining_balance', 12, 2)->default(0.00);
                $table->string('payment_method')->default('Cash'); // Cash, Bank Transfer, Cheque
                $table->string('reference_number')->nullable();
                $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
                $table->foreignId('journal_voucher_id')->nullable()->constrained('journal_vouchers')->onDelete('set null');
                $table->string('status')->default('fully_settled'); // pending, partially_settled, fully_settled, disputed, cancelled
                $table->text('remarks')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 6. Create vendor_ledgers table
        if (!Schema::hasTable('vendor_ledgers')) {
            Schema::create('vendor_ledgers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
                $table->foreignId('vendor_sale_id')->nullable()->constrained('vendor_sales')->onDelete('set null');
                $table->foreignId('settlement_id')->nullable()->constrained('vendor_settlements')->onDelete('set null');
                $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
                $table->date('transaction_date');
                $table->string('reference_number');
                $table->string('transaction_type'); // sale_credit, commission_debit, settlement_payout, opening_balance, adjustment
                $table->text('description');
                $table->decimal('sale_amount', 12, 2)->default(0.00);
                $table->decimal('commission_amount', 12, 2)->default(0.00);
                $table->decimal('payment_amount', 12, 2)->default(0.00);
                $table->decimal('running_balance', 12, 2)->default(0.00);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();

                $table->index(['vendor_id', 'transaction_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_ledgers');
        Schema::dropIfExists('vendor_settlements');
        Schema::dropIfExists('vendor_sales');
        Schema::dropIfExists('vendor_commission_agreements');
        Schema::dropIfExists('vendor_services');

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'vendor_code', 'alternate_phone', 'address', 'city', 'branch_id',
                'tax_ntn', 'bank_name', 'account_title', 'account_number_iban',
                'payment_terms', 'notes', 'opening_balance'
            ]);
        });
    }
};
