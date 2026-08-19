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
        // 1. Add subscription columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->after('role_id')->constrained('subscription_plans')->onDelete('restrict');
            $table->dateTime('subscription_trial_ends_at')->nullable()->after('subscription_plan_id');
            $table->dateTime('subscription_ends_at')->nullable()->after('subscription_trial_ends_at');
        });

        // 2. Drop subscription and owner columns from marquees table
        Schema::table('marquees', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropForeign(['owner_user_id']);
            $table->dropColumn(['subscription_plan_id', 'subscription_trial_ends_at', 'subscription_ends_at', 'owner_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add subscription and owner columns to marquees table
        Schema::table('marquees', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->onDelete('restrict');
            $table->dateTime('subscription_trial_ends_at')->nullable();
            $table->dateTime('subscription_ends_at')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // 2. Drop subscription columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn(['subscription_plan_id', 'subscription_trial_ends_at', 'subscription_ends_at']);
        });
    }
};
