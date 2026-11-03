<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Support\AnnouncementManager;
use App\Livewire\SuperAdmin\Support\ChangelogManager;
use App\Livewire\SuperAdmin\Support\ContactRequestManager;
use App\Livewire\Support\AnnouncementBanner;
use App\Livewire\Support\ChangelogViewer;
use App\Livewire\Support\ContactSupport;
use App\Models\Announcement;
use App\Models\AnnouncementAcknowledgment;
use App\Models\ContactRequest;
use App\Models\Marquee;
use App\Models\ProductChangelog;
use App\Models\Role;
use App\Models\SupportCategory;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SupportHelpModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupportChangelogAnnouncementContactTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $tenantOwnerA;
    protected $tenantOwnerB;
    protected $marqueeA;
    protected $marqueeB;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SupportHelpModuleSeeder::class);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $ownerRole = Role::where('name', 'business_owner')->first();

        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'email' => 'superadmin_phase4@marquee.com',
        ]);

        $this->marqueeA = Marquee::factory()->create([
            'name' => 'Grand Marquee Alpha',
            'business_type' => 'marquee',
            'is_setup_completed' => true,
        ]);

        $this->tenantOwnerA = User::factory()->create([
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
            'email' => 'owner_phase4_a@marquee.com',
        ]);
        $this->tenantOwnerA->ownedMarquees()->attach($this->marqueeA->id);

        $this->marqueeB = Marquee::factory()->create([
            'name' => 'Banquet Beta',
            'business_type' => 'marquee',
            'is_setup_completed' => true,
        ]);

        $this->tenantOwnerB = User::factory()->create([
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marqueeB->id,
            'email' => 'owner_phase4_b@marquee.com',
        ]);
        $this->tenantOwnerB->ownedMarquees()->attach($this->marqueeB->id);

        $this->category = SupportCategory::first();
    }

    public function test_unauthenticated_user_cannot_access_protected_pages()
    {
        $this->get(route('support.changelog.index'))->assertRedirect('/login');
        $this->get(route('support.contact'))->assertRedirect('/login');
        $this->get(route('admin.support.changelog'))->assertRedirect('/login');
        $this->get(route('admin.support.announcements'))->assertRedirect('/login');
        $this->get(route('admin.support.contacts'))->assertRedirect('/login');
    }

    public function test_tenant_can_view_published_changelogs_and_filter()
    {
        ProductChangelog::create([
            'version' => 'v1.38.0',
            'title' => 'Live Support Chat Launch',
            'summary' => 'Real-time messaging for hall staff.',
            'description' => 'Detailed walkthrough of chat features.',
            'release_date' => now()->subDay(),
            'release_type' => 'new_feature',
            'affected_modules' => ['support', 'billing'],
            'is_published' => true,
        ]);

        ProductChangelog::create([
            'version' => 'v1.39.0-draft',
            'title' => 'Unpublished Internal Release',
            'description' => 'Not yet visible to clients.',
            'release_date' => now(),
            'release_type' => 'bug_fix',
            'is_published' => false,
        ]);

        $this->actingAs($this->tenantOwnerA);

        $component = Livewire::test(ChangelogViewer::class);
        $component->assertSee('v1.38.0');
        $component->assertSee('Live Support Chat Launch');
        $component->assertDontSee('Unpublished Internal Release');

        // Test filtering by type
        $component->set('typeFilter', 'bug_fix');
        $component->assertDontSee('Live Support Chat Launch');

        $component->set('typeFilter', 'new_feature');
        $component->assertSee('Live Support Chat Launch');
    }

    public function test_super_admin_can_manage_changelog_entries()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ChangelogManager::class)
            ->set('version', 'v1.40.0')
            ->set('title', 'WhatsApp Event Contract Notifications')
            ->set('summary', 'Instant customer alerts on signature.')
            ->set('release_date', Carbon::today()->format('Y-m-d'))
            ->set('release_type', 'new_feature')
            ->set('affected_modules', ['bookings', 'pos'])
            ->set('description', 'Comprehensive automated notification integration.')
            ->set('is_published', true)
            ->call('save')
            ->assertHasNoErrors();

        $changelog = ProductChangelog::where('version', 'v1.40.0')->first();
        $this->assertNotNull($changelog);
        $this->assertEquals('WhatsApp Event Contract Notifications', $changelog->title);

        // Toggle publish
        Livewire::test(ChangelogManager::class)->call('togglePublish', $changelog->id);
        $changelog->refresh();
        $this->assertFalse($changelog->is_published);
    }

    public function test_active_announcement_renders_for_targeted_user()
    {
        Announcement::create([
            'title' => 'Scheduled Cloud Backup Window',
            'message' => 'System maintenance scheduled on Sunday 2:00 AM PKT.',
            'severity' => 'warning',
            'start_date' => now()->subHour(),
            'end_date' => now()->addDays(2),
            'target_audience' => 'all',
            'is_published' => true,
        ]);

        $this->actingAs($this->tenantOwnerA);

        $component = Livewire::test(AnnouncementBanner::class);
        $component->assertSee('Scheduled Cloud Backup Window');
        $component->assertSee('Sunday 2:00 AM PKT');
    }

    public function test_inactive_or_expired_announcements_do_not_render()
    {
        // 1. Unpublished
        Announcement::create([
            'title' => 'Unpublished Notice',
            'message' => 'Should never render.',
            'severity' => 'info',
            'is_published' => false,
        ]);

        // 2. Expired in the past
        Announcement::create([
            'title' => 'Expired Notice',
            'message' => 'Was active last week.',
            'severity' => 'info',
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDay(),
            'is_published' => true,
        ]);

        // 3. Scheduled in the future
        Announcement::create([
            'title' => 'Future Notice',
            'message' => 'Scheduled for next month.',
            'severity' => 'info',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'is_published' => true,
        ]);

        $this->actingAs($this->tenantOwnerA);

        $component = Livewire::test(AnnouncementBanner::class);
        $component->assertDontSee('Unpublished Notice');
        $component->assertDontSee('Expired Notice');
        $component->assertDontSee('Future Notice');
    }

    public function test_audience_targeted_announcement_renders_only_for_target_tenant()
    {
        // Targeted strictly to Marquee A
        Announcement::create([
            'title' => 'Alpha Marquee Custom Feature Activated',
            'message' => 'Special custom catering module enabled for your halls.',
            'severity' => 'success',
            'target_audience' => 'selected_tenants',
            'target_ids' => [(int) $this->marqueeA->id],
            'is_published' => true,
        ]);

        // Tenant A views banner -> sees announcement
        $this->actingAs($this->tenantOwnerA);
        Livewire::test(AnnouncementBanner::class)
            ->assertSee('Alpha Marquee Custom Feature Activated');

        // Tenant B views banner -> does NOT see announcement
        $this->actingAs($this->tenantOwnerB);
        Livewire::test(AnnouncementBanner::class)
            ->assertDontSee('Alpha Marquee Custom Feature Activated');
    }

    public function test_user_can_acknowledge_announcement_and_dismiss_it()
    {
        $announcement = Announcement::create([
            'title' => 'Critical Policy Revision',
            'message' => 'Please confirm you have read the updated terms of service.',
            'severity' => 'critical',
            'target_audience' => 'all',
            'requires_acknowledgment' => true,
            'is_published' => true,
        ]);

        $this->actingAs($this->tenantOwnerA);

        $component = Livewire::test(AnnouncementBanner::class);
        $component->assertSee('Critical Policy Revision');
        $component->assertSee('I Acknowledge');

        // Acknowledge notice
        $component->call('acknowledge', $announcement->id);

        $this->assertDatabaseHas('announcement_acknowledgments', [
            'announcement_id' => $announcement->id,
            'user_id' => $this->tenantOwnerA->id,
        ]);

        // Re-render -> notice is dismissed
        Livewire::test(AnnouncementBanner::class)
            ->assertDontSee('Critical Policy Revision');
    }

    public function test_customer_can_submit_contact_request()
    {
        $this->actingAs($this->tenantOwnerA);

        Livewire::test(ContactSupport::class)
            ->set('name', 'Hassan Raza')
            ->set('email', 'hassan@palacebanquet.pk')
            ->set('phone', '0321-9876543')
            ->set('company_name', 'Palace Banquet Hall')
            ->set('category', 'sales')
            ->set('preferred_contact_method', 'whatsapp')
            ->set('subject', 'Request quotation for 3 additional branch licenses')
            ->set('message', 'We are expanding to Rawalpindi and need 3 new marquee branch configurations.')
            ->call('submitInquiry')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('contact_requests', [
            'name' => 'Hassan Raza',
            'email' => 'hassan@palacebanquet.pk',
            'preferred_contact_method' => 'whatsapp',
            'category' => 'sales',
            'status' => 'new',
        ]);
    }

    public function test_super_admin_can_manage_contact_requests_and_update_notes()
    {
        $contact = ContactRequest::create([
            'name' => 'Ahmad Khan',
            'email' => 'ahmad@khanmarquees.com',
            'phone' => '0300-5551234',
            'company_name' => 'Khan Marquee Complex',
            'category' => 'enterprise',
            'subject' => 'Custom API integration query',
            'message' => 'We want to hook our biometric fingerprint scanners into staff payroll.',
            'preferred_contact_method' => 'phone',
            'status' => 'new',
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ContactRequestManager::class)
            ->call('review', $contact->id)
            ->set('contactStatus', 'contacted')
            ->set('assignedTo', $this->superAdmin->id)
            ->set('internalNotes', 'Called client on phone. Demo scheduled for Tuesday 3 PM.')
            ->set('followUpDate', Carbon::today()->addDays(2)->format('Y-m-d'))
            ->call('saveReview')
            ->assertHasNoErrors();

        $contact->refresh();
        $this->assertEquals('contacted', $contact->status);
        $this->assertEquals($this->superAdmin->id, $contact->assigned_to);
        $this->assertEquals('Called client on phone. Demo scheduled for Tuesday 3 PM.', $contact->internal_notes);
        $this->assertNotNull($contact->follow_up_date);
    }
}
