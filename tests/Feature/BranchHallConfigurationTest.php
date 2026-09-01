<?php

namespace Tests\Feature;

use App\Livewire\BranchForm;
use App\Livewire\HallForm;
use App\Models\Branch;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BranchHallConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected Marquee $marquee;
    protected Branch $branchMain;
    protected User $owner;
    protected User $branchManager;
    protected Role $bmRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marquee = Marquee::create([
            'name' => 'Signature Venues',
            'slug' => 'signature-venues',
            'is_active' => true,
            'status' => 'active',
            'email' => 'info@signature.com',
            'phone' => '03001234567',
            'address' => 'Mall Road',
            'city' => 'Lahore',
            'province' => 'Punjab',
        ]);

        $this->branchMain = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Gulberg Branch',
            'code' => 'BR-01',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Main Boulevard Gulberg',
            'phone' => '042-3571234',
            'status' => 'active',
            'is_head_office' => true,
            'tax_rate' => 16.00,
        ]);

        $ownerRole = Role::firstOrCreate(
            ['name' => 'owner'],
            ['display_name' => 'Owner', 'label' => 'Owner', 'marquee_id' => $this->marquee->id, 'description' => 'Owner']
        );

        $this->bmRole = Role::firstOrCreate(
            ['name' => 'branch_manager'],
            ['display_name' => 'Branch Manager', 'label' => 'Branch Manager', 'marquee_id' => $this->marquee->id, 'description' => 'Branch Manager']
        );

        // Ensure permissions exist
        $permView = Permission::firstOrCreate(['name' => 'view_halls'], ['label' => 'View Halls']);
        $permCreate = Permission::firstOrCreate(['name' => 'create_halls'], ['label' => 'Create Halls']);
        $permEdit = Permission::firstOrCreate(['name' => 'edit_halls'], ['label' => 'Edit Halls']);
        $permDelete = Permission::firstOrCreate(['name' => 'delete_halls'], ['label' => 'Delete Halls']);
        $permSettings = Permission::firstOrCreate(['name' => 'manage_settings'], ['label' => 'Manage Settings']);

        $ownerRole->permissions()->sync([$permView->id, $permCreate->id, $permEdit->id, $permDelete->id, $permSettings->id]);
        $this->bmRole->permissions()->sync([$permView->id, $permCreate->id, $permEdit->id, $permDelete->id, $permSettings->id]);

        $this->owner = User::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Owner User',
            'email' => 'owner@signature.com',
            'password' => bcrypt('password'),
            'role_id' => $ownerRole->id,
            'user_type' => 'business_owner',
            'branch_id' => null,
            'status' => 'active',
        ]);
        $this->marquee->update(['owner_user_id' => $this->owner->id]);

        $this->branchManager = User::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Branch Manager User',
            'email' => 'bm@signature.com',
            'password' => bcrypt('password'),
            'role_id' => $this->bmRole->id,
            'user_type' => 'branch_manager',
            'branch_id' => $this->branchMain->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test 1: When owner creates a new branch with starter hall enabled, both are created and linked.
     */
    public function test_owner_creating_branch_auto_creates_starter_hall(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(BranchForm::class)
            ->set('name', 'DHA Phase 6 Branch')
            ->set('phone', '0321-8611353')
            ->set('address', 'Commercial Broadway DHA Phase 6')
            ->set('province', 'Punjab')
            ->set('city', 'Lahore')
            ->set('status', 'active')
            ->set('tax_rate', 15.00)
            ->set('create_initial_hall', true)
            ->set('initial_hall_name', 'Grand Symphony Arena')
            ->set('initial_hall_code', 'HALL-DHA-01')
            ->set('initial_hall_capacity', 600)
            ->set('initial_hall_price', 75000)
            ->call('save')
            ->assertRedirect(route('branches.index'));

        $newBranch = Branch::where('name', 'DHA Phase 6 Branch')->first();
        $this->assertNotNull($newBranch);

        $starterHall = Hall::where('branch_id', $newBranch->id)->first();
        $this->assertNotNull($starterHall);
        $this->assertEquals('Grand Symphony Arena', $starterHall->hall_name);
        $this->assertEquals('HALL-DHA-01', $starterHall->hall_code);
        $this->assertEquals(600, $starterHall->capacity);
        $this->assertEquals(75000, $starterHall->default_booking_price);
        $this->assertEquals($this->marquee->id, $starterHall->marquee_id);
    }

    /**
     * Test 2: Branch Manager can add a new hall to their assigned branch.
     */
    public function test_branch_manager_can_create_hall_for_their_branch(): void
    {
        $this->actingAs($this->branchManager);

        Livewire::test(HallForm::class)
            ->assertSet('branch_id', $this->branchMain->id)
            ->set('hall_name', 'Crystal Ballroom')
            ->set('hall_code', 'CRYSTAL-01')
            ->set('capacity', 400)
            ->set('hall_type', 'Banquet Hall')
            ->set('default_booking_price', 60000)
            ->set('status', 'active')
            ->call('save')
            ->assertRedirect(route('halls.index'));

        $hall = Hall::where('hall_code', 'CRYSTAL-01')->first();
        $this->assertNotNull($hall);
        $this->assertEquals($this->branchMain->id, $hall->branch_id);
        $this->assertEquals('Crystal Ballroom', $hall->hall_name);
    }

    /**
     * Test 3: Branch Manager cannot add a hall to an unauthorized branch.
     */
    public function test_branch_manager_cannot_create_hall_for_other_branch(): void
    {
        $otherBranch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Faisalabad Branch',
            'code' => 'BR-FSD',
            'city' => 'Faisalabad',
            'province' => 'Punjab',
            'address' => 'Canal Road',
            'phone' => '041-8765432',
            'status' => 'active',
            'tax_rate' => 16.00,
        ]);

        $this->actingAs($this->branchManager);

        Livewire::test(HallForm::class)
            // Attempt to assign other branch
            ->set('branch_id', $otherBranch->id)
            ->set('hall_name', 'Unauthorized Hall')
            ->set('hall_code', 'UNAUTH-01')
            ->set('capacity', 300)
            ->set('hall_type', 'Banquet Hall')
            ->set('default_booking_price', 40000)
            ->set('status', 'active')
            ->call('save');

        // Hall must still be forced to branchMain
        $hall = Hall::where('hall_code', 'UNAUTH-01')->first();
        $this->assertNotNull($hall);
        $this->assertEquals($this->branchMain->id, $hall->branch_id);
    }

    /**
     * Test 4: Branch details view displays halls belonging to that branch.
     */
    public function test_branch_details_view_shows_halls_and_add_button(): void
    {
        $hall = Hall::create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branchMain->id,
            'hall_name' => 'Executive Hall',
            'hall_code' => 'EXEC-01',
            'capacity' => 250,
            'hall_type' => 'Seminar Room',
            'default_booking_price' => 30000,
            'status' => 'active',
        ]);

        $this->actingAs($this->owner);

        $response = $this->get(route('branches.show', $this->branchMain->id));
        $response->assertOk();
        $response->assertSee('Executive Hall');
        $response->assertSee('EXEC-01');
        $response->assertSee(route('halls.create', ['branch_id' => $this->branchMain->id]));
    }

    /**
     * Test 5: Business Owner can create a hall for a newly created branch.
     */
    public function test_business_owner_can_create_hall_for_newly_created_branch(): void
    {
        $this->actingAs($this->owner);

        // 1. Create a new branch
        $newBranch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Brand New DHA Branch',
            'code' => 'BR-DHA-99',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'DHA Phase 5 Block CCA',
            'phone' => '042-3571234',
            'status' => 'active',
            'tax_rate' => 16.00,
        ]);

        // 2. Load HallForm and check if DHA Branch can be selected and saved
        Livewire::test(HallForm::class)
            ->set('branch_id', $newBranch->id)
            ->set('hall_name', 'Grand Symphony Arena')
            ->set('hall_code', 'HALL-DHA-99')
            ->set('capacity', 600)
            ->set('hall_type', 'Banquet Hall')
            ->set('default_booking_price', 75000)
            ->set('status', 'active')
            ->call('save')
            ->assertRedirect(route('halls.index'));

        $hall = Hall::where('hall_code', 'HALL-DHA-99')->first();
        $this->assertNotNull($hall);
        $this->assertEquals($newBranch->id, $hall->branch_id);
    }

    /**
     * Test 6: Business Owner with a default branch_id set can create a hall for another branch.
     */
    public function test_business_owner_with_default_branch_can_create_hall_for_another_branch(): void
    {
        // 1. Force a branch_id on the owner to simulate the database state
        $this->owner->branch_id = $this->branchMain->id;
        $this->owner->save();

        $this->actingAs($this->owner);

        // 2. Create another branch
        $newBranch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Brand New DHA Branch B',
            'code' => 'BR-DHA-99B',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'DHA Phase 5 Block CCB',
            'phone' => '042-3571234',
            'status' => 'active',
            'tax_rate' => 16.00,
        ]);

        // 3. Try to save a hall under the new branch using HallForm
        Livewire::test(HallForm::class)
            ->set('branch_id', $newBranch->id)
            ->set('hall_name', 'Grand Symphony B')
            ->set('hall_code', 'HALL-DHA-99B')
            ->set('capacity', 600)
            ->set('hall_type', 'Banquet Hall')
            ->set('default_booking_price', 75000)
            ->set('status', 'active')
            ->call('save')
            ->assertRedirect(route('halls.index'));

        // Verify the hall was created under the new branch, NOT the owner's default branchMain
        $hall = Hall::where('hall_code', 'HALL-DHA-99B')->first();
        $this->assertNotNull($hall);
        $this->assertEquals($newBranch->id, $hall->branch_id);
    }
}
