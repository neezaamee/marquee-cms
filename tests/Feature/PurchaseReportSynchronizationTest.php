<?php

namespace Tests\Feature;

use App\Livewire\Inventory\SupplierList;
use App\Livewire\Owner\BusinessOwnerDashboard;
use App\Livewire\Purchases\PurchaseDashboard;
use App\Models\Branch;
use App\Models\Marquee;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseReportSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;
    protected Branch $branch;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::firstOrCreate(['name' => 'business_owner'], ['label' => 'Business Owner']);
        $this->marquee = Marquee::factory()->create(['name' => 'Test Sheraton Marquee']);
        $this->branch = Branch::factory()->create(['marquee_id' => $this->marquee->id, 'name' => 'Main Branch']);

        $this->owner = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('Password123!'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => null,
            'status' => 'active',
        ]);
        $this->owner->ownedMarquees()->attach($this->marquee->id);

        $this->supplier = Supplier::factory()->create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Supreme Poultry & Meat',
            'opening_balance' => 0,
        ]);
    }

    public function test_owner_dashboard_procurement_dashboard_and_supplier_directory_report_matching_totals()
    {
        $this->actingAs($this->owner);

        // 1. Create a valid posted purchase invoice for Rs. 100,000
        $invoice1 = PurchaseInvoice::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'PINV-TEST-001',
            'purchase_date' => now()->format('Y-m-d'),
            'subtotal' => 100000,
            'tax' => 0,
            'discount' => 0,
            'gross_amount' => 100000,
            'net_amount' => 100000,
            'paid_amount' => 0,
            'balance_due' => 100000,
            'status' => 'Posted',
        ]);

        // 2. Create another purchase invoice for Rs. 50,000 then soft-delete it
        $deletedInvoice = PurchaseInvoice::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'invoice_number' => 'PINV-TEST-DELETED',
            'purchase_date' => now()->format('Y-m-d'),
            'subtotal' => 50000,
            'tax' => 0,
            'discount' => 0,
            'gross_amount' => 50000,
            'net_amount' => 50000,
            'paid_amount' => 0,
            'balance_due' => 50000,
            'status' => 'Draft',
        ]);
        $deletedInvoice->delete(); // Soft delete

        // 3. Create a purchase return for Rs. 15,000
        $return = PurchaseReturn::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'return_number' => 'PRET-TEST-001',
            'return_date' => now()->format('Y-m-d'),
            'subtotal' => 15000,
            'tax' => 0,
            'gross_amount' => 15000,
            'net_amount' => 15000,
            'status' => 'Approved',
        ]);

        // Expected Net Procurement: 100,000 - 15,000 = 85,000
        // Soft-deleted invoice of 50,000 must NOT be counted in any dashboard!

        // Test 1: Owner Dashboard
        Livewire::test(BusinessOwnerDashboard::class)
            ->set('timeframe', 'this_month')
            ->assertViewHas('totalPurchases', 85000.0)
            ->assertSee('Rs. 85.0k')
            ->assertSee('85,000');

        // Test 2: Purchase & Procurement Dashboard
        Livewire::test(PurchaseDashboard::class)
            ->set('filterRange', 'this_month')
            ->assertViewHas('effectiveProcurementSpend', 85000.0)
            ->assertSee('85,000.00');

        // Test 3: Supplier Directory
        Livewire::test(SupplierList::class)
            ->call('setDateRange', 'this_month')
            ->assertViewHas('totalStockValueOverall', 85000.0)
            ->assertSee('Total Stock Received Overall')
            ->assertSee('85,000.00');
    }
}
