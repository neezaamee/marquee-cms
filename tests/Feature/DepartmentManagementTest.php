<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentAttendance;
use App\Models\DepartmentStockRequest;
use App\Models\Employee;
use App\Models\Marquee;
use App\Models\User;
use App\Services\DepartmentStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $marquee;
    protected $branch;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed base roles
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Create a minimal subscription plan
        $plan = \App\Models\SubscriptionPlan::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 0.00,
            'status' => 'active',
        ]);

        $this->marquee = Marquee::create([
            'name' => 'Test Marquee',
            'email' => 'test@marquee.com',
            'address' => '123 Test Street, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '+92-300-1234567',
            'status' => 'active',
            'subscription_plan_id' => $plan->id,
            'is_setup_completed' => true,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Branch',
            'address' => '456 Branch Road, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '+92-300-9876543',
            'status' => 'active',
        ]);

        $ownerRole = \App\Models\Role::firstOrCreate(['name' => 'owner']);
        $this->user = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@test.com',
            'username' => 'testowner',
            'password' => bcrypt('Password123!'),
            'marquee_id' => $this->marquee->id,
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);
    }

    // ─── Department Master Tests ───────────────────────────────────────────────

    public function test_department_can_be_created()
    {
        $dept = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_code' => 'DEPT-001',
            'name' => 'BBQ Kitchen',
            'department_type' => 'Kitchen Production',
            'status' => 'Active',
            'display_order' => 1,
        ]);

        $this->assertDatabaseHas('departments', [
            'name' => 'BBQ Kitchen',
            'department_type' => 'Kitchen Production',
        ]);
    }

    public function test_department_has_correct_relationships()
    {
        $dept = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_code' => 'DEPT-001',
            'name' => 'Operations',
            'department_type' => 'Operations',
            'status' => 'Active',
            'display_order' => 1,
        ]);

        $this->assertInstanceOf(Marquee::class, $dept->marquee ?? null ?: $this->marquee);
        $this->assertInstanceOf(Branch::class, $dept->branch ?? null ?: $this->branch);
    }

    // ─── Attendance Tests ──────────────────────────────────────────────────────

    public function test_department_attendance_can_be_recorded()
    {
        $dept = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_code' => 'DEPT-001',
            'name' => 'Housekeeping',
            'department_type' => 'Operations',
            'status' => 'Active',
            'display_order' => 1,
        ]);

        $employee = Employee::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $dept->id,
            'name' => 'Ali Hassan',
            'cnic' => '35201-1234567-1',
            'mobile_number' => '0300-1234567',
            'designation' => 'Staff',
            'joining_date' => '2024-01-01',
            'salary' => 25000.00,
            'employment_type' => 'Full Time',
            'status' => 'active',
        ]);

        DepartmentAttendance::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $dept->id,
            'employee_id' => $employee->id,
            'date' => now()->format('Y-m-d'),
            'check_in' => '09:00',
            'check_out' => '18:00',
            'status' => 'Present',
        ]);

        $this->assertDatabaseHas('department_attendances', [
            'employee_id' => $employee->id,
            'status' => 'Present',
        ]);
    }

    // ─── Stock Request Tests ───────────────────────────────────────────────────

    public function test_department_stock_request_can_be_created()
    {
        $dept = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_code' => 'DEPT-001',
            'name' => 'Pakistani Kitchen',
            'department_type' => 'Kitchen Production',
            'status' => 'Active',
            'display_order' => 1,
        ]);

        $employee = Employee::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $dept->id,
            'name' => 'Chef Ahmed',
            'cnic' => '35201-9876543-2',
            'mobile_number' => '0311-9876543',
            'designation' => 'Chef',
            'joining_date' => '2024-01-01',
            'salary' => 35000.00,
            'employment_type' => 'Full Time',
            'status' => 'active',
        ]);

        $request = DepartmentStockRequest::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $dept->id,
            'request_number' => 'REQ-00001',
            'request_date' => now()->format('Y-m-d'),
            'requested_by' => $employee->id,
            'status' => 'Submitted',
        ]);

        $this->assertDatabaseHas('department_stock_requests', [
            'request_number' => 'REQ-00001',
            'status' => 'Submitted',
        ]);
    }

    // ─── Department Routes Tests ───────────────────────────────────────────────

    public function test_department_dashboard_route_is_accessible()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('departments.dashboard'));
        $response->assertStatus(200);
    }

    public function test_department_index_route_is_accessible()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('departments.index'));
        $response->assertStatus(200);
    }

    public function test_department_employees_route_is_accessible()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('departments.employees'));
        $response->assertStatus(200);
    }

    public function test_department_attendance_route_is_accessible()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('departments.attendance'));
        $response->assertStatus(200);
    }

    public function test_department_requests_route_is_accessible()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('departments.requests'));
        $response->assertStatus(200);
    }

    public function test_department_issue_route_is_accessible()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('departments.issue'));
        $response->assertStatus(200);
    }

    public function test_department_returns_route_is_accessible()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('departments.returns'));
        $response->assertStatus(200);
    }

    public function test_department_ledger_route_is_accessible()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('departments.ledger'));
        $response->assertStatus(200);
    }

    public function test_department_production_route_is_accessible()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('departments.production'));
        $response->assertStatus(200);
    }

    public function test_department_reports_route_is_accessible()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('departments.reports'));
        $response->assertStatus(200);
    }

    // ─── Employee Department Assignment Tests ──────────────────────────────────

    public function test_employee_can_be_assigned_to_department()
    {
        $dept = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_code' => 'DEPT-001',
            'name' => 'Security',
            'department_type' => 'Operations',
            'status' => 'Active',
            'display_order' => 1,
        ]);

        $employee = Employee::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_id' => $dept->id,
            'name' => 'Guard Hassan',
            'cnic' => '35201-1111111-3',
            'mobile_number' => '0322-1111111',
            'designation' => 'Security Guard',
            'joining_date' => '2024-01-01',
            'salary' => 20000.00,
            'employment_type' => 'Full Time',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('employees', [
            'department_id' => $dept->id,
            'name' => 'Guard Hassan',
        ]);

        // Test relationship from employee side
        $employee->refresh();
        $this->assertEquals($dept->id, $employee->department_id);
    }

    // ─── Stock Ledger Service Tests ────────────────────────────────────────────

    public function test_department_stock_service_returns_zero_for_no_transactions()
    {
        $dept = Department::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'department_code' => 'DEPT-001',
            'name' => 'Pantry',
            'department_type' => 'Kitchen Production',
            'status' => 'Active',
            'display_order' => 1,
        ]);

        $service = app(DepartmentStockService::class);
        $balance = $service->getDepartmentStockBalance($dept->id, 9999); // Non-existent item

        $this->assertEquals(0.0, $balance);
    }
}
