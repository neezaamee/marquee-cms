<?php

namespace Tests\Feature;

use App\Livewire\Owner\TenantDefaultManager;
use App\Livewire\SetupWizard;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Hall;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SetupWizardTermsAndConditionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Marquee $marquee;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marquee = Marquee::create([
            'name' => 'Grand Royal Marquee',
            'business_type' => 'Single Marquee',
            'phone' => '03001234567',
            'email' => 'contact@grandroyal.com',
            'address' => 'Main Boulevard, Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'status' => 'active',
            'is_setup_completed' => false,
        ]);

        $this->branch = Branch::create([
            'marquee_id' => $this->marquee->id,
            'name' => 'Main Gulberg Branch',
            'phone' => '03001234568',
            'address' => 'Main Boulevard, Gulberg',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'is_head_office' => true,
            'status' => 'active',
            'tax_rate' => 13.00,
        ]);

        $ownerRole = Role::create([
            'name' => 'business_owner',
            'label' => 'Business Owner',
        ]);

        $permission = \App\Models\Permission::firstOrCreate(['name' => 'view_bookings', 'label' => 'View Bookings']);
        $ownerRole->permissions()->syncWithoutDetaching([$permission->id]);

        $this->owner = User::create([
            'name' => 'Haji Muhammad',
            'email' => 'owner@grandroyal.com',
            'password' => bcrypt('secret123'),
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        $this->owner->ownedMarquees()->attach($this->marquee->id);
    }

    public function test_setup_wizard_initializes_step_3_with_terms()
    {
        Livewire::actingAs($this->owner)
            ->test(SetupWizard::class)
            ->set('currentStep', 3)
            ->assertSee('Documentation Terms', false)
            ->assertSee('Booking Slip Terms', false)
            ->assertSee('Final Bill Conditions', false)
            ->assertSet('booking_slip_terms', function ($val) {
                return !empty($val) && str_contains($val, 'refundable security deposit');
            })
            ->assertSet('final_bill_conditions', function ($val) {
                return !empty($val) && str_contains($val, 'All payments must be settled');
            });
    }

    public function test_setup_wizard_saves_custom_terms_in_step_3()
    {
        $customSlipTerms = "Term A: 50% advance required upon booking.\nTerm B: Sound system must shut down by 10:00 PM.";
        $customBillConditions = "Policy 1: All final dues must be cleared on event day.\nPolicy 2: Extra stage decor will be invoiced separately.";

        Livewire::actingAs($this->owner)
            ->test(SetupWizard::class)
            ->set('currentStep', 3)
            ->set('booking_slip_terms', $customSlipTerms)
            ->set('final_bill_conditions', $customBillConditions)
            ->call('nextStep');

        $this->marquee->refresh();
        $this->branch->refresh();

        $this->assertEquals($customSlipTerms, $this->marquee->booking_slip_terms);
        $this->assertEquals($customBillConditions, $this->marquee->final_bill_conditions);
        $this->assertEquals($customSlipTerms, $this->branch->booking_slip_terms);
        $this->assertEquals($customBillConditions, $this->branch->final_bill_conditions);
    }

    public function test_booking_slip_renders_custom_terms()
    {
        $this->marquee->update([
            'is_setup_completed' => true,
            'booking_slip_terms' => "Custom Term 1: Strictly no external catering allowed.\nCustom Term 2: Hall must be vacated within 30 minutes of shift end.",
        ]);
        $this->branch->update([
            'booking_slip_terms' => "Custom Term 1: Strictly no external catering allowed.\nCustom Term 2: Hall must be vacated within 30 minutes of shift end.",
        ]);

        $booking = Booking::factory()->create([
            'marquee_id' => $this->marquee->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('bookings.slip-v2', $booking->id));
        $response->assertStatus(200);
        $response->assertSee('Strictly no external catering allowed', false);
        $response->assertSee('Hall must be vacated within 30 minutes', false);
    }

    public function test_tenant_default_manager_can_update_terms()
    {
        $newSlipTerms = "1. Advance is non-refundable on cancellation within 14 days.\n2. Security deposit returned after inspection.";
        $newBillConditions = "1. Net balance payable immediately.\n2. Cash or bank transfer accepted only.";

        Livewire::actingAs($this->owner)
            ->test(TenantDefaultManager::class)
            ->set('activeCategory', 'documentation_terms')
            ->set('booking_slip_terms', $newSlipTerms)
            ->set('final_bill_conditions', $newBillConditions)
            ->call('saveDocumentationTerms')
            ->assertHasNoErrors();

        $this->marquee->refresh();
        $this->assertEquals($newSlipTerms, $this->marquee->booking_slip_terms);
        $this->assertEquals($newBillConditions, $this->marquee->final_bill_conditions);
    }
}
