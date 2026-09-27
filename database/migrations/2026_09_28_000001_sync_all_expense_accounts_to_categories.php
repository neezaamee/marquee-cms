<?php

use App\Services\AccountingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(AccountingService::class)->syncExpenseAccountsWithCategories();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: preserve synced categories
    }
};
