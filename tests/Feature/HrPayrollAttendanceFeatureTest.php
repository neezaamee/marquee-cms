<?php

namespace Tests\Feature;

use App\Livewire\MonthlyAttendanceSheet;
use App\Livewire\Payroll\PayrollBatchDetail;
use App\Livewire\Payroll\PayrollBatchManager;
use App\Livewire\Payroll\SalaryAdvanceManager;
use App\Livewire\StaffAttendance;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\FinancialYear;
use App\Models\Marquee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SalaryAdvance;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrPayrollAttendanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $marquee;
    protected $branch;
    protected $user;
    protected $cashAccount;
    protected $cashBankAccount;
    protected $salaryAccount;
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $pManageStaff = Permission::create(['name' => 'manage_staff', 'label' => 'Manage Staff']);
        $pAccounting = Permission::create(['name' => 'manage_accounting', 'label' => 'Manage Accounting']);
        $ownerRole->permissions()->attach([$pManageStaff->id, $pAccounting->id]);

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price' => 15000,
            'billing_interval' => 'month',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'The Grand Sheraton Marquee',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Gulberg III, Lahore',
            'phone' => '03001234567',
            'email' => 'contact@sheraton.pk',
            'subscription_plan_id' => $plan->id,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Royal Hall',
            'address' => 'Gulberg III, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '04235778899',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'name' => 'Haji Sheraz',
            'email' => 'sheraz@sheraton.pk',
            'password' => bcrypt('password123'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);

        $accTypeAsset = AccountType::create([
            'name' => 'Current Assets',
            'code' => '100',
            'nature' => 'Asset',
        ]);

        $accTypeExpense = AccountType::create([
            'name' => 'Operating Expenses',
            'code' => '500',
            'nature' => 'Expense',
        ]);

        $this->cashAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Cash in Hand',
            'account_code' => '1001',
            'account_type_id' => $accTypeAsset->id,
            'nature' => 'Asset',
            'is_active' => true,
        ]);

        $this->salaryAccount = Account::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Permanent Staff Salaries',
            'account_code' => '5302',
            'account_type_id' => $accTypeExpense->id,
            'nature' => 'Expense',
            'is_active' => true,
        ]);

        $this->cashBankAccount = CashBankAccount::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'account_id' => $this->cashAccount->id,
            'account_name' => 'Main Cash Drawer',
            'type' => 'cash',
            'status' => 'active',
            'current_balance' => 500000,
        ]);

        FinancialYear::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'FY 2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'status' => 'open',
            'is_active' => true,
        ]);

        Currency::create([
            'marquee_id' => $this->marquee->id,
            'code' => 'PKR',
            'name' => 'Pakistani Rupee',
            'symbol' => 'Rs',
            'exchange_rate' => 1.0,
            'is_base' => true,
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => 'EMP-001',
            'name' => 'Muhammad Aslam',
            'cnic' => '35202-1234567-1',
            'mobile_number' => '0300-1234567',
            'designation' => 'Executive Head Chef',
            'employment_type' => 'Permanent',
            'joining_date' => '2025-01-01',
            'salary' => 60000.00,
            'status' => 'Active',
        ]);
    }

    public function test_daily_attendance_marking_and_bulk_actions(): void
    {
        $this->actingAs($this->user);

        Livewire::test(StaffAttendance::class)
            ->set('date', '2026-11-01')
            ->call('markAllPresent')
            ->assertSet('attendanceData.' . $this->employee->id . '.status', 'Present')
            ->call('saveAttendance');

        $att = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', '2026-11-01')
            ->first();

        $this->assertNotNull($att);
        $this->assertEquals('Present', $att->status);

        // Update attendance with overtime and notes
        Livewire::test(StaffAttendance::class)
            ->set('date', '2026-11-01')
            ->set('attendanceData.' . $this->employee->id . '.overtime_hours', 3.5)
            ->set('attendanceData.' . $this->employee->id . '.notes', 'Late wedding shift coverage')
            ->call('saveAttendance');

        $att = $att->fresh();
        $this->assertEquals(3.5, (float) $att->overtime_hours);
        $this->assertEquals('Late wedding shift coverage', $att->notes);
    }

    public function test_monthly_attendance_sheet_matrix(): void
    {
        $this->actingAs($this->user);

        // Seed 3 attendances for November 2026
        Attendance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-11-01',
            'status' => 'Present',
            'overtime_hours' => 2.0,
        ]);

        Attendance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-11-02',
            'status' => 'Absent',
            'overtime_hours' => 0.0,
        ]);

        Attendance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-11-03',
            'status' => 'Half Day',
            'overtime_hours' => 0.0,
        ]);

        Livewire::test(MonthlyAttendanceSheet::class)
            ->set('selectedMonth', '2026-11')
            ->assertSee('Muhammad Aslam')
            ->assertSee('EMP-001');
    }

    public function test_salary_advance_disbursement_and_balance(): void
    {
        $this->actingAs($this->user);

        // Disburse mid-month advance Rs. 10,000 for deduction in 2026-11
        Livewire::test(SalaryAdvanceManager::class)
            ->set('employee_id', $this->employee->id)
            ->set('advance_date', '2026-11-10')
            ->set('amount', 10000.00)
            ->set('deduction_month', '2026-11')
            ->set('payment_method', 'cash')
            ->set('cash_bank_account_id', $this->cashBankAccount->id)
            ->set('reason', 'Family medical urgent expense')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salary_advances', [
            'marquee_id' => $this->marquee->id,
            'employee_id' => $this->employee->id,
            'amount' => 10000.00,
            'deduction_month' => '2026-11',
            'status' => 'pending',
        ]);

        $this->assertEquals(10000.00, $this->employee->fresh()->pending_advance_balance);
    }

    public function test_payroll_batch_generation_with_attendance_and_advance_deductions(): void
    {
        $this->actingAs($this->user);

        // 1. Employee salary: 60,000. In 30 days month:
        // Daily Rate = 2,000. Hourly Rate = 250.
        // Seed 2 Absents -> Absence Deduction = 4,000.
        // Seed 8 hours Overtime -> Overtime Pay = 2,000.
        Attendance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-11-02',
            'status' => 'Absent',
        ]);
        Attendance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-11-03',
            'status' => 'Absent',
        ]);
        Attendance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-11-05',
            'status' => 'Present',
            'overtime_hours' => 8.0,
        ]);

        // 2. Mid-month advance 10,000
        SalaryAdvance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'advance_date' => '2026-11-10',
            'amount' => 10000.00,
            'deduction_month' => '2026-11',
            'payment_method' => 'cash',
            'status' => 'pending',
            'cash_bank_account_id' => $this->cashBankAccount->id,
        ]);

        // 3. Generate Monthly Payroll
        Livewire::test(PayrollBatchManager::class)
            ->set('genMonth', '2026-11')
            ->set('genWorkingDays', 30)
            ->call('generatePayroll');

        $payroll = Payroll::where('marquee_id', $this->marquee->id)
            ->where('payroll_month', '2026-11')
            ->first();

        $this->assertNotNull($payroll);
        $this->assertEquals('draft', $payroll->status);

        $item = PayrollItem::where('payroll_id', $payroll->id)
            ->where('employee_id', $this->employee->id)
            ->first();

        $this->assertNotNull($item);
        $this->assertEquals(60000.00, (float) $item->basic_salary);
        $this->assertEquals(2, $item->absent_days);
        $this->assertEquals(4000.00, (float) $item->absence_deduction);
        $this->assertEquals(8.0, (float) $item->overtime_hours);
        $this->assertEquals(2000.00, (float) $item->overtime_amount);
        $this->assertEquals(10000.00, (float) $item->advance_deduction);
        // Gross: 60000 + 2000 = 62000
        $this->assertEquals(62000.00, (float) $item->gross_salary);
        // Deductions: 4000 + 10000 = 14000
        $this->assertEquals(14000.00, (float) $item->total_deductions);
        // Net: 62000 - 14000 = 48000
        $this->assertEquals(48000.00, (float) $item->net_salary);
    }

    public function test_payroll_approval_disbursement_and_accounting_settlement(): void
    {
        $this->actingAs($this->user);

        // Advance
        $adv = SalaryAdvance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'advance_date' => '2026-11-10',
            'amount' => 5000.00,
            'deduction_month' => '2026-11',
            'payment_method' => 'cash',
            'status' => 'pending',
            'cash_bank_account_id' => $this->cashBankAccount->id,
        ]);

        $payroll = Payroll::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'payroll_month' => '2026-11',
            'batch_number' => 'PAY-202611-001',
            'total_employees' => 1,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $item = PayrollItem::create([
            'payroll_id' => $payroll->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'salary_slip_number' => 'SLIP-202611-0001',
            'basic_salary' => 60000.00,
            'total_working_days' => 30,
            'present_days' => 30,
            'absent_days' => 0,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'total_allowances' => 0,
            'gross_salary' => 60000.00,
            'advance_deduction' => 5000.00,
            'total_deductions' => 5000.00,
            'net_salary' => 55000.00,
            'payment_status' => 'unpaid',
        ]);
        $payroll->recalculateTotals();

        // 1. Approve batch
        Livewire::test(PayrollBatchDetail::class, ['id' => $payroll->id])
            ->call('approveBatch');

        $this->assertEquals('approved', $payroll->fresh()->status);

        // 2. Disburse batch
        $component = Livewire::test(PayrollBatchDetail::class, ['id' => $payroll->id])
            ->set('disburseCashBankAccountId', $this->cashBankAccount->id)
            ->set('disbursePaymentMethod', 'cash')
            ->set('disburseDate', '2026-11-30')
            ->set('disburseReference', 'SAL-NOV-CASH')
            ->call('executeDisburseBatch')
            ->assertHasNoErrors();

        if (session('error')) {
            $this->fail("Disburse error: " . session('error'));
        }

        $payroll = $payroll->fresh();
        $this->assertEquals('paid', $payroll->status);
        $this->assertEquals(55000.00, (float) $payroll->total_paid);
        $this->assertNotNull($payroll->payment_voucher_id);

        // Check Payment Voucher
        $this->assertDatabaseHas('payment_vouchers', [
            'id' => $payroll->payment_voucher_id,
            'voucher_type' => 'CPV',
            'amount' => 55000.00,
            'debit_account_id' => $this->salaryAccount->id,
        ]);

        // Check Item status
        $this->assertEquals('paid', $item->fresh()->payment_status);

        // Check Advance settled
        $this->assertEquals('deducted', $adv->fresh()->status);
        $this->assertEquals($payroll->id, $adv->fresh()->payroll_id);
    }

    public function test_printable_payslip_and_employee_360_profile_screens(): void
    {
        $this->actingAs($this->user);

        $payroll = Payroll::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'payroll_month' => '2026-11',
            'batch_number' => 'PAY-202611-002',
            'total_employees' => 1,
            'status' => 'paid',
            'created_by' => $this->user->id,
        ]);

        $item = PayrollItem::create([
            'payroll_id' => $payroll->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'salary_slip_number' => 'SLIP-202611-0002',
            'basic_salary' => 60000.00,
            'total_working_days' => 30,
            'present_days' => 28,
            'absent_days' => 2,
            'absence_deduction' => 4000.00,
            'overtime_hours' => 4.0,
            'overtime_rate' => 250.00,
            'overtime_amount' => 1000.00,
            'allowances' => ['bonus' => 5000.00],
            'total_allowances' => 5000.00,
            'gross_salary' => 66000.00,
            'total_deductions' => 4000.00,
            'net_salary' => 62000.00,
            'payment_status' => 'paid',
        ]);

        // Test Printable Payslip
        $responseSlip = $this->get(route('payroll.slip', $item->id));
        $responseSlip->assertStatus(200);
        $responseSlip->assertSee('SLIP-202611-0002');
        $responseSlip->assertSee('Muhammad Aslam');
        $responseSlip->assertSee('62,000.00');

        // Test 360 Employee Profile
        $responseProfile = $this->get(route('staff.show', $this->employee->id));
        $responseProfile->assertStatus(200);
        $responseProfile->assertSee('Muhammad Aslam');
        $responseProfile->assertSee('Attendance History');
        $responseProfile->assertSee('Salary Advances');
        $responseProfile->assertSee('Payslips &amp; Payroll', false);
    }

    public function test_individual_item_adjustment_and_single_employee_payout(): void
    {
        $this->actingAs($this->user);

        $payroll = Payroll::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'payroll_month' => '2026-11',
            'batch_number' => 'PAY-202611-003',
            'total_employees' => 1,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $item = PayrollItem::create([
            'payroll_id' => $payroll->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'employee_id' => $this->employee->id,
            'salary_slip_number' => 'SLIP-202611-0003',
            'basic_salary' => 60000.00,
            'total_working_days' => 30,
            'present_days' => 30,
            'absent_days' => 0,
            'gross_salary' => 60000.00,
            'net_salary' => 60000.00,
            'payment_status' => 'unpaid',
        ]);
        $payroll->recalculateTotals();

        // 1. Adjust Item via Edit Modal: add bonus 5,000, fine 2,000
        Livewire::test(PayrollBatchDetail::class, ['id' => $payroll->id])
            ->call('openEditModal', $item->id)
            ->set('editBonus', 5000.00)
            ->set('editFineDeduction', 2000.00)
            ->call('saveItem')
            ->assertHasNoErrors();

        $item = $item->fresh();
        // Gross: 60000 + 5000 = 65000
        $this->assertEquals(65000.00, (float) $item->gross_salary);
        // Deductions: 2000
        $this->assertEquals(2000.00, (float) $item->total_deductions);
        // Net: 63000
        $this->assertEquals(63000.00, (float) $item->net_salary);

        // 2. Disburse individual payout for this employee
        Livewire::test(PayrollBatchDetail::class, ['id' => $payroll->id])
            ->call('openSinglePayModal', $item->id)
            ->set('singleCashBankAccountId', $this->cashBankAccount->id)
            ->set('singlePaymentMethod', 'cash')
            ->set('singlePaymentDate', '2026-11-28')
            ->set('singleReference', 'IND-PAY-01')
            ->call('executeSinglePay')
            ->assertHasNoErrors();

        $this->assertEquals('paid', $item->fresh()->payment_status);
        $this->assertEquals(63000.00, (float) $item->fresh()->paid_amount);

        // Batch should now be fully paid
        $payroll = $payroll->fresh();
        $this->assertEquals('paid', $payroll->status);
        $this->assertEquals(63000.00, (float) $payroll->total_paid);
    }
}
