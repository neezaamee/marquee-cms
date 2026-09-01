<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Marquee;
use App\Models\Branch;
use App\Models\Hall;
use App\Models\FinancialYear;
use App\Models\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\SetupWizard;
use Tests\TestCase;

class InitialSetupWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed default roles and plans which are necessary for the seeder/wizard logic
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_access_setup_wizard()
    {
        $response = $this->get('/setup');
        $response->assertRedirect('/login');
    }

    public function test_new_user_without_setup_is_redirected_to_dashboard_from_operational_routes()
    {
        // Create user with null marquee_id (new registration)
        $user = User::factory()->create([
            'marquee_id' => null,
            'branch_id' => null,
            'role_id' => null,
        ]);

        $this->actingAs($user);

        // Attempting to access bookings should redirect to dashboard with warning
        $response = $this->get('/bookings');
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('warning');

        // Dashboard is accessible
        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Initial Business Configuration Required');
    }

    public function test_super_admin_bypasses_setup_wizard()
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $user = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'marquee_id' => null,
        ]);

        $this->actingAs($user);

        // Super admin should not be redirected or blocked
        $response = $this->get('/bookings');
        $response->assertStatus(200);
    }

    public function test_user_can_complete_setup_wizard_via_livewire()
    {
        $user = User::factory()->create([
            'marquee_id' => null,
            'branch_id' => null,
            'role_id' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(SetupWizard::class)
            // Step 1: Marquee details
            ->set('marquee_name', 'Grand Palace Marquee')
            ->set('business_type', 'Single Marquee')
            ->set('phone', '0300-9999999')
            ->set('email', 'grand@palace.com')
            ->set('address', '12 Main Boulevard')
            ->set('province', 'Punjab')
            ->set('city', 'Lahore')
            ->set('country', 'Pakistan')
            ->set('timezone', 'Asia/Karachi')
            ->set('currency', 'PKR')
            ->set('tax_authority', 'PRA')
            ->call('nextStep')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 2)

            // Step 2: Branch details
            ->set('branch_name', 'Gulberg Branch')
            ->set('branch_phone', '0342-3999999')
            ->set('branch_address', '12-A Ghalib Market')
            ->set('branch_province', 'Punjab')
            ->set('branch_city', 'Lahore')
            ->call('nextStep')
            ->assertHasNoErrors()
            ->assertSet('currentStep', 3)

            // Step 3: Branch Config
            ->set('tax_rate', 16.00)
            ->set('default_payment_method', 'Cash')
            ->call('nextStep')
            ->assertSet('currentStep', 4)

            // Step 4: Halls Setup
            ->set('new_hall_name', 'Executive Hall')
            ->set('new_hall_code', 'EXEC-HL')
            ->set('new_capacity', 400)
            ->set('new_hall_type', 'Banquet')
            ->set('new_default_booking_price', 150000)
            ->call('addHall')
            ->call('nextStep')
            ->assertSet('currentStep', 5)

            // Step 5: Departments
            ->call('nextStep')
            ->assertSet('currentStep', 6)

            // Step 6: Booking Masters
            ->call('nextStep')
            ->assertSet('currentStep', 7)

            // Step 7: Menu & Packages
            ->call('nextStep')
            ->assertSet('currentStep', 8)

            // Step 8: Inventory
            ->call('nextStep')
            ->assertSet('currentStep', 9)

            // Step 9: Finance Config & Launch
            ->set('fy_name', 'FY 2026')
            ->set('fy_start_date', '2026-01-01')
            ->set('fy_end_date', '2026-12-31')
            ->call('finishSetup')
            ->assertHasNoErrors()
            ->assertRedirect('/dashboard');

        // Assert database state updates
        $this->assertDatabaseHas('marquees', [
            'name' => 'Grand Palace Marquee',
            'is_setup_completed' => true,
        ]);

        $this->assertDatabaseHas('branches', [
            'name' => 'Gulberg Branch',
        ]);

        $this->assertDatabaseHas('halls', [
            'hall_name' => 'Executive Hall',
            'hall_code' => 'EXEC-HL',
        ]);

        $this->assertDatabaseHas('financial_years', [
            'name' => 'FY 2026',
        ]);

        // Assert Catering Menu & Package Seeding
        $this->assertDatabaseHas('menu_categories', [
            'category_name' => 'Rice Dishes',
            'category_code' => 'RICE',
        ]);

        $this->assertDatabaseHas('menu_items', [
            'item_name' => 'Chicken Special Biryani',
            'item_code' => 'CH-BIR',
        ]);

        $this->assertDatabaseHas('packages', [
            'package_name' => 'Standard Chicken Package',
            'package_code' => 'STD-CH',
            'per_plate_price' => 990.00,
        ]);

        // Assert Inventory Seeding
        $this->assertDatabaseHas('inventory_settings', [
            'marquee_id' => $user->fresh()->marquee_id,
        ]);

        $this->assertDatabaseHas('inventory_units', [
            'short_code' => 'Pcs',
        ]);

        $this->assertDatabaseHas('inventory_categories', [
            'name' => 'Food Items',
        ]);

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Al-Makkah Foods & Grains',
        ]);

        // Assert Expenses Seeding
        $this->assertDatabaseHas('currencies', [
            'code' => 'PKR',
            'is_base' => true,
        ]);

        $this->assertDatabaseHas('expense_types', [
            'code' => 'utility_bills',
        ]);

        $this->assertDatabaseHas('expense_categories', [
            'category_code' => 'SAL',
        ]);

        $this->assertDatabaseHas('petty_cash_accounts', [
            'account_name' => 'Main Reception Cash Drawer',
            'custodian_id' => $user->id,
        ]);

        $this->assertDatabaseHas('expense_approval_rules', [
            'marquee_id' => $user->fresh()->marquee_id,
        ]);

        // Assert user belongs to marquee, branch, and role
        $updatedUser = $user->fresh();
        $this->assertNotNull($updatedUser->marquee_id);
        $this->assertNotNull($updatedUser->branch_id);
        
        $ownerRole = Role::where('name', 'owner')->first();
        $this->assertEquals($ownerRole->id, $updatedUser->role_id);
    }
}
