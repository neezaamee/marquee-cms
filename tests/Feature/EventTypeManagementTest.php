<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EventType;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\EventTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $ownerRole;
    protected $unauthorizedRole;
    protected $marqueeA;
    protected $marqueeB;
    protected $branchA1;
    protected $branchA2;
    protected $userA;
    protected $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = SubscriptionPlan::create([
            'name'             => 'Standard',
            'slug'             => 'standard',
            'price'            => 10000,
            'billing_interval' => 'month',
        ]);

        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $viewPermission = Permission::create(['name' => 'event-types.view', 'label' => 'View Event Types']);
        $createPermission = Permission::create(['name' => 'event-types.create', 'label' => 'Create Event Types']);
        $editPermission = Permission::create(['name' => 'event-types.edit', 'label' => 'Edit Event Types']);
        $deletePermission = Permission::create(['name' => 'event-types.delete', 'label' => 'Delete Event Types']);

        $this->ownerRole->permissions()->sync([
            $viewPermission->id,
            $createPermission->id,
            $editPermission->id,
            $deletePermission->id,
        ]);

        $this->unauthorizedRole = Role::create(['name' => 'store_keeper', 'label' => 'Store Keeper']);

        $this->marqueeA = Marquee::create([
            'name'                 => 'Marquee Lahore A',
            'address'             => '123 St',
            'city'                => 'Lahore',
            'province'            => 'Punjab',
            'phone'               => '+923001234567',
            'email'               => 'a@marquee.com',
            'status'              => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);

        $this->marqueeB = Marquee::create([
            'name'                 => 'Marquee Karachi B',
            'address'             => '456 St',
            'city'                => 'Karachi',
            'province'            => 'Sindh',
            'phone'               => '+923007654321',
            'email'               => 'b@marquee.com',
            'status'              => 'active',
            'subscription_plan_id' => $this->plan->id,
        ]);

        $this->branchA1 = Branch::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Lahore Branch 1',
            'address' => 'Gulberg', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '1',
        ]);

        $this->branchA2 = Branch::create([
            'marquee_id' => $this->marqueeA->id,
            'name' => 'Lahore Branch 2',
            'address' => 'DHA', 'city' => 'Lahore', 'province' => 'Punjab', 'phone' => '2',
        ]);

        $this->userA = User::create([
            'name'       => 'Owner A',
            'email'      => 'owner.a@marquee.com',
            'password'   => bcrypt('password'),
            'role_id'    => $this->ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $this->userB = User::create([
            'name'       => 'Owner B',
            'email'      => 'owner.b@marquee.com',
            'password'   => bcrypt('password'),
            'role_id'    => $this->ownerRole->id,
            'marquee_id' => $this->marqueeB->id,
        ]);
    }

    public function test_authorized_users_can_access_event_types_index()
    {
        $response = $this->actingAs($this->userA)->get(route('event-types.index'));
        $response->assertStatus(200);
        $response->assertSeeLivewire('event-type-list');
    }

    public function test_unauthorized_users_cannot_access_event_types_index()
    {
        $unauthorizedUser = User::create([
            'name'       => 'Unauthorized Staff',
            'email'      => 'staff@marquee.com',
            'password'   => bcrypt('password'),
            'role_id'    => $this->unauthorizedRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $response = $this->actingAs($unauthorizedUser)->get(route('event-types.index'));
        $response->assertStatus(403);
    }

    public function test_can_create_event_type_with_validation()
    {
        Livewire::actingAs($this->userA);

        Livewire::test('event-type-form')
            ->set('event_type_name', 'Mehndi Ceremony')
            ->set('event_type_code', 'MEHN')
            ->set('base_price', '45000')
            ->set('default_duration_hours', '4.5')
            ->set('status', 'Active')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('event-types.index'));

        $this->assertDatabaseHas('event_types', [
            'event_type_name' => 'Mehndi Ceremony',
            'event_type_code' => 'MEHN',
            'base_price' => 45000.00,
            'default_duration_hours' => 4.50,
            'marquee_id' => $this->marqueeA->id,
        ]);
    }

    public function test_event_type_code_must_be_unique_within_same_marquee()
    {
        // First Event Type
        EventType::create([
            'marquee_id' => $this->marqueeA->id,
            'event_type_name' => 'Wedding',
            'event_type_code' => 'WEDD',
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->userA);

        Livewire::test('event-type-form')
            ->set('event_type_name', 'Another Wedding')
            ->set('event_type_code', 'WEDD') // Duplicate code
            ->call('save')
            ->assertHasErrors(['event_type_code' => 'unique']);
    }

    public function test_validation_rejects_invalid_numeric_values()
    {
        Livewire::actingAs($this->userA);

        Livewire::test('event-type-form')
            ->set('event_type_name', 'Mehndi')
            ->set('event_type_code', 'MEHN')
            ->set('base_price', 'abc') // Invalid numeric
            ->set('default_duration_hours', 'xyz') // Invalid numeric
            ->call('save')
            ->assertHasErrors(['base_price', 'default_duration_hours']);
    }

    public function test_system_default_event_type_cannot_be_deleted()
    {
        $systemDefault = EventType::create([
            'marquee_id' => $this->marqueeA->id,
            'event_type_name' => 'Wedding',
            'event_type_code' => 'WEDD',
            'is_system_default' => true,
            'status' => 'Active',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('System default event types cannot be deleted.');

        $systemDefault->delete();
    }

    public function test_system_default_is_protected_from_deletion_in_livewire()
    {
        Livewire::actingAs($this->userA);

        $systemDefault = EventType::create([
            'marquee_id' => $this->marqueeA->id,
            'event_type_name' => 'Wedding',
            'event_type_code' => 'WEDD',
            'is_system_default' => true,
            'status' => 'Active',
        ]);

        Livewire::test('event-type-list')
            ->call('confirmDeletion', $systemDefault->id)
            ->call('deleteRecord')
            ->assertSee('System default event types cannot be deleted.'); // Flashed error

        // Make sure it still exists in the database
        $this->assertDatabaseHas('event_types', ['id' => $systemDefault->id, 'deleted_at' => null]);
    }

    public function test_custom_event_type_can_be_soft_deleted()
    {
        Livewire::actingAs($this->userA);

        $custom = EventType::create([
            'marquee_id' => $this->marqueeA->id,
            'event_type_name' => 'Custom Party',
            'event_type_code' => 'CUST',
            'is_system_default' => false,
            'status' => 'Active',
        ]);

        Livewire::test('event-type-list')
            ->call('confirmDeletion', $custom->id)
            ->call('deleteRecord')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('event_types', ['id' => $custom->id]);
    }

    public function test_tenant_scope_isolation()
    {
        $eventTypeB = EventType::create([
            'marquee_id' => $this->marqueeB->id,
            'event_type_name' => 'Barat B',
            'event_type_code' => 'BRAT',
            'status' => 'Active',
        ]);

        $this->actingAs($this->userA);

        // Accessing EventType B show page must return 404 due to global scope
        $response = $this->get(route('event-types.show', $eventTypeB->id));
        $response->assertStatus(404);
    }

    public function test_helper_service_returns_correct_eligible_event_types()
    {
        // 1. Marquee-wide event type (active)
        $globalActive = EventType::create([
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => null,
            'event_type_name' => 'Global Active Wedding',
            'event_type_code' => 'WED1',
            'status' => 'Active',
            'sort_order' => 1,
        ]);

        // 2. Marquee-wide event type (inactive)
        $globalInactive = EventType::create([
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => null,
            'event_type_name' => 'Global Inactive Wedding',
            'event_type_code' => 'WED2',
            'status' => 'Inactive',
            'sort_order' => 2,
        ]);

        // 3. Branch 1 specific event type (active)
        $branch1Active = EventType::create([
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA1->id,
            'event_type_name' => 'Branch 1 Special',
            'event_type_code' => 'BR1A',
            'status' => 'Active',
            'sort_order' => 3,
        ]);

        // 4. Branch 2 specific event type (active)
        $branch2Active = EventType::create([
            'marquee_id' => $this->marqueeA->id,
            'branch_id' => $this->branchA2->id,
            'event_type_name' => 'Branch 2 Special',
            'event_type_code' => 'BR2A',
            'status' => 'Active',
            'sort_order' => 4,
        ]);

        $service = new EventTypeService();

        // Query active event types for Branch 1
        $branch1Results = $service->getActiveEventTypesForBooking($this->marqueeA->id, $this->branchA1->id);

        $this->assertCount(2, $branch1Results); // Global Active + Branch 1 Active
        $this->assertTrue($branch1Results->contains('id', $globalActive->id));
        $this->assertTrue($branch1Results->contains('id', $branch1Active->id));
        $this->assertFalse($branch1Results->contains('id', $globalInactive->id));
        $this->assertFalse($branch1Results->contains('id', $branch2Active->id));

        // Query active event types for general Marquee bookings (no branch specified)
        $generalResults = $service->getActiveEventTypesForBooking($this->marqueeA->id);

        $this->assertCount(1, $generalResults); // Global Active only
        $this->assertTrue($generalResults->contains('id', $globalActive->id));
        $this->assertFalse($generalResults->contains('id', $branch1Active->id));
    }
}
