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
        // 1. Add department_id to menu_categories
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('marquee_id')->constrained('departments')->onDelete('set null');
        });

        // 2. Add kitchen tracking fields to bookings table
        Schema::table('bookings', function (Blueprint $table) {
            $table->dateTime('kitchen_printed_at')->nullable()->after('special_instructions');
            $table->integer('kitchen_print_version')->default(0)->after('kitchen_printed_at');
            $table->string('kitchen_menu_hash')->nullable()->after('kitchen_print_version');
            $table->text('kitchen_special_instructions')->nullable()->after('kitchen_menu_hash');
        });

        // 3. Create kitchen_print_logs table
        Schema::create('kitchen_print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('marquee_id')->constrained('marquees')->onDelete('cascade');
            $table->foreignId('printed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('language')->default('bilingual'); // bilingual, english, urdu
            $table->integer('version_number')->default(1);
            $table->dateTime('printed_at');
            $table->timestamps();

            $table->index(['booking_id', 'printed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_print_logs');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['kitchen_printed_at', 'kitchen_print_version', 'kitchen_menu_hash', 'kitchen_special_instructions']);
        });

        Schema::table('menu_categories', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
