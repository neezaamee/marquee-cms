<?php

namespace Tests\Feature;

use App\Livewire\Inventory\SupplierList;
use App\Livewire\Owner\BusinessOwnerDashboard;
use App\Models\Branch;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClosingPayablesDueTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $ownerRole = Role::where('name', 'owner')->first();

        $this->marquee = Marquee::create([
            'name' => 'Royal Palace Marquee',
            'slug' => 'royal-palace-marquee',
            'status' => 'active',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'royal@example.com',
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Wing',
            'code' => 'MW-01',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'branch@example.com',
            'status' => 'Active',
            'is_main_branch' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Marquee Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole ? $ownerRole->id : null,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
    }

    public function test_supplier_list_shows_closing_payables_due_when_past_date_is_selected(): void
    {
        $this->actingAs($this->owner);

        // Supplier with 100k credit on Oct 5 and 100k payment on Oct 25
        $supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'supplier_code' => 'SUP-HIST-001',
            'name' => 'Crown Catering Supplies',
            'mobile_number' => '03001234567',
            'opening_balance' => 0.00,
            'status' => 'Active',
        ]);

        SupplierLedger::create([
            'marquee_id' => $this->marquee->id,
            'supplier_id' => $supplier->id,
            'transaction_date' => '2026-10-05',
            'reference_type' => 'PurchaseInvoice',
            'reference_id' => 1,
            'voucher_no' => 'PINV-001',
            'debit' => 0,
            'credit' => 100000,
            'running_balance' => 100000,
            'description' => 'Meat purchase',
        ]);

        SupplierLedger::create([
            'marquee_id' => $this->marquee->id,
            'supplier_id' => $supplier->id,
            'transaction_date' => '2026-10-25',
            'reference_type' => 'PaymentVoucher',
            'reference_id' => 1,
            'voucher_no' => 'CPV-001',
            'debit' => 100000,
            'credit' => 0,
            'running_balance' => 0,
            'description' => 'Payment cleared',
        ]);

        // When filtering with past date cutoff of 2026-10-15:
        // Card title should be "Closing Payables due" and outstanding should be 100,000.
        Livewire::test(SupplierList::class)
            ->set('dateRange', 'custom')
            ->set('dateFrom', '2026-10-01')
            ->set('dateTo', '2026-10-15')
            ->assertSee('Closing Payables due')
            ->assertSee('As of 15 Oct, 2026')
            ->assertSee('Rs. 100,000.00');

        // When filtering after full settlement (2026-10-30):
        Livewire::test(SupplierList::class)
            ->set('dateRange', 'custom')
            ->set('dateFrom', '2026-10-01')
            ->set('dateTo', '2026-10-30')
            ->assertSee('Closing Payables due')
            ->assertSee('As of 30 Oct, 2026')
            ->assertSee('Rs. 0.00');
    }

    public function test_dashboard_shows_closing_payables_due_when_past_custom_range_is_selected(): void
    {
        $this->actingAs($this->owner);

        $supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'supplier_code' => 'SUP-HIST-002',
            'name' => 'Royal Flour Mills',
            'mobile_number' => '03007654321',
            'opening_balance' => 0.00,
            'status' => 'Active',
        ]);

        SupplierLedger::create([
            'marquee_id' => $this->marquee->id,
            'supplier_id' => $supplier->id,
            'transaction_date' => '2026-10-05',
            'reference_type' => 'PurchaseInvoice',
            'reference_id' => 2,
            'voucher_no' => 'PINV-002',
            'debit' => 0,
            'credit' => 80000,
            'running_balance' => 80000,
            'description' => 'Flour delivery',
        ]);

        Livewire::test(BusinessOwnerDashboard::class)
            ->set('timeframe', 'custom')
            ->set('customDateFrom', '01-10-2026')
            ->set('customDateTo', '15-10-2026')
            ->assertSee('Closing Payables due')
            ->assertSee('As of 15 Oct, 2026')
            ->assertSee('Rs. 80.0k');
    }
}
