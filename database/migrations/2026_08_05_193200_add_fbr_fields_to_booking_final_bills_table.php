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
        Schema::table('booking_final_bills', function (Blueprint $table) {
            $table->string('fbr_invoice_number')->nullable()->after('notes');
            $table->string('fbr_sync_status')->default('pending')->after('fbr_invoice_number');
            $table->timestamp('fbr_sync_time')->nullable()->after('fbr_sync_status');
            $table->string('usin')->nullable()->after('fbr_sync_time');
            $table->text('qr_code')->nullable()->after('usin');
            $table->text('fbr_response_message')->nullable()->after('qr_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_final_bills', function (Blueprint $table) {
            $table->dropColumn([
                'fbr_invoice_number',
                'fbr_sync_status',
                'fbr_sync_time',
                'usin',
                'qr_code',
                'fbr_response_message',
            ]);
        });
    }
};
