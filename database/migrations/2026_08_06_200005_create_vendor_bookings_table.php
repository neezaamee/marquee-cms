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
        Schema::create('vendor_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marquee_id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('booking_id');
            $table->decimal('agreed_price', 15, 2);
            $table->decimal('commission_rate', 5, 2)->default(0.00); // percentage commission e.g. 10.00%
            $table->decimal('commission_amount', 15, 2)->default(0.00);
            $table->string('payment_status')->default('Unpaid'); // Unpaid, Paid, Partially Paid
            $table->timestamps();

            // Constraints
            $table->foreign('marquee_id')->references('id')->on('marquees')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bookings');
    }
};
