<?php

namespace Tests\Feature\Security;

use App\Livewire\Finance\CashBankManager;
use App\Livewire\Finance\JournalVoucherForm;
use App\Livewire\Finance\PaymentsList;
use App\Livewire\Finance\PaymentVoucherDetail;
use App\Livewire\Inventory\StockTakeManager;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\JournalVoucher;
use App\Models\Marquee;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\PaymentVoucher;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class Phase4FinancialAndOperationalSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdminRole;
    protected $ownerRole;
    protected $branchManagerRole;
    protected $accountantRole;
    protected $storeKeeperRole;
    protected $staffRole;

    protected $postPaymentsPermission;
    protected $viewPaymentsPermission;
    protected $manageAccountingPermission;
    protected $inventoryAdjustPermission;
    protected $viewInventoryPermission;
    protected $viewMenusPermission;

    protected $marqueeA;
    protected $marqueeB;

    protected $branchA1;
    protected $branchB1;

    protected $superAdmin;
    protected $ownerA;
    protected $accountantA;
    protected $storeKeeperA;
    protected $unauthorizedUserA;

    protected $accountTypeCurrentAsset;
    protected $accountA_Cash;
    protected $accountB_Cash;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $this->superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Admin']);
        $this->ownerRole = Role::create(['name' => 'business_owner', 'label' => 'Business Owner']);
        $this->branchManagerRole = Role::create(['name' => 'branch_manager', 'label' => 'Branch Manager']);
        $this->accountantRole = Role::create(['name' => 'accountant', 'label' => 'Accountant']);
        $this->staffRole = Role::create(['name' => 'staff', 'label' => 'Staff Member']);

        // 2. Permissions
        $this->postPaymentsPermission = Permission::create(['name' => 'post_payments', 'label' => 'Post Payments']);
        $this->viewPaymentsPermission = Permission::create(['name' => 'view_payments', 'label' => 'View Payments']);
        $this->manageAccountingPermission = Permission::create(['name' => 'manage_accounting', 'label' => 'Manage Accounting']);
        $this->inventoryAdjustPermission = Permission::create(['name' => 'inventory.adjust', 'label' => 'Adjust Inventory']);
        $this->viewInventoryPermission = Permission::create(['name' => 'view_inventory', 'label' => 'View Inventory']);
        $this->viewMenusPermission = Permission::create(['name' => 'view_menus', 'label' => 'View Menus']);

        // 3. Marquees
        $this->marqueeA = Marquee::create([
            'name' => 'Royal Palace Marquee A',
            'address' => 'Mall Road A',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001111111',
            'email' => 'marqueeA@test.com',
            'status' => 'active',
        ]);

        $this->marqueeB = Marquee::create([
            'name' => 'Grand Banquet Marquee B',
            'address' => 'Clifton Road B',
            'city' => 'Karachi',
            'province' => 'Sindh',
            'phone' => '03002222222',
            'email' => 'marqueeB@test.com',
            'status' => 'active',
        ]);

        // 4. Branches
        $this->branchA1 = Branch::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Branch Lahore Central',
            'address' => 'Gulberg III',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '03001111112',
            'status' => 'active',
        ]);

        $this->branchB1 = Branch::create([
            'marquee_id' => $this->marqueeB->id,
            'name' => 'Branch Karachi South',
            'address' => 'DHA Phase 5',
            'city' => 'Karachi',
            'province' => 'Sindh',
            'phone' => '03002222223',
            'status' => 'active',
        ]);

        // 5. Users
        $this->superAdmin = User::create([
            'name' => 'Global Super Admin',
            'email' => 'super@financial-test.com',
            'password' => Hash::make('Password123!#'),
            'role_id' => $this->superAdminRole->id,
            'status' => 'active',
        ]);

        $this->ownerA = User::create([
            'name' => 'Owner Marquee A',
            'email' => 'ownerA@financial-test.com',
            'password' => Hash::make('Password123!#'),
            'role_id' => $this->ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
            'status' => 'active',
        ]);
        $this->marqueeA->update(['owner_user_id' => $this->ownerA->id]);

        $this->accountantA = User::create([
            'name' => 'Accountant A',
            'email' => 'accountantA@financial-test.com',
            'password' => Hash::make('Password123!#'),
            'role_id' => $this->accountantRole->id,
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->accountantRole->permissions()->attach([
            $this->postPaymentsPermission->id,
            $this->viewPaymentsPermission->id,
            $this->manageAccountingPermission->id,
        ]);

        $this->storeKeeperRole = Role::create(['name' => 'store_keeper', 'label' => 'Store Keeper']);
        $this->storeKeeperRole->permissions()->attach([
            $this->viewInventoryPermission->id,
        ]);

        $this->storeKeeperA = User::create([
            'name' => 'Store Keeper A',
            'email' => 'storeA@financial-test.com',
            'password' => Hash::make('Password123!#'),
            'role_id' => $this->storeKeeperRole->id,
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);

        $this->unauthorizedUserA = User::create([
            'name' => 'Basic Staff A',
            'email' => 'basicA@financial-test.com',
            'password' => Hash::make('Password123!#'),
            'role_id' => $this->staffRole->id,
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);

        // 6. Chart of Accounts Setup
        $this->accountTypeCurrentAsset = AccountType::firstOrCreate(
            ['code' => 'CURRENT_ASSETS'],
            ['name' => 'Current Assets', 'nature' => 'Asset']
        );

        $this->accountA_Cash = Account::create([
            'marquee_id' => $this->marqueeA->id,
            'account_type_id' => $this->accountTypeCurrentAsset->id,
            'account_code' => '1001',
            'nature' => 'Asset',
            'name' => 'Main Cash Drawer A',
            'is_active' => true,
        ]);

        $this->accountB_Cash = Account::create([
            'marquee_id' => $this->marqueeB->id,
            'account_type_id' => $this->accountTypeCurrentAsset->id,
            'account_code' => '1001',
            'nature' => 'Asset',
            'name' => 'Main Cash Drawer B',
            'is_active' => true,
        ]);
    }

    /**
     * Helper to create a complete booking instance for testing.
     */
    protected function createBooking(Marquee $marquee, Branch $branch): Booking
    {
        $customer = Customer::create([
            'marquee_id' => $marquee->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'phone_number' => '0300' . rand(1000000, 9999999),
        ]);

        $hall = Hall::create([
            'marquee_id' => $marquee->id,
            'branch_id' => $branch->id,
            'hall_name' => 'Grand Hall',
            'hall_code' => 'GH-' . rand(10, 99),
            'capacity' => 500,
            'hall_type' => 'Marquee',
            'default_booking_price' => 50000.00,
            'status' => 'active',
        ]);

        $slot = Slot::create([
            'marquee_id' => $marquee->id,
            'slot_name' => 'Evening Dinner',
            'start_time' => '19:00:00',
            'end_time' => '23:00:00',
            'status' => 'active',
        ]);

        $eventType = EventType::create([
            'marquee_id' => $marquee->id,
            'event_type_name' => 'Wedding Reception',
            'event_type_code' => 'EV-' . rand(10, 99),
            'status' => 'active',
        ]);

        return Booking::create([
            'marquee_id' => $marquee->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'hall_id' => $hall->id,
            'slot_id' => $slot->id,
            'event_type_id' => $eventType->id,
            'booking_number' => 'BK-' . rand(1000, 9999),
            'booking_date' => now()->addDays(14)->format('Y-m-d'),
            'start_time' => now()->addDays(14)->setTime(18, 0, 0)->format('Y-m-d H:i:s'),
            'end_time' => now()->addDays(14)->setTime(23, 0, 0)->format('Y-m-d H:i:s'),
            'guest_count' => 200,
            'per_plate_price' => 1500.00,
            'total_amount' => 300000.00,
            'grand_total' => 300000.00,
            'advance_received' => 0.00,
            'booking_status' => 'Confirmed',
            'payment_status' => 'Unpaid',
        ]);
    }

    /**
     * 1. Test Financial Immutability: Posted BookingPayment cannot be deleted directly.
     */
    public function test_posted_booking_payment_cannot_be_deleted()
    {
        $booking = $this->createBooking($this->marqueeA, $this->branchA1);

        $postedPayment = BookingPayment::create([
            'booking_id' => $booking->id,
            'payment_number' => 'PAY-TEST-001',
            'amount' => 50000.00,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'payment_type' => 'advance',
            'status' => 'posted',
            'recorded_by' => $this->accountantA->id,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Posted payment transactions cannot be deleted');

        $postedPayment->delete();
    }

    /**
     * 2. Test Pending BookingPayment can be deleted if needed.
     */
    public function test_pending_booking_payment_can_be_deleted()
    {
        $booking = $this->createBooking($this->marqueeA, $this->branchA1);

        $pendingPayment = BookingPayment::create([
            'booking_id' => $booking->id,
            'payment_number' => 'PAY-TEST-002',
            'amount' => 25000.00,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'payment_type' => 'advance',
            'status' => 'pending_posting',
            'recorded_by' => $this->accountantA->id,
        ]);

        $deleted = $pendingPayment->delete();
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('booking_payments', ['id' => $pendingPayment->id]);
    }

    /**
     * 3. Test unauthorized user cannot mount PaymentsList.
     */
    public function test_unauthorized_user_cannot_access_payments_list()
    {
        Livewire::actingAs($this->unauthorizedUserA)
            ->test(PaymentsList::class)
            ->assertForbidden();
    }

    /**
     * 4. Test cross-tenant booking payment posting IDOR is blocked.
     */
    public function test_cross_tenant_booking_payment_posting_is_blocked()
    {
        $bookingB = $this->createBooking($this->marqueeB, $this->branchB1);

        $paymentB = BookingPayment::create([
            'booking_id' => $bookingB->id,
            'payment_number' => 'PAY-B-001',
            'amount' => 30000.00,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'payment_type' => 'advance',
            'status' => 'pending_posting',
            'recorded_by' => $this->superAdmin->id,
        ]);

        // Accountant of Marquee A attempting to open or post payment of Marquee B
        Livewire::actingAs($this->accountantA)
            ->test(PaymentsList::class)
            ->call('openPostModal', $paymentB->id)
            ->assertForbidden();
    }

    /**
     * 5. Test PaymentsList prevents mapping a foreign tenant account during posting.
     */
    public function test_payments_list_rejects_foreign_tenant_account_on_posting()
    {
        $bookingA = $this->createBooking($this->marqueeA, $this->branchA1);

        $paymentA = BookingPayment::create([
            'booking_id' => $bookingA->id,
            'payment_number' => 'PAY-A-001',
            'amount' => 15000.00,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'payment_type' => 'advance',
            'status' => 'pending_posting',
            'recorded_by' => $this->accountantA->id,
        ]);

        Livewire::actingAs($this->accountantA)
            ->test(PaymentsList::class)
            ->call('openPostModal', $paymentA->id)
            ->set('targetAccountId', $this->accountB_Cash->id) // Foreign tenant account
            ->set('postingDate', now()->format('Y-m-d'))
            ->call('confirmPostPayment', app(\App\Services\BookingFinancialService::class))
            ->assertHasErrors(['targetAccountId']);
    }

    /**
     * 6. Test Cash/Bank Manager enforces tenant isolation and creates ActivityLog.
     */
    public function test_cash_bank_manager_tenant_isolation_and_activity_logging()
    {
        // Create CashBankAccount for Marquee B
        $cbB = CashBankAccount::create([
            'marquee_id' => $this->marqueeB->id,
            'account_id' => $this->accountB_Cash->id,
            'type' => 'cash',
            'status' => 'active',
        ]);

        // User A cannot edit Marquee B's CashBankAccount
        try {
            Livewire::actingAs($this->accountantA)
                ->test(CashBankManager::class)
                ->call('edit', $cbB->id);
            $this->fail('Expected ModelNotFoundException');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertTrue(true);
        }

        // User A cannot delete Marquee B's CashBankAccount
        try {
            Livewire::actingAs($this->accountantA)
                ->test(CashBankManager::class)
                ->call('delete', $cbB->id);
            $this->fail('Expected ModelNotFoundException');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertTrue(true);
        }

        // User A creating valid CashBankAccount generates ActivityLog
        Livewire::actingAs($this->accountantA)
            ->test(CashBankManager::class)
            ->set('account_id', $this->accountA_Cash->id)
            ->set('type', 'cash')
            ->set('status', 'active')
            ->call('save');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->accountantA->id,
            'marquee_id' => $this->marqueeA->id,
            'action' => 'cash_bank_account_created',
        ]);
    }

    /**
     * 7. Test Journal Voucher cross-tenant IDOR and ActivityLog.
     */
    public function test_journal_voucher_cross_tenant_idor_and_activity_logging()
    {
        $jvB = JournalVoucher::create([
            'marquee_id' => $this->marqueeB->id,
            'voucher_no' => 'JV-B-001',
            'voucher_date' => now()->format('Y-m-d'),
            'status' => 'draft',
        ]);

        // Accountant A cannot edit Journal Voucher of Marquee B via controller
        $response = $this->actingAs($this->accountantA)->get(route('finance.journal-vouchers.edit', $jvB->id));
        $response->assertStatus(403);

        // Accountant A cannot mount Journal Voucher of Marquee B via Livewire
        try {
            Livewire::actingAs($this->accountantA)
                ->test(JournalVoucherForm::class, ['id' => $jvB->id]);
            $this->fail('Expected ModelNotFoundException');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * 8. Test Payment Voucher cross-tenant print and detail viewing are blocked.
     */
    public function test_payment_voucher_cross_tenant_access_blocked()
    {
        $pvB = PaymentVoucher::create([
            'marquee_id' => $this->marqueeB->id,
            'voucher_type' => 'CPV',
            'voucher_no' => 'CPV-B-0001',
            'voucher_date' => now()->format('Y-m-d'),
            'payee_type' => 'general',
            'payee_name' => 'Foreign Supplier',
            'cash_bank_account_id' => CashBankAccount::create([
                'marquee_id' => $this->marqueeB->id,
                'account_id' => $this->accountB_Cash->id,
                'type' => 'cash',
                'status' => 'active',
            ])->id,
            'debit_account_id' => $this->accountB_Cash->id,
            'amount' => 5000.00,
            'payment_method' => 'Cash',
            'status' => 'draft',
            'created_by' => $this->superAdmin->id,
        ]);

        // Accountant A cannot print or view voucher B
        $response = $this->actingAs($this->accountantA)->get(route('finance.payment-vouchers.print', $pvB->id));
        $response->assertStatus(403);

        // Livewire PaymentVoucherDetail aborts with 403
        Livewire::actingAs($this->accountantA)
            ->test(PaymentVoucherDetail::class, ['id' => $pvB->id])
            ->assertForbidden();
    }

    /**
     * 9. Test unauthorized user cannot make inventory adjustments, and authorized user generates ActivityLog.
     */
    public function test_inventory_adjustments_authorization_and_activity_logging()
    {
        $uom = InventoryUnit::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Kilogram',
            'short_code' => 'KG',
        ]);

        $cat = InventoryCategory::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Raw Materials',
        ]);

        $itemA = InventoryItem::create([
            'marquee_id' => $this->marqueeA->id,
            'category_id' => $cat->id,
            'unit_id' => $uom->id,
            'item_code' => 'RAW-001',
            'name' => 'Basmati Rice',
            'current_stock' => 100,
            'default_purchase_rate' => 200.00,
            'average_cost' => 200.00,
        ]);

        // StoreKeeper A does NOT have inventory.adjust permission
        Livewire::actingAs($this->storeKeeperA)
            ->test(StockTakeManager::class)
            ->call('openAdjustmentForm')
            ->assertForbidden();

        // Grant inventory.adjust to storekeeper role
        $this->storeKeeperRole->permissions()->attach($this->inventoryAdjustPermission->id);
        $this->storeKeeperA->refresh();

        Livewire::actingAs($this->storeKeeperA)
            ->test(StockTakeManager::class)
            ->call('openAdjustmentForm')
            ->set('adj_item_id', $itemA->id)
            ->set('adj_quantity', 10)
            ->set('adj_type', 'Opening')
            ->set('adj_unit_cost', 200.00)
            ->set('adj_date', now()->format('Y-m-d'))
            ->call('saveAdjustment')
            ->assertHasNoErrors();

        // Verify Ledger entry was created
        $this->assertDatabaseHas('inventory_stock_ledgers', [
            'item_id' => $itemA->id,
            'transaction_type' => 'Opening',
            'created_by' => $this->storeKeeperA->id,
        ]);

        // Verify ActivityLog was created
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->storeKeeperA->id,
            'marquee_id' => $this->marqueeA->id,
            'action' => 'stock_adjustment_posted',
        ]);
    }

    /**
     * 10. Test Menu Items and Categories cross-tenant scoping with hasAccessToMarquee().
     */
    public function test_menu_item_and_category_cross_tenant_scoping()
    {
        $this->accountantRole->permissions()->attach($this->viewMenusPermission->id);

        $catB = MenuCategory::create([
            'marquee_id' => $this->marqueeB->id,
            'category_name' => 'Desserts B',
            'category_code' => 'CAT-B01',
        ]);

        $itemB = MenuItem::create([
            'marquee_id' => $this->marqueeB->id,
            'category_id' => $catB->id,
            'item_name' => 'Gulab Jamun B',
            'item_code' => 'MI-' . rand(100, 999),
            'selling_price' => 150.00,
        ]);

        // Accountant of Marquee A accessing Marquee B's menu item or category returns 403 or 404 (due to tenant scoping)
        $respCat = $this->actingAs($this->accountantA)->get(route('menu-categories.show', $catB->id));
        $this->assertTrue(in_array($respCat->status(), [403, 404]));

        $respItem = $this->actingAs($this->accountantA)->get(route('menu-items.show', $itemB->id));
        $this->assertTrue(in_array($respItem->status(), [403, 404]));
    }

    /**
     * 11. Test Super Admin can access cross-tenant menu items and categories.
     */
    public function test_super_admin_can_access_cross_tenant_menu_items_and_categories()
    {
        $catB = MenuCategory::create([
            'marquee_id' => $this->marqueeB->id,
            'category_name' => 'Desserts B',
            'category_code' => 'CAT-B02',
        ]);

        $itemB = MenuItem::create([
            'marquee_id' => $this->marqueeB->id,
            'category_id' => $catB->id,
            'item_name' => 'Gulab Jamun B',
            'item_code' => 'MI-' . rand(100, 999),
            'selling_price' => 150.00,
        ]);

        $this->actingAs($this->superAdmin)->get(route('menu-categories.show', $catB->id))->assertStatus(200);
        $this->actingAs($this->superAdmin)->get(route('menu-items.show', $itemB->id))->assertStatus(200);
    }
}
