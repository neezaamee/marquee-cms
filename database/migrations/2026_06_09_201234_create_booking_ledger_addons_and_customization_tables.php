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
        // 1. Extra Services (Catalog)
        Schema::create('extra_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->string('service_name');
            $table->decimal('default_price', 15, 2)->default(0.00);
            $table->string('status')->default('Active'); // Active, Inactive
            $table->timestamps();

            $table->index(['marquee_id', 'status']);
        });

        // 2. Booking Payments Ledger
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method'); // Cash, Bank Transfer, Cheque, Card, etc.
            $table->string('transaction_reference')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_id']);
        });

        // 3. Booking Extra Services (Booked Add-ons)
        Schema::create('booking_extra_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('extra_service_id')->nullable()->constrained('extra_services')->onDelete('set null');
            $table->string('service_name');
            $table->decimal('unit_price', 15, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();

            $table->index(['booking_id']);
        });

        // 4. Booking Custom Menu Items
        Schema::create('booking_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->string('custom_note')->nullable();
            $table->timestamps();

            $table->index(['booking_id']);
        });

        // 5. Update Bookings Table
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('deposit_status')->default('Held')->after('security_deposit'); // Held, Refunded, Deducted
            $table->decimal('deposit_refunded_amount', 15, 2)->default(0.00)->after('deposit_status');
            $table->decimal('deposit_deducted_amount', 15, 2)->default(0.00)->after('deposit_refunded_amount');
            $table->text('deposit_notes')->nullable()->after('deposit_deducted_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_status',
                'deposit_refunded_amount',
                'deposit_deducted_amount',
                'deposit_notes'
            ]);
        });

        Schema::dropIfExists('booking_menu_items');
        Schema::dropIfExists('booking_extra_services');
        Schema::dropIfExists('booking_payments');
        Schema::dropIfExists('extra_services');
    }
};
