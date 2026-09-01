<?php

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Marquee;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $liabType = AccountType::where('code', 'CURRENT_LIABILITIES')->first();
        if (!$liabType) {
            $liabType = AccountType::create(['name' => 'Current Liabilities', 'code' => 'CURRENT_LIABILITIES', 'nature' => 'Liability']);
        }

        $incomeType = AccountType::where('code', 'OPERATING_REVENUE')->first();
        if (!$incomeType) {
            $incomeType = AccountType::create(['name' => 'Operating Revenue', 'code' => 'OPERATING_REVENUE', 'nature' => 'Income']);
        }

        $marquees = Marquee::all();

        foreach ($marquees as $marquee) {
            $parentLiab = Account::where('marquee_id', $marquee->id)->where('account_code', '2000')->first();
            $parentIncome = Account::where('marquee_id', $marquee->id)->where('account_code', '4000')->first();

            // 2003: Customer Advances / Contract Liabilities
            Account::updateOrCreate(
                [
                    'marquee_id' => $marquee->id,
                    'account_code' => '2003',
                ],
                [
                    'name' => 'Customer Advances / Contract Liabilities',
                    'parent_id' => $parentLiab?->id,
                    'account_type_id' => $liabType->id,
                    'nature' => 'Liability',
                    'is_active' => true,
                    'system_generated' => true,
                    'description' => 'Unearned customer booking advances and contract liabilities held prior to event completion',
                ]
            );

            // 2004: Sales Tax Payable
            Account::updateOrCreate(
                [
                    'marquee_id' => $marquee->id,
                    'account_code' => '2004',
                ],
                [
                    'name' => 'Sales Tax Payable',
                    'parent_id' => $parentLiab?->id,
                    'account_type_id' => $liabType->id,
                    'nature' => 'Liability',
                    'is_active' => true,
                    'system_generated' => true,
                    'description' => 'Government sales tax / PRA / SRB collected on event bookings',
                ]
            );

            // 4004: Cancellation Charges Income
            Account::updateOrCreate(
                [
                    'marquee_id' => $marquee->id,
                    'account_code' => '4004',
                ],
                [
                    'name' => 'Cancellation Charges Income',
                    'parent_id' => $parentIncome?->id,
                    'account_type_id' => $incomeType->id,
                    'nature' => 'Income',
                    'is_active' => true,
                    'system_generated' => true,
                    'description' => 'Earned forfeiture and cancellation penalties from cancelled event bookings',
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Account::whereIn('account_code', ['2003', '2004', '4004'])->delete();
    }
};
