<?php

namespace Tests\Feature;

use App\Livewire\BranchForm;
use App\Livewire\Finance\TaxConfiguration;
use App\Livewire\MarqueeList;
use App\Models\Branch;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessOwnerBranchAndTaxAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $businessOwnerRole;
    protected $plan;
    protected $owner;
    protected $marquee1;
    protected $marquee2;
    protected $otherMarquee;
    protected $branch1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->businessOwnerRole = Role::whereIn('name', ['business_owner', 'owner'])->first();
        $this->plan = SubscriptionPlan::create([
            'name' => 'Unlimited Multi-Branch',
            'slug' => 'unlimited-multi',
            'code' => 'UNLIMITED',
            'price' => 10000,
            'max_marquees' => 5,
            'max_branches' => 10,
        ]);

        // Business Owner who owns marquees via marquee_owners pivot table
        $this->owner = User::create([
            'name' => 'Test Business Owner',
            'email' => 'owner@group.com',
            'username' => 'owner_group',
            'password' => bcrypt('Password123!'),
            'role_id' => $this->businessOwnerRole->id,
            'marquee_id' => null, // Multi-marquee owners have null marquee_id on user record
            'subscription_plan_id' => $this->plan->id,
            'subscription_ends_at' => now()->addYear(),
            'status' => 'active',
        ]);

        // First marquee owned by this owner
        $this->marquee1 = Marquee::create([
            'name' => 'Royal Palace Marquee',
            'email' => 'royal@palace.com',
            'phone' => '0300-1111111',
            'address' => 'Gulberg III, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'tax_authority' => 'PRA',
            'status' => 'active',
            'is_setup_completed' => true,
        ]);
        $this->owner->ownedMarquees()->attach($this->marquee1->id);

        // Second marquee owned by this owner
        $this->marquee2 = Marquee::create([
            'name' => 'Grand Arena Marquee',
            'email' => 'grand@arena.com',
            'phone' => '0300-2222222',
            'address' => 'DHA Phase 5, Lahore',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'tax_authority' => 'PRA',
            'status' => 'active',
            'is_setup_completed' => true,
        ]);
        $this->owner->ownedMarquees()->attach($this->marquee2->id);

        // Another marquee owned by a different user
        $this->otherMarquee = Marquee::create([
            'name' => 'Unrelated Marquee',
            'email' => 'other@marquee.com',
            'phone' => '0300-9999999',
            'address' => 'Rawalpindi',
            'city' => 'Rawalpindi',
            'province' => 'Punjab',
            'tax_authority' => 'PRA',
            'status' => 'active',
            'is_setup_completed' => true,
        ]);

        // Branch belonging to marquee 1
        $this->branch1 = Branch::create([
            'marquee_id' => $this->marquee1->id,
            'name' => 'Gulberg Branch',
            'address' => 'Main Boulevard, Gulberg III',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'phone' => '042-35876543',
            'status' => 'active',
            'fbr_pos_id' => 'OLD-POS-01',
            'fbr_pos_key' => 'old_key',
            'fbr_sandbox_mode' => true,
            'tax_rate' => 16.00,
        ]);
    }

    /** @test */
    public function test_business_owner_can_open_marquees_index_and_see_only_their_businesses()
    {
        $this->actingAs($this->owner);

        $response = $this->get(route('marquees.index'));
        $response->assertOk();

        Livewire::test(MarqueeList::class)
            ->assertOk()
            ->assertSee('Royal Palace Marquee')
            ->assertSee('Grand Arena Marquee')
            ->assertDontSee('Unrelated Marquee');
    }

    /** @test */
    public function test_business_owner_can_edit_branch_fbr_pos_id_and_key_via_branch_form()
    {
        $this->actingAs($this->owner);

        Livewire::test(BranchForm::class, ['branch' => $this->branch1])
            ->assertOk()
            ->set('fbr_pos_id', 'PRA-LHR-ROYAL-01')
            ->set('fbr_pos_key', 'pra_secret_key_12345')
            ->set('fbr_sandbox_mode', false)
            ->set('tax_rate', 5.00)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('branches.index'));

        $this->branch1->refresh();
        $this->assertEquals('PRA-LHR-ROYAL-01', $this->branch1->fbr_pos_id);
        $this->assertEquals('pra_secret_key_12345', $this->branch1->fbr_pos_key);
        $this->assertFalse((bool)$this->branch1->fbr_sandbox_mode);
        $this->assertEquals(5.00, (float)$this->branch1->tax_rate);
    }

    /** @test */
    public function test_business_owner_can_save_branch_pos_and_tax_via_tax_configuration()
    {
        $this->actingAs($this->owner);

        Livewire::test(TaxConfiguration::class)
            ->assertOk()
            ->set('selectedMarqueeId', $this->marquee1->id)
            ->set("branchData.{$this->branch1->id}.fbr_pos_id", 'PRA-GUL-UPDATED-09')
            ->set("branchData.{$this->branch1->id}.fbr_pos_key", 'new_auth_code_999')
            ->set("branchData.{$this->branch1->id}.fbr_sandbox_mode", true)
            ->set("branchData.{$this->branch1->id}.tax_rate", 16.00)
            ->call('saveBranch', $this->branch1->id)
            ->assertHasNoErrors()
            ->assertSee('saved successfully');

        $this->branch1->refresh();
        $this->assertEquals('PRA-GUL-UPDATED-09', $this->branch1->fbr_pos_id);
        $this->assertEquals('new_auth_code_999', $this->branch1->fbr_pos_key);
        $this->assertTrue((bool)$this->branch1->fbr_sandbox_mode);
        $this->assertEquals(16.00, (float)$this->branch1->tax_rate);
    }
}
