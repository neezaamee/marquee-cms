<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->string('payment_number', 50)->nullable()->after('id');
            $table->string('status', 50)->default('pending_posting')->after('amount'); // pending_posting, posted, rejected, cancelled, reversed
            $table->string('cheque_number', 100)->nullable()->after('transaction_reference');
            $table->string('bank_reference', 100)->nullable()->after('cheque_number');
            $table->unsignedBigInteger('posted_by')->nullable()->after('journal_voucher_id');
            $table->timestamp('posted_at')->nullable()->after('posted_by');
            $table->text('accountant_notes')->nullable()->after('notes');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('accountant_notes');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->unsignedBigInteger('reversed_by')->nullable()->after('rejection_reason');
            $table->timestamp('reversed_at')->nullable()->after('reversed_by');
            $table->text('reversal_reason')->nullable()->after('reversed_at');
            $table->unsignedBigInteger('reversal_journal_voucher_id')->nullable()->after('reversal_reason');

            $table->index('payment_number');
            $table->index('status');
            $table->foreign('posted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reversal_journal_voucher_id')->references('id')->on('journal_vouchers')->onDelete('set null');
        });

        // Backfill existing payments
        $existingPayments = DB::table('booking_payments')->get();
        foreach ($existingPayments as $p) {
            $status = $p->journal_voucher_id ? 'posted' : 'pending_posting';
            $paymentNo = 'PAY-' . date('Y', strtotime($p->created_at ?: 'now')) . '-' . str_pad((string)$p->id, 5, '0', STR_PAD_LEFT);
            DB::table('booking_payments')->where('id', $p->id)->update([
                'payment_number' => $paymentNo,
                'status' => $status,
                'posted_by' => $p->journal_voucher_id ? $p->recorded_by : null,
                'posted_at' => $p->journal_voucher_id ? ($p->created_at ?: now()) : null,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            $table->dropForeign(['posted_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['reversed_by']);
            $table->dropForeign(['reversal_journal_voucher_id']);

            $table->dropIndex(['payment_number']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'payment_number',
                'status',
                'cheque_number',
                'bank_reference',
                'posted_by',
                'posted_at',
                'accountant_notes',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'reversed_by',
                'reversed_at',
                'reversal_reason',
                'reversal_journal_voucher_id',
            ]);
        });
    }
};
