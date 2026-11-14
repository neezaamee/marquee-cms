<?php

namespace Tests\Feature;

use App\Livewire\DepartmentReports;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentAttendance;
use App\Models\DepartmentProduction;
use App\Models\DepartmentStockLedger;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Recipe;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class DepartmentReportsExportAndPrintTest extends TestCase
{
    use RefreshDatabase;

    protected $marquee;
    protected $branch;
    protected $user;
    protected $department;
    protected $item;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $perm = Permission::create(['name' => 'department.view', 'label' => 'View Department']);
        $role->permissions()->attach($perm);

        $plan = SubscriptionPlan::create([
            'name' => 'Standard',
            'slug' => 'standard',
            'price' => 5000,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Royal Palm Banquet',
            'email' => 'info@royalpalm.pk',
            'phone' => '0300-1122334',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Canal Road, Lahore',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Crystal Hall',
            'address' => 'Canal Road, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '042-1234567',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'name' => 'Mian Waqas',
            'email' => 'waqas@royalpalm.pk',
            'password' => bcrypt('secret123'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->marquee->owners()->attach($this->user->id);

        $this->department = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_code' => 'DEPT-KIT',
            'name' => 'Main Kitchen',
            'department_type' => 'Kitchen',
            'status' => 'Active',
        ]);

        $category = \App\Models\InventoryCategory::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Food Supplies',
            'status' => 'active',
        ]);

        $unit = \App\Models\InventoryUnit::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Kilogram',
            'short_code' => 'KG',
            'status' => 'active',
        ]);

        $this->item = InventoryItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'item_code' => 'ITM-001',
            'name' => 'Basmati Rice',
            'status' => 'active',
        ]);
    }

    public function test_consumption_csv_export(): void
    {
        $this->actingAs($this->user);

        DepartmentStockLedger::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'item_id' => $this->item->id,
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'Issue',
            'qty_in' => 50,
            'qty_out' => 0,
            'unit_price' => 350.00,
            'total_cost' => 17500.00,
            'running_balance' => 50,
        ]);

        $fileName = 'department-consumption-report-' . date('Y-m-d') . '.csv';

        Livewire::test(DepartmentReports::class)
            ->set('reportType', 'consumption')
            ->call('exportCsv')
            ->assertFileDownloaded($fileName);
    }

    public function test_attendance_csv_export(): void
    {
        $this->actingAs($this->user);

        $emp = Employee::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'employee_id' => 'EMP-101',
            'name' => 'Ali Raza',
            'cnic' => '35201-1111111-1',
            'mobile_number' => '0300-1111111',
            'designation' => 'Cook',
            'joining_date' => '2025-01-01',
            'salary' => 45000,
            'employment_type' => 'Permanent',
            'status' => 'Active',
        ]);

        DepartmentAttendance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'employee_id' => $emp->id,
            'date' => now()->toDateString(),
            'status' => 'Present',
            'check_in' => '08:30:00',
            'check_out' => '17:00:00',
        ]);

        $fileName = 'department-attendance-report-' . date('Y-m-d') . '.csv';

        Livewire::test(DepartmentReports::class)
            ->set('reportType', 'attendance')
            ->call('exportCsv')
            ->assertFileDownloaded($fileName);
    }

    public function test_production_and_ledger_summary_csv_export(): void
    {
        $this->actingAs($this->user);

        $mCat = \App\Models\MenuCategory::create([
            'marquee_id' => $this->marquee->id,
            'category_code' => 'MCAT-01',
            'category_name' => 'Main Courses',
        ]);

        $mItem = \App\Models\MenuItem::create([
            'marquee_id' => $this->marquee->id,
            'category_id' => $mCat->id,
            'item_code' => 'MITM-01',
            'item_name' => 'Biryani Special',
            'selling_price' => 500.00,
        ]);

        $recipe = Recipe::create([
            'marquee_id' => $this->marquee->id,
            'menu_item_id' => $mItem->id,
            'name' => 'Chicken Biryani Special',
            'yield_quantity' => 100,
            'yield_unit' => 'Degh',
            'status' => 'active',
        ]);

        $cook = Employee::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'employee_id' => 'EMP-201',
            'name' => 'Chef Zubair',
            'cnic' => '35201-2222222-1',
            'mobile_number' => '0300-2222222',
            'designation' => 'Chef',
            'joining_date' => '2025-01-01',
            'salary' => 50000,
            'employment_type' => 'Permanent',
            'status' => 'Active',
        ]);

        DepartmentProduction::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'recipe_id' => $recipe->id,
            'batch_number' => 'BATCH-202611-001',
            'production_date' => now()->toDateString(),
            'produced_qty' => 5,
            'legacy_finished_good_wastage_qty' => 0.5,
            'prepared_by' => $cook->id,
        ]);

        // Production CSV
        $prodFileName = 'department-production-report-' . date('Y-m-d') . '.csv';
        Livewire::test(DepartmentReports::class)
            ->set('reportType', 'production')
            ->call('exportCsv')
            ->assertFileDownloaded($prodFileName);

        // Ledger Summary CSV
        $ledgerFileName = 'department-ledger_summary-report-' . date('Y-m-d') . '.csv';
        Livewire::test(DepartmentReports::class)
            ->set('reportType', 'ledger_summary')
            ->call('exportCsv')
            ->assertFileDownloaded($ledgerFileName);
    }

    public function test_print_view_letterhead_and_page_rendering(): void
    {
        $this->actingAs($this->user);

        // Seed a stock ledger transaction within current period
        DepartmentStockLedger::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'item_id' => $this->item->id,
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'Issue',
            'qty_in' => 10,
            'qty_out' => 0,
            'running_balance' => 10,
            'unit_price' => 500,
            'total_cost' => 5000,
        ]);

        $component = Livewire::test(DepartmentReports::class)
            ->set('reportType', 'consumption')
            ->set('perPage', 'all')
            ->assertSee('Royal Palm Banquet')
            ->assertSee('Stock Movement Audit Report')
            ->assertSee('Print View')
            ->assertSee('Export CSV')
            ->assertSee('print-letterhead', false)
            ->assertSee('print-summary-box', false)
            ->assertSee('FILTERED TOTAL:')
            ->assertSee('5,000.00')
            ->assertSee('10.00');

        // Test Ledger Summary report print view and totals
        Livewire::test(DepartmentReports::class)
            ->set('reportType', 'ledger_summary')
            ->assertSee('Department Stock Balance Summary')
            ->assertSee('print-summary-box', false)
            ->assertSee('FILTERED TOTAL:')
            ->assertSee('5,000.00');
    }
}
