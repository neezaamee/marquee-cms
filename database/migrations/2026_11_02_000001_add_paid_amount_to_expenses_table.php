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
        if (Schema::hasTable('expenses') && !Schema::hasColumn('expenses', 'paid_amount')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->decimal('paid_amount', 15, 2)->default(0.00)->after('total_amount_base');
            });

            // Populate existing paid expenses with their total amount
            DB::statement("UPDATE expenses SET paid_amount = total_amount WHERE payment_status = 'Paid'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'paid_amount')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }
    }
};

