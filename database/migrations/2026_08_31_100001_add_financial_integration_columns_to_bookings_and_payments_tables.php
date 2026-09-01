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
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('advance_received', 15, 2)->default(0.00)->after('grand_total');
            $table->decimal('revenue_recognized', 15, 2)->default(0.00)->after('advance_received');
            $table->decimal('receivable_amount', 15, 2)->default(0.00)->after('revenue_recognized');
            $table->boolean('is_revenue_recognized')->default(false)->after('receivable_amount');
            $table->timestamp('revenue_recognized_at')->nullable()->after('is_revenue_recognized');
            $table->string('financial_status', 50)->default('Pending')->after('payment_status'); // Pending, Partially Paid, Fully Paid, Settled, Refunded, Cancelled
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->after('payment_method');
            $table->string('payment_type', 50)->default('advance')->after('account_id'); // advance, receivable_payment, refund, cancellation_fee, security_deposit
            $table->unsignedBigInteger('journal_voucher_id')->nullable()->after('payment_type');

            $table->index('account_id');
            $table->index('journal_voucher_id');
            $table->index('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'advance_received',
                'revenue_recognized',
                'receivable_amount',
                'is_revenue_recognized',
                'revenue_recognized_at',
                'financial_status',
            ]);
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropIndex(['journal_voucher_id']);
            $table->dropIndex(['payment_type']);

            $table->dropColumn([
                'account_id',
                'payment_type',
                'journal_voucher_id',
            ]);
        });
    }
};
