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
        Schema::create('booking_final_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->onDelete('cascade');
            $table->integer('guest_count')->default(0);
            $table->decimal('per_plate_price', 15, 2)->default(0.00);
            $table->decimal('package_amount', 15, 2)->default(0.00);
            $table->decimal('hall_charges', 15, 2)->default(0.00);
            $table->decimal('extra_charges', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('booking_final_bill_extra_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_bill_id')->constrained('booking_final_bills')->onDelete('cascade');
            $table->string('service_name');
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->integer('quantity')->default(1);
            $table->decimal('total_price', 15, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_final_bill_extra_services');
        Schema::dropIfExists('booking_final_bills');
    }
};
