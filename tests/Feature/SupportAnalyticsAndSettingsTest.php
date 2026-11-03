<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Support\SupportDashboard;
use App\Livewire\SuperAdmin\Support\SupportSettingsManager;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SupportCategory;
use App\Models\SupportSetting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Support\SlaManagementService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SupportHelpModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupportAnalyticsAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $tenantOwner;
    protected $marquee;
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
            'email' => 'superadmin_phase5@marquee.com',
        ]);

        $this->marquee = Marquee::factory()->create([
            'name' => 'Grand Marquee SLA Test',
            'business_type' => 'marquee',
            'is_setup_completed' => true,
        ]);

        $this->tenantOwner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'email' => 'owner_phase5@marquee.com',
        ]);

        $this->category = SupportCategory::first();
    }

    public function test_super_admin_can_access_support_dashboard()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.support.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Support Operations &amp; SLA Analytics', false);
        $response->assertSee('Ticket Volume');
        $response->assertSee('SLA Performance');
    }

    public function test_tenant_owner_cannot_access_support_dashboard()
    {
        $response = $this->actingAs($this->tenantOwner)->get(route('admin.support.dashboard'));
        $response->assertStatus(403);
    }

    public function test_sla_management_service_calculates_metrics_accurately()
    {
        // 1. Ticket with First Response & Resolution & CSAT Rating
        $t1 = SupportTicket::create([
            'marquee_id' => $this->marquee->id,
            'user_id' => $this->tenantOwner->id,
            'category_id' => $this->category->id,
            'ticket_number' => 'TKT-2026-9001',
            'subject' => 'Billing Inquiry',
            'description' => 'Need clarification on subscription invoice.',
            'priority' => 'normal',
            'status' => 'resolved',
            'first_response_at' => Carbon::now()->subHours(4), // 1 hr response
            'resolved_at' => Carbon::now()->subHours(1), // 4 hr resolution
            'sla_due_at' => Carbon::now()->addHours(20),
            'satisfaction_rating' => 5,
            'satisfaction_comment' => 'Outstanding prompt help!',
        ]);
        $t1->created_at = Carbon::now()->subHours(5);
        $t1->save();

        // 2. Ticket that is Breached
        $t2 = SupportTicket::create([
            'marquee_id' => $this->marquee->id,
            'user_id' => $this->tenantOwner->id,
            'category_id' => $this->category->id,
            'ticket_number' => 'TKT-2026-9002',
            'subject' => 'Critical Server Outage',
            'description' => 'Printing service failed.',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'sla_due_at' => Carbon::now()->subHours(6), // Overdue by 6 hrs
            'is_sla_breached' => false,
        ]);
        $t2->created_at = Carbon::now()->subHours(10);
        $t2->save();

        // Calculate metrics
        $metrics = SlaManagementService::calculateMetrics();

        $this->assertEquals(2, $metrics['total_tickets']);
        $this->assertEquals(1, $metrics['open_tickets']);
        $this->assertEquals(1, $metrics['resolved_tickets']);
        $this->assertEquals(1, $metrics['breached_count']); // Auto-detected
        $this->assertEquals(50.0, $metrics['breach_rate']);
        $this->assertEquals(1.0, $metrics['avg_first_response_hours']);
        $this->assertEquals(4.0, $metrics['avg_resolution_hours']);
        $this->assertEquals(5.0, $metrics['csat_average']);
        $this->assertEquals(1, $metrics['csat_breakdown'][5]);

        // Verify t2 was updated in DB
        $t2->refresh();
        $this->assertTrue($t2->is_sla_breached);
    }

    public function test_sla_breach_auto_detection_updates_database()
    {
        $ticket = SupportTicket::create([
            'marquee_id' => $this->marquee->id,
            'user_id' => $this->tenantOwner->id,
            'category_id' => $this->category->id,
            'ticket_number' => 'TKT-2026-9003',
            'subject' => 'Overdue High Priority Ticket',
            'description' => 'Test overdue detection.',
            'priority' => 'high',
            'status' => 'assigned',
            'created_at' => Carbon::now()->subHours(20),
            'sla_due_at' => Carbon::now()->subHours(2),
            'is_sla_breached' => false,
        ]);

        $this->assertFalse($ticket->is_sla_breached);

        $updatedCount = SlaManagementService::checkAndUpdateSlaBreaches();
        $this->assertGreaterThanOrEqual(1, $updatedCount);

        $ticket->refresh();
        $this->assertTrue($ticket->is_sla_breached);
    }

    public function test_super_admin_can_access_and_save_support_settings()
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.support.settings'));
        $response->assertStatus(200);
        $response->assertSee('Support Desk Configuration &amp; SLA Matrix', false);

        Livewire::actingAs($this->superAdmin)
            ->test(SupportSettingsManager::class)
            ->assertSet('ticketPrefix', 'TKT')
            ->set('ticketPrefix', 'MARQ')
            ->set('supportEmail', 'ops@marquee-enterprise.com')
            ->set('supportPhone', '+92 42 111-222-333')
            ->set('autoCloseDays', 14)
            ->set('businessStart', '08:30')
            ->set('businessEnd', '19:30')
            ->set('businessTimezone', 'Asia/Karachi')
            ->set('slaUrgentResolution', 3)
            ->call('saveSettings')
            ->assertHasNoErrors()
            ->assertSee('Support configuration and SLA parameters updated successfully.');

        $this->assertEquals('MARQ', SupportSetting::get('ticket_prefix'));
        $this->assertEquals('ops@marquee-enterprise.com', SupportSetting::get('support_email'));
        $this->assertEquals('+92 42 111-222-333', SupportSetting::get('support_phone'));
        $this->assertEquals(14, SupportSetting::get('auto_close_days'));
        $sla = SupportSetting::get('sla_targets_hours');
        $this->assertEquals(3, $sla['urgent']['resolution']);
    }

    public function test_tenant_owner_cannot_access_or_modify_support_settings()
    {
        $response = $this->actingAs($this->tenantOwner)->get(route('admin.support.settings'));
        $response->assertStatus(403);
    }

    public function test_support_ticket_csv_export_streams_audit_data()
    {
        $ticket = SupportTicket::create([
            'marquee_id' => $this->marquee->id,
            'user_id' => $this->tenantOwner->id,
            'category_id' => $this->category->id,
            'ticket_number' => 'TKT-2026-CSV1',
            'subject' => 'CSV Export Validation Ticket',
            'description' => 'Validating streamed CSV output.',
            'priority' => 'urgent',
            'status' => 'open',
            'created_at' => Carbon::now(),
            'sla_due_at' => Carbon::now()->addHours(4),
            'is_sla_breached' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.support.export.tickets'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $streamedContent = $response->streamedContent();
        $this->assertStringContainsString('Ticket ID', $streamedContent);
        $this->assertStringContainsString('Marquee Business', $streamedContent);
        $this->assertStringContainsString('SLA Breached', $streamedContent);
        $this->assertStringContainsString('TKT-2026-CSV1', $streamedContent);
        $this->assertStringContainsString('Grand Marquee SLA Test', $streamedContent);
        $this->assertStringContainsString('CSV Export Validation Ticket', $streamedContent);
    }

    public function test_tenant_owner_cannot_export_platform_tickets_csv()
    {
        $response = $this->actingAs($this->tenantOwner)->get(route('admin.support.export.tickets'));
        $response->assertStatus(403);
    }
}
