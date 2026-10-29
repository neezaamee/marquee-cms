<?php

namespace Tests\Feature;

use App\Livewire\Inventory\SupplierList;
use App\Models\Branch;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierOpeningBalanceRecalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $staff;
    protected Marquee $marquee;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Marquee Owner']);
        $staffRole = Role::create(['name' => 'inventory_manager', 'label' => 'Inventory Manager']);

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 1000,
            'billing_interval' => 'monthly',
            'max_branches' => 5,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Grand Palace Marquee',
            'slug' => 'grand-palace',
            'status' => 'active',
            'address' => '12 Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'email' => 'contact@grandpalace.test',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Gulberg Wing',
            'code' => 'GW-01',
            'address' => '12 Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001234567',
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'name' => 'Owner Asim',
            'email' => 'owner@grandpalace.test',
            'password' => bcrypt('password123'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
            'is_owner' => true,
        ]);

        $this->staff = User::create([
            'name' => 'Staff Kashif',
            'email' => 'kashif@grandpalace.test',
            'password' => bcrypt('password123'),
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'role_id' => $staffRole->id,
            'status' => 'active',
            'is_owner' => false,
        ]);
    }

    public function test_owner_can_add_opening_balance_later_with_automatic_ledger_recalculation(): void
    {
        $inventoryService = app(InventoryService::class);

        // 1. Create supplier with initial opening balance = 0
        $supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'supplier_code' => 'SUP-TEST-01',
            'name' => 'Al-Madina Rice Mill',
            'mobile_number' => '03001122334',
            'opening_balance' => 0.00,
            'status' => 'Active',
        ]);

        // 2. Add an invoice transaction: Credit 20,000 -> Running balance = 20,000
        $inventoryService->recordSupplierTransaction(
            $this->marquee->id,
            $supplier->id,
            '2026-03-01',
            0.00,
            20000.00,
            'PurchaseInvoice',
            101,
            'INV-001',
            'Rice 50 bags purchase'
        );

        // 3. Add a payment transaction: Debit 5,000 -> Running balance = 15,000
        $inventoryService->recordSupplierTransaction(
            $this->marquee->id,
            $supplier->id,
            '2026-03-05',
            5000.00,
            0.00,
            'VendorPayment',
            201,
            'PMT-001',
            'Partial payment'
        );

        $this->assertEquals(15000.00, (float) $supplier->fresh()->current_balance);

        // 4. Owner opens edit modal and sets opening balance to 50,000
        Livewire::actingAs($this->owner)
            ->test(SupplierList::class)
            ->call('edit', $supplier->id)
            ->set('opening_balance', 50000.00)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSessionHas('success', 'Supplier profile and ledger balances updated successfully.');

        $supplier->refresh();
        $this->assertEquals(50000.00, (float) $supplier->opening_balance);

        // Assert OpeningBalance ledger entry created
        $this->assertDatabaseHas('supplier_ledgers', [
            'supplier_id' => $supplier->id,
            'reference_type' => 'OpeningBalance',
            'credit' => 50000.00,
        ]);

        // Assert overall running balance recalculated: 50,000 + 20,000 - 5,000 = 65,000
        $this->assertEquals(65000.00, (float) $supplier->current_balance);

        // Check running balance on the payment transaction
        $paymentLedger = SupplierLedger::where('supplier_id', $supplier->id)
            ->where('reference_type', 'VendorPayment')
            ->first();
        $this->assertEquals(65000.00, (float) $paymentLedger->running_balance);

        // 5. Owner updates opening balance again to 30,000
        Livewire::actingAs($this->owner)
            ->test(SupplierList::class)
            ->call('edit', $supplier->id)
            ->set('opening_balance', 30000.00)
            ->call('save')
            ->assertHasNoErrors();

        $supplier->refresh();
        $this->assertEquals(30000.00, (float) $supplier->opening_balance);

        // Recalculated: 30,000 + 20,000 - 5,000 = 45,000
        $this->assertEquals(45000.00, (float) $supplier->current_balance);
    }

    public function test_non_owner_cannot_modify_opening_balance(): void
    {
        $supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'supplier_code' => 'SUP-TEST-02',
            'name' => 'Kashmiri Spice Traders',
            'mobile_number' => '03009988776',
            'opening_balance' => 10000.00,
            'status' => 'Active',
        ]);

        // Non-owner staff attempts to edit opening balance
        Livewire::actingAs($this->staff)
            ->test(SupplierList::class)
            ->call('edit', $supplier->id)
            ->set('opening_balance', 99999.00)
            ->call('save')
            ->assertHasErrors(['opening_balance']);

        // Assert supplier opening balance did not change
        $this->assertEquals(10000.00, (float) $supplier->fresh()->opening_balance);
    }

    public function test_non_owner_can_still_update_other_supplier_fields(): void
    {
        $supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'supplier_code' => 'SUP-TEST-03',
            'name' => 'Original Name Traders',
            'mobile_number' => '03005544332',
            'opening_balance' => 10000.00,
            'status' => 'Active',
            'notes' => 'Old notes',
        ]);

        // Non-owner staff updates notes without altering opening balance
        Livewire::actingAs($this->staff)
            ->test(SupplierList::class)
            ->call('edit', $supplier->id)
            ->set('notes', 'Updated delivery instructions')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSessionHas('success', 'Supplier profile updated successfully.');

        $supplier->refresh();
        $this->assertEquals('Updated delivery instructions', $supplier->notes);
        $this->assertEquals(10000.00, (float) $supplier->opening_balance);
    }
}
