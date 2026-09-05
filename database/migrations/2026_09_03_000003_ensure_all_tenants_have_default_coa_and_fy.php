<?php

use App\Models\Marquee;
use App\Services\AccountingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $accountingService = app(AccountingService::class);
        $marquees = Marquee::all();

        foreach ($marquees as $marquee) {
            $accountingService->seedTenantDefaultAccounts($marquee->id);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: preserve user accounting data
    }
};
