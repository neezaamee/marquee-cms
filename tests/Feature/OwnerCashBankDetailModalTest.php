<?php

namespace Tests\Feature;

use App\Livewire\Finance\CashBankManager;
use App\Livewire\Owner\BusinessOwnerDashboard;
use App\Models\Account;
use App\Models\AccountOpeningBalance;
use App\Models\AccountType;
use App\Models\CashBankAccount;
use App\Models\FinancialYear;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerCashBankDetailModalTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Role $ownerRole;
    protected User $owner;
    protected Account $bankAccount;
    protected Account $cashAccount;
    protected CashBankAccount $cbBank;
    protected CashBankAccount $cbCash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marquee = Marquee::create([
            'name' => 'The Sheraton Marquee',
            'slug' => 'the-sheraton-marquee',
            'status' => 'active',
            'address' => 'Main Boulevard',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'sheraton@test.com',
            'is_setup_completed' => true,
        ]);

        $this->ownerRole = Role::create(['name' => 'business_owner', 'label' => 'Business Owner']);

        $this->owner = User::create([
            'name' => 'Sheraton Owner',
            'email' => 'owner@sheraton.test',
            'password' => bcrypt('password'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'status' => 'active',
        ]);
        $this->owner->ownedMarquees()->attach($this->marquee->id);

        $assetType = AccountType::firstOrCreate(
            ['code' => 'CURRENT_ASSETS'],
            ['name' => 'Current Assets', 'nature' => 'Asset']
        );

        $this->bankAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_type_id' => $assetType->id,
            'account_code' => '10021',
            'name' => 'UNITED BANK LIMITED',
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_type_id' => $assetType->id,
            'account_code' => '1001',
            'name' => 'Cash in Hand Vault',
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->cbBank = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->bankAccount->id,
            'type' => 'bank',
            'bank_name' => 'UBL Main',
            'account_number' => '2103402424301',
            'iban' => 'PK00UBL0000000000',
            'status' => 'active',
        ]);

        $this->cbCash = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'account_id' => $this->cashAccount->id,
            'type' => 'cash',
            'bank_name' => 'Counter Cash Drawer',
            'status' => 'active',
        ]);

        $fy = FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $fy->id,
            'account_id' => $this->bankAccount->id,
            'debit' => 250000,
            'credit' => 0,
        ]);

        AccountOpeningBalance::create([
            'marquee_id' => $this->marquee->id,
            'financial_year_id' => $fy->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 75000,
            'credit' => 0,
        ]);
    }

    public function test_business_owner_dashboard_provides_breakdown_and_modals_for_bank_and_cash(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(BusinessOwnerDashboard::class)
            ->assertViewHas('bankAccountsBreakdown', function ($breakdown) {
                return $breakdown->count() === 1 
                    && $breakdown->first()['bank_name'] === 'UBL Main'
                    && $breakdown->first()['balance'] == 250000;
            })
            ->assertViewHas('cashAccountsBreakdown', function ($breakdown) {
                return $breakdown->count() === 1 
                    && $breakdown->first()['title'] === 'Counter Cash Drawer'
                    && $breakdown->first()['balance'] == 75000;
            })
            ->assertSeeHtml('id="bankBalanceModal"')
            ->assertSeeHtml('id="cashInHandModal"')
            ->assertSeeHtml('data-bs-target="#bankBalanceModal"')
            ->assertSeeHtml('data-bs-target="#cashInHandModal"')
            ->assertSeeHtml(route('finance.general-ledger', ['account_id' => $this->bankAccount->id]))
            ->assertSeeHtml(route('finance.general-ledger', ['account_id' => $this->cashAccount->id]))
            ->assertSeeHtml(e(route('finance.daily-cash-bank-report', ['methodFilter' => 'bank', 'tab' => 'accounts'])))
            ->assertSeeHtml(e(route('finance.daily-cash-bank-report', ['methodFilter' => 'cash', 'tab' => 'accounts'])));
    }

    public function test_cash_bank_manager_accessible_to_owner_and_displays_live_balances(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(CashBankManager::class)
            ->assertSuccessful()
            ->assertViewHas('accountBalances', function ($balances) {
                return isset($balances[$this->bankAccount->id])
                    && $balances[$this->bankAccount->id] == 250000
                    && isset($balances[$this->cashAccount->id])
                    && $balances[$this->cashAccount->id] == 75000;
            })
            ->assertViewHas('totalBankBalance', 250000.0)
            ->assertViewHas('totalCashBalance', 75000.0)
            ->assertViewHas('totalLiquidBalance', 325000.0)
            ->assertSeeHtml('Current Balance')
            ->assertSeeHtml(route('finance.general-ledger', ['account_id' => $this->bankAccount->id]));
    }
}
