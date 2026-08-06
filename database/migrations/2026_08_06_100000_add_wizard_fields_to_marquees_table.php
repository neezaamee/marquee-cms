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
        Schema::table('marquees', function (Blueprint $table) {
            $table->string('business_type')->nullable()->after('name');
            $table->string('country')->default('Pakistan')->after('city');
            $table->string('timezone')->default('Asia/Karachi')->after('country');
            $table->string('currency')->default('PKR')->after('timezone');
            $table->boolean('is_setup_completed')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marquees', function (Blueprint $table) {
            $table->dropColumn([
                'business_type',
                'country',
                'timezone',
                'currency',
                'is_setup_completed'
            ]);
        });
    }
};
