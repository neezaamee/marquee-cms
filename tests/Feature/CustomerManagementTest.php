<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\CustomerCommunicationLog;
use App\Models\Marquee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
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

        $this->plan = SubscriptionPlan::create([
            'name'             => 'Standard',
            'slug'             => 'standard',
            'price'            => 10000,
            'billing_interval' => 'month',
        ]);

        $this->ownerRole = Role::create(['name' => 'owner', 'label' => 'Owner']);
        $viewPermission = Permission::create(['name' => 'view_bookings', 'label' => 'View Bookings']);
        $createPermission = Permission::create(['name' => 'create_bookings', 'label' => 'Create Bookings']);
        $editPermission = Permission::create(['name' => 'edit_bookings', 'label' => 'Edit Bookings']);
        $deletePermission = Permission::create(['name' => 'delete_bookings', 'label' => 'Delete Bookings']);

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

    public function test_authorized_users_can_access_customers_index()
    {
        $response = $this->actingAs($this->userA)->get(route('customers.index'));
        $response->assertStatus(200);
        $response->assertSeeLivewire('customer-list');
    }

    public function test_unauthorized_users_cannot_access_customers_index()
    {
        $unauthorizedUser = User::create([
            'name'       => 'Unauthorized Staff',
            'email'      => 'staff@marquee.com',
            'password'   => bcrypt('password'),
            'role_id'    => $this->unauthorizedRole->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        $response = $this->actingAs($unauthorizedUser)->get(route('customers.index'));
        $response->assertStatus(403);
    }

    public function test_can_create_customer_with_validation()
    {
        Livewire::actingAs($this->userA);

        Livewire::test('customer-form')
            ->set('first_name', 'Ajmal')
            ->set('last_name', 'Khan')
            ->set('customer_type', 'Individual')
            ->set('phone_number', '03001234567')
            ->set('cnic_national_id', '35202-1234567-1')
            ->set('email', 'ajmal@test.com')
            ->set('referred_by_type', 'Walk-In')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'first_name' => 'Ajmal',
            'last_name' => 'Khan',
            'phone_number' => '03001234567',
            'customer_code' => 'CUST-00001',
            'marquee_id' => $this->marqueeA->id,
        ]);
    }

    public function test_phone_number_with_dashes_is_sanitized_and_validated()
    {
        Livewire::actingAs($this->userA);

        Livewire::test('customer-form')
            ->set('first_name', 'Ajmal')
            ->set('last_name', 'Khan')
            ->set('customer_type', 'Individual')
            ->set('phone_number', '0300-1234567')
            ->set('alternate_phone', '0321-7654321')
            ->set('cnic_national_id', '35202-1234567-1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'first_name' => 'Ajmal',
            'last_name' => 'Khan',
            'phone_number' => '03001234567',
            'alternate_phone' => '03217654321',
        ]);
    }

    public function test_sequential_customer_code_generation()
    {
        Customer::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_type' => 'Individual',
            'first_name' => 'Customer A',
            'last_name' => 'One',
            'phone_number' => '03001111111',
        ]);

        Customer::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_type' => 'Individual',
            'first_name' => 'Customer B',
            'last_name' => 'Two',
            'phone_number' => '03002222222',
        ]);

        $first = Customer::where('first_name', 'Customer A')->first();
        $second = Customer::where('first_name', 'Customer B')->first();

        $this->assertEquals('CUST-00001', $first->customer_code);
        $this->assertEquals('CUST-00002', $second->customer_code);
    }

    public function test_invalid_cnic_and_phone_number_format_validation()
    {
        Livewire::actingAs($this->userA);

        Livewire::test('customer-form')
            ->set('first_name', 'Ajmal')
            ->set('last_name', 'Khan')
            ->set('customer_type', 'Individual')
            ->set('phone_number', '12345') // Invalid phone format
            ->set('cnic_national_id', '3520212345671') // Invalid CNIC format
            ->call('save')
            ->assertHasErrors(['phone_number', 'cnic_national_id']);
    }

    public function test_corporate_requires_company_name()
    {
        Livewire::actingAs($this->userA);

        Livewire::test('customer-form')
            ->set('first_name', 'Elite')
            ->set('last_name', 'Corp')
            ->set('customer_type', 'Corporate')
            ->set('company_name', '') // Missing company name
            ->set('phone_number', '03001234567')
            ->call('save')
            ->assertHasErrors(['company_name']);
    }

    public function test_tenant_scope_isolation_prevents_unauthorized_data_sharing()
    {
        // Customer created under Marquee A
        $customerA = Customer::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_type' => 'Individual',
            'first_name' => 'Client A',
            'last_name' => 'A',
            'phone_number' => '03001111111',
        ]);

        // Customer created under Marquee B
        $customerB = Customer::create([
            'marquee_id' => $this->marqueeB->id,
            'customer_type' => 'Individual',
            'first_name' => 'Client B',
            'last_name' => 'B',
            'phone_number' => '03002222222',
        ]);

        // Log in as Owner A (Marquee A)
        $this->actingAs($this->userA);

        // Try to access Customer B's profile page - should return 404 (due to global tenant scope Route Model binding filtering it out)
        $response = $this->get(route('customers.show', $customerB->id));
        $response->assertStatus(404);

        // Try to access Customer A's profile page - should return 200
        $response = $this->get(route('customers.show', $customerA->id));
        $response->assertStatus(200);
    }

    public function test_can_upload_and_delete_documents()
    {
        Storage::fake('public');
        Livewire::actingAs($this->userA);

        $customer = Customer::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_type' => 'Individual',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '03001234567',
        ]);

        $file = UploadedFile::fake()->create('cnic_front.jpg', 500);

        Livewire::test('customer-profile', ['customer' => $customer])
            ->set('document_name', 'CNIC Front Side')
            ->set('document_type', 'CNIC Front')
            ->set('document_file', $file)
            ->call('uploadDocument')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customer_documents', [
            'customer_id' => $customer->id,
            'document_name' => 'CNIC Front Side',
            'document_type' => 'CNIC Front',
        ]);

        $doc = CustomerDocument::first();
        Storage::disk('public')->assertExists($doc->file_path);

        // Delete Document
        Livewire::test('customer-profile', ['customer' => $customer])
            ->call('deleteDocument', $doc->id);

        $this->assertDatabaseMissing('customer_documents', ['id' => $doc->id]);
        Storage::disk('public')->assertMissing($doc->file_path);
    }

    public function test_can_log_crm_activities()
    {
        Livewire::actingAs($this->userA);

        $customer = Customer::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_type' => 'Individual',
            'first_name' => 'CRM',
            'last_name' => 'Client',
            'phone_number' => '03001234567',
        ]);

        Livewire::test('customer-profile', ['customer' => $customer])
            ->set('comm_medium', 'Call')
            ->set('comm_subject', 'Follow-up Call')
            ->set('comm_content', 'Discussed venue setup and catering pricing.')
            ->call('logCommunication')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customer_communication_logs', [
            'customer_id' => $customer->id,
            'communication_medium' => 'Call',
            'subject' => 'Follow-up Call',
            'content' => 'Discussed venue setup and catering pricing.',
            'logged_by' => $this->userA->id,
        ]);
    }

    public function test_customer_list_can_search_by_referrer_name_and_contact()
    {
        // Create customers with referrers
        Customer::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_type' => 'Individual',
            'first_name' => 'Waqas',
            'last_name' => 'Ahmed',
            'phone_number' => '03001234567',
            'referred_by_name' => 'Farhan Shah',
            'referred_by_contact' => '03215551234',
        ]);

        Customer::create([
            'marquee_id' => $this->marqueeA->id,
            'customer_type' => 'Individual',
            'first_name' => 'Hamza',
            'last_name' => 'Ali',
            'phone_number' => '03007654321',
            'referred_by_name' => 'Zubair Khan',
            'referred_by_contact' => '03339998888',
        ]);

        Livewire::actingAs($this->userA);

        // Search by referrer name "Farhan"
        Livewire::test('customer-list')
            ->set('search', 'Farhan')
            ->assertSee('Waqas')
            ->assertDontSee('Hamza');

        // Search by referrer contact "0333999"
        Livewire::test('customer-list')
            ->set('search', '0333999')
            ->assertSee('Hamza')
            ->assertDontSee('Waqas');
    }
}
