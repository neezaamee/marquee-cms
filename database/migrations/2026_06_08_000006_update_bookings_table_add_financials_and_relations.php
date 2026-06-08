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
            $table->string('booking_number')->nullable()->after('marquee_id');
            $table->foreignId('customer_id')->nullable()->after('booking_number')->constrained('customers')->onDelete('cascade');
            $table->foreignId('event_type_id')->nullable()->after('customer_id')->constrained('event_types')->onDelete('set null');
            $table->foreignId('package_id')->nullable()->after('slot_id')->constrained('packages')->onDelete('set null');
            
            $table->integer('guest_count')->default(0)->after('package_id');
            $table->decimal('per_plate_price', 10, 2)->default(0.00)->after('guest_count');
            $table->decimal('package_amount', 15, 2)->default(0.00)->after('per_plate_price');
            $table->decimal('hall_charges', 15, 2)->default(0.00)->after('package_amount');
            $table->decimal('extra_charges', 15, 2)->default(0.00)->after('hall_charges');
            $table->decimal('discount_amount', 15, 2)->default(0.00)->after('extra_charges');
            $table->decimal('security_deposit', 15, 2)->default(0.00)->after('discount_amount');
            $table->decimal('tax_amount', 15, 2)->default(0.00)->after('security_deposit');
            $table->decimal('subtotal', 15, 2)->default(0.00)->after('tax_amount');
            $table->decimal('grand_total', 15, 2)->default(0.00)->after('subtotal');
            
            $table->text('special_instructions')->nullable()->after('grand_total');
            $table->string('payment_status')->default('Unpaid')->after('booking_status'); // Unpaid, Partially Paid, Paid, Refunded

            // Composite unique index for booking number scoped per tenant (marquee_id)
            $table->unique(['marquee_id', 'booking_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['marquee_id', 'booking_number']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['event_type_id']);
            $table->dropForeign(['package_id']);

            $table->dropColumn([
                'booking_number',
                'customer_id',
                'event_type_id',
                'package_id',
                'guest_count',
                'per_plate_price',
                'package_amount',
                'hall_charges',
                'extra_charges',
                'discount_amount',
                'security_deposit',
                'tax_amount',
                'subtotal',
                'grand_total',
                'special_instructions',
                'payment_status',
            ]);
        });
    }
};
