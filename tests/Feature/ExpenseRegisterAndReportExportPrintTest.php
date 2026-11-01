<?php

namespace Tests\Feature;

use App\Livewire\Finance\ExpenseList;
use App\Livewire\Finance\ExpenseReportManager;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialYear;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\ExpenseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseRegisterAndReportExportPrintTest extends TestCase
{
    use RefreshDatabase;

    protected $ownerRole;
    protected $marquee;
    protected $branch;
    protected $ownerUser;
    protected $category;
    protected $supplier;
    protected $expense1;
    protected $expense2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $pView = Permission::create(['name' => 'view_expenses', 'label' => 'View Expenses']);
        $pReports = Permission::create(['name' => 'view_reports', 'label' => 'View Reports']);
        $this->ownerRole->permissions()->attach([$pView->id, $pReports->id]);

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price' => 15000,
            'billing_interval' => 'month',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Grand Palace Marquee',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Gulberg, Lahore',
            'phone' => '03001234567',
            'email' => 'contact@grandpalace.pk',
            'subscription_plan_id' => $plan->id,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Executive Hall',
            'address' => 'Gulberg, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '04235778899',
            'status' => 'active',
        ]);

        $this->ownerUser = User::create([
            'name' => 'Haji Iqbal',
            'email' => 'iqbal@grandpalace.pk',
            'password' => bcrypt('secret123'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $this->ownerRole->id,
        ]);

        FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'active',
            'is_default' => true,
        ]);

        $accType = AccountType::create(['name' => 'Operating Expenses', 'code' => 'OPERATING_EXPENSES', 'nature' => 'Expense']);
        $expAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'account_code' => '5001',
            'name' => 'Decorations & Stage Lightings',
            'account_type_id' => $accType->id,
            'nature' => 'Expense',
            'is_active' => true,
        ]);

        $this->category = ExpenseCategory::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Floral Decor & Lighting',
            'category_code' => 'FLORAL_01',
            'default_account_id' => $expAccount->id,
            'is_active' => true,
        ]);

        $currency = Currency::create([
            'marquee_id' => $this->marquee->id,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs',
            'exchange_rate' => 1.0,
            'is_default' => true,
        ]);

        $this->supplier = Supplier::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Floral Art Lahore',
            'supplier_code' => 'SUP-FLORAL-01',
            'mobile_number' => '03219988776',
            'status' => 'active',
        ]);

        $this->expense1 = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => '2026-10-15',
            'department' => 'Decoration',
            'expense_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'description' => 'Fresh imported roses for main wedding aisle',
            'amount' => 50000.00,
            'tax_amount' => 2500.00,
            'discount_amount' => 0.00,
            'total_amount' => 52500.00,
            'total_amount_base' => 52500.00,
            'payment_method' => 'Cash',
            'payment_status' => 'Paid',
            'status' => Expense::STATUS_APPROVED,
        ]);

        $this->expense2 = Expense::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'expense_number' => 'EXP-2026-0002',
            'expense_date' => '2026-10-20',
            'department' => 'Kitchen',
            'expense_category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'description' => 'Stage lighting truss rental',
            'amount' => 30000.00,
            'tax_amount' => 1500.00,
            'discount_amount' => 500.00,
            'total_amount' => 31000.00,
            'total_amount_base' => 31000.00,
            'payment_method' => 'Accounts Payable',
            'payment_status' => 'Unpaid',
            'status' => Expense::STATUS_POSTED,
        ]);
    }

    public function test_expense_register_pagination_and_per_page()
    {
        $this->actingAs($this->ownerUser);

        Livewire::test(ExpenseList::class)
            ->assertSet('perPage', 15)
            ->set('perPage', 10)
            ->assertSee('EXP-2026-0001')
            ->assertSee('EXP-2026-0002')
            ->assertSee('Showing 1 to 2 of 2 expenses');
    }

    public function test_expense_register_export_csv()
    {
        $this->actingAs($this->ownerUser);

        $repository = new ExpenseRepository();
        $component = Livewire::test(ExpenseList::class);

        $response = $component->instance()->exportCSV($repository);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'Expense_Register_'));

        ob_start();
        $response->sendContent();
        $csvContent = ob_get_clean();

        $this->assertStringContainsString('Voucher No', $csvContent);
        $this->assertStringContainsString('Floral Art Lahore', $csvContent);
        $this->assertStringContainsString('EXP-2026-0001', $csvContent);
        $this->assertStringContainsString('52500.00', $csvContent);
        $this->assertStringContainsString('EXP-2026-0002', $csvContent);
        $this->assertStringContainsString('31000.00', $csvContent);
    }

    public function test_expense_register_print_route()
    {
        $this->actingAs($this->ownerUser);

        $response = $this->get(route('expenses.print'));

        $response->assertStatus(200);
        $response->assertSee('Grand Palace Marquee');
        $response->assertSee('Expense Register Log');
        $response->assertSee('EXP-2026-0001');
        $response->assertSee('EXP-2026-0002');
        $response->assertSee('83,500.00 PKR'); // 52500 + 31000
        $response->assertSee('Print Register');
        $response->assertSee('Download PDF');
    }

    public function test_expense_register_pdf_route()
    {
        $this->actingAs($this->ownerUser);

        $response = $this->get(route('expenses.pdf'));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_expense_report_manager_includes_print_pdf_and_export()
    {
        $this->actingAs($this->ownerUser);

        Livewire::test(ExpenseReportManager::class)
            ->assertSee('Print / PDF')
            ->assertSee('Export CSV')
            ->assertSee('Category-Wise Summary')
            ->assertSee('Grand Palace Marquee');
    }
}
