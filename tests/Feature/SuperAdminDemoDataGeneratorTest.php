<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\DemoDataGenerator;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccountingModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminDemoDataGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;
    protected Marquee $marquee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AccountingModuleSeeder::class);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $managerRole = Role::where('name', 'branch_manager')->first();

        $this->marquee = Marquee::factory()->create([
            'name' => 'Royal Test Palace',
            'city' => 'Lahore',
        ]);

        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'marquee_id' => null,
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => $managerRole->id,
            'marquee_id' => $this->marquee->id,
        ]);
    }

    /** @test */
    public function test_non_super_admin_cannot_access_synthetic_data_studio()
    {
        $this->actingAs($this->regularUser)
            ->get(route('super-admin.synthetic-data'))
            ->assertStatus(403);
    }

    /** @test */
    public function test_super_admin_can_access_synthetic_data_studio()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.synthetic-data'))
            ->assertStatus(200)
            ->assertSee('Synthetic Data Studio')
            ->assertSee('Run Synthetic Generator Now');
    }

    /** @test */
    public function test_presets_update_component_quantities_correctly()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(DemoDataGenerator::class)
            ->call('applyPreset', 'quick')
            ->assertSet('selectedPreset', 'quick')
            ->assertSet('bookingCount', 10)
            ->assertSet('customerCount', 10)
            ->call('applyPreset', 'stress')
            ->assertSet('selectedPreset', 'stress')
            ->assertSet('bookingCount', 60)
            ->assertSet('customerCount', 60);
    }

    /** @test */
    public function test_super_admin_can_interactively_generate_factory_data()
    {
        $initialBookings = Booking::where('marquee_id', $this->marquee->id)->count();

        Livewire::actingAs($this->superAdmin)
            ->test(DemoDataGenerator::class)
            ->set('selectedMarqueeId', $this->marquee->id)
            ->set('bookingCount', 5)
            ->set('customerCount', 5)
            ->set('staffCount', 2)
            ->set('expenseCount', 2)
            ->call('runGenerator')
            ->assertSet('feedbackType', 'success');

        $this->assertGreaterThan($initialBookings, Booking::where('marquee_id', $this->marquee->id)->count());
        $this->assertGreaterThan(0, Customer::where('marquee_id', $this->marquee->id)->count());
    }

    /** @test */
    public function test_super_admin_can_purge_synthetic_data()
    {
        // First generate some data
        Livewire::actingAs($this->superAdmin)
            ->test(DemoDataGenerator::class)
            ->set('selectedMarqueeId', $this->marquee->id)
            ->set('bookingCount', 3)
            ->call('runGenerator')
            ->assertSet('feedbackType', 'success');

        $this->assertGreaterThan(0, Booking::where('marquee_id', $this->marquee->id)->count());

        // Now purge
        Livewire::actingAs($this->superAdmin)
            ->test(DemoDataGenerator::class)
            ->set('selectedMarqueeId', $this->marquee->id)
            ->call('purgeData')
            ->assertSet('feedbackType', 'info');

        $this->assertEquals(0, Booking::where('marquee_id', $this->marquee->id)->count());
    }

    /** @test */
    public function test_generator_does_not_create_unwanted_phantom_marquees()
    {
        $initialMarquees = Marquee::count();
        $initialBranches = Branch::where('marquee_id', $this->marquee->id)->count();

        Livewire::actingAs($this->superAdmin)
            ->test(DemoDataGenerator::class)
            ->set('selectedMarqueeId', $this->marquee->id)
            ->set('newBranchesCount', 0)
            ->set('bookingCount', 5)
            ->set('customerCount', 5)
            ->set('staffCount', 4)
            ->set('attendanceDays', 5)
            ->set('expenseCount', 3)
            ->call('runGenerator')
            ->assertSet('feedbackType', 'success');

        // Verify that NO extra marquees were created
        $this->assertEquals($initialMarquees, Marquee::count());
    }

    /** @test */
    public function test_super_admin_can_generate_extra_branches()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(DemoDataGenerator::class)
            ->set('selectedMarqueeId', $this->marquee->id)
            ->set('newBranchesCount', 2)
            ->set('bookingCount', 0)
            ->set('customerCount', 0)
            ->set('staffCount', 0)
            ->call('runGenerator')
            ->assertSet('feedbackType', 'success');

        $this->assertGreaterThanOrEqual(2, Branch::where('marquee_id', $this->marquee->id)->count());
    }

    /** @test */
    public function test_super_admin_can_delete_demo_marquee()
    {
        $demoMarquee = Marquee::factory()->create(['name' => 'Temporary Demo Marquee']);

        Livewire::actingAs($this->superAdmin)
            ->test(DemoDataGenerator::class)
            ->set('selectedMarqueeId', $demoMarquee->id)
            ->set('purgeScope', 'delete_marquee')
            ->call('purgeData')
            ->assertSet('feedbackType', 'info');

        $this->assertNull(Marquee::find($demoMarquee->id));
    }

    /** @test */
    public function test_cli_artisan_data_generate_command_executes_successfully()
    {
        $this->artisan('data:generate', [
            '--marquee' => $this->marquee->id,
            '--preset' => 'quick',
        ])->assertSuccessful();

        $this->assertGreaterThan(0, Booking::where('marquee_id', $this->marquee->id)->count());
    }
}
