<?php

namespace Tests\Feature;

use App\Models\ExtraService;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExtraServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $plan;
    protected $ownerRole;
    protected $unauthorizedRole;
    protected $marqueeA;
    protected $marqueeB;
    protected $userA;
    protected $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Subscription Plan
        $this->plan = SubscriptionPlan::create([
            'name'             => 'Standard',
            'slug'             => 'standard',
            'price'            => 10000,
            'billing_interval' => 'month',
        ]);

        // Setup Roles and Permissions
        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $manageSettingsPermission = Permission::create(['name' => 'manage_settings', 'label' => 'Manage Settings']);
        $this->ownerRole->permissions()->sync([$manageSettingsPermission->id]);

        $this->unauthorizedRole = Role::create(['name' => 'store_keeper', 'label' => 'Store Keeper']);

        // Setup Marquees
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

        // Setup Users
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

    public function test_authorized_users_can_access_extra_services_index()
    {
        $response = $this->actingAs($this->userA)->get(route('extra-services.index'));
        $response->assertStatus(200);
        $response->assertSeeLivewire('extra-service-list');
    }

    public function test_unauthorized_users_cannot_access_extra_services_index()
    {
        $unauthorizedUser = User::create([
            'name'       => 'Storekeeper Staff',
            'email'      => 'keeper@marquee.com',
            'password'   => bcrypt('password'),
            'role_id'    => $this->unauthorizedRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $response = $this->actingAs($unauthorizedUser)->get(route('extra-services.index'));
        $response->assertStatus(403);
    }

    public function test_can_create_extra_service_with_validation()
    {
        Livewire::actingAs($this->userA);

        Livewire::test('extra-service-form')
            ->set('service_name', 'Stage Lighting & Laser Show')
            ->set('default_price', '35000')
            ->set('status', 'Active')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('extra-services.index'));

        $this->assertDatabaseHas('extra_services', [
            'service_name' => 'Stage Lighting & Laser Show',
            'default_price' => 35000.00,
            'status' => 'Active',
            'marquee_id' => $this->marqueeA->id,
        ]);
    }

    public function test_validation_rejects_empty_and_invalid_values()
    {
        Livewire::actingAs($this->userA);

        Livewire::test('extra-service-form')
            ->set('service_name', '') // Required
            ->set('default_price', 'abc') // Numeric
            ->call('save')
            ->assertHasErrors(['service_name', 'default_price']);
    }

    public function test_can_edit_existing_extra_service()
    {
        $extraService = ExtraService::create([
            'marquee_id' => $this->marqueeA->id,
            'service_name' => 'Sound System',
            'default_price' => 15000.00,
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->userA);

        Livewire::test('extra-service-form', ['extraService' => $extraService])
            ->set('service_name', 'Premium Bose Sound System')
            ->set('default_price', '22000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('extra-services.index'));

        $this->assertDatabaseHas('extra_services', [
            'id' => $extraService->id,
            'service_name' => 'Premium Bose Sound System',
            'default_price' => 22000.00,
        ]);
    }

    public function test_can_delete_extra_service()
    {
        $extraService = ExtraService::create([
            'marquee_id' => $this->marqueeA->id,
            'service_name' => 'Generator Backup',
            'default_price' => 25000.00,
            'status' => 'Active',
        ]);

        Livewire::actingAs($this->userA);

        Livewire::test('extra-service-list')
            ->call('confirmDeletion', $extraService->id)
            ->call('deleteRecord')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('extra_services', ['id' => $extraService->id]);
    }

    public function test_tenant_scope_isolation()
    {
        $extraServiceB = ExtraService::create([
            'marquee_id' => $this->marqueeB->id,
            'service_name' => 'Addon B',
            'default_price' => 5000.00,
            'status' => 'Active',
        ]);

        $this->actingAs($this->userA);

        // Accessing edit page of B by user A must abort 404/403 due to tenant scopes
        $response = $this->get(route('extra-services.edit', $extraServiceB->id));
        $response->assertStatus(404);
    }
}
