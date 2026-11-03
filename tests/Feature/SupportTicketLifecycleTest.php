<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Support\AdminTicketDetail;
use App\Livewire\SuperAdmin\Support\AdminTicketQueue;
use App\Livewire\Support\TicketCreate;
use App\Livewire\Support\TicketDetail;
use App\Livewire\Support\TicketList;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SupportHelpModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SupportTicketLifecycleTest extends TestCase
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
            'email' => 'superadmin_tickets@marquee.com',
        ]);

        $this->marqueeA = Marquee::factory()->create([
            'name' => 'Marquee A Palace',
            'business_type' => 'marquee',
            'is_setup_completed' => true,
        ]);

        $this->tenantOwnerA = User::factory()->create([
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
            'email' => 'owner_a@marquee.com',
        ]);
        $this->tenantOwnerA->ownedMarquees()->attach($this->marqueeA->id);

        $this->marqueeB = Marquee::factory()->create([
            'name' => 'Marquee B Banquet',
            'business_type' => 'marquee',
            'is_setup_completed' => true,
        ]);

        $this->tenantOwnerB = User::factory()->create([
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marqueeB->id,
            'email' => 'owner_b@marquee.com',
        ]);
        $this->tenantOwnerB->ownedMarquees()->attach($this->marqueeB->id);

        $this->category = SupportCategory::first();
    }

    public function test_tenant_can_view_ticket_list_and_metrics()
    {
        $this->actingAs($this->tenantOwnerA);

        SupportTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'category_id' => $this->category->id,
            'subject' => 'Issue with booking calendar',
            'description' => 'Cannot view booked slots for next month.',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        $response = $this->get(route('support.tickets.index'));
        $response->assertStatus(200);
        $response->assertSee('TKT-2026-0001');
        $response->assertSee('Issue with booking calendar');
    }

    public function test_tenant_can_create_ticket_with_sequential_number_and_sla()
    {
        $this->actingAs($this->tenantOwnerA);

        Livewire::test(TicketCreate::class)
            ->set('subject', 'Payment gateway error on checkout')
            ->set('category_id', $this->category->id)
            ->set('priority', 'urgent')
            ->set('related_module', 'billing')
            ->set('related_record_id', 105)
            ->set('description', 'When customer tries to pay advance via credit card, 500 error is thrown.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $ticket = SupportTicket::first();
        $this->assertNotNull($ticket);
        $this->assertEquals('TKT-2026-0001', $ticket->ticket_number);
        $this->assertEquals($this->marqueeA->id, $ticket->marquee_id);
        $this->assertEquals('urgent', $ticket->priority);
        $this->assertEquals('open', $ticket->status);
        $this->assertNotNull($ticket->sla_due_at);

        // Verify initial message was created
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->tenantOwnerA->id,
            'is_internal_note' => false,
        ]);

        // Verify status history
        $this->assertDatabaseHas('ticket_status_histories', [
            'ticket_id' => $ticket->id,
            'to_status' => 'open',
        ]);
    }

    public function test_sequential_ticket_number_increments_properly()
    {
        $this->actingAs($this->tenantOwnerA);

        $t1 = SupportTicket::generateTicketNumber();
        SupportTicket::create([
            'ticket_number' => $t1,
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'category_id' => $this->category->id,
            'subject' => 'Ticket One',
            'description' => 'Description one details here.',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        $t2 = SupportTicket::generateTicketNumber();
        $this->assertEquals('TKT-2026-0001', $t1);
        $this->assertEquals('TKT-2026-0002', $t2);
    }

    public function test_tenant_isolation_prevents_viewing_other_tenants_tickets()
    {
        // Ticket belongs to Tenant A
        $ticketA = SupportTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'category_id' => $this->category->id,
            'subject' => 'Tenant A Private Ticket',
            'description' => 'Confidential business inquiries.',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        // Tenant B attempts to access Tenant A's ticket
        $this->actingAs($this->tenantOwnerB);

        // BelongsToTenant prevents it in index
        $response = $this->get(route('support.tickets.index'));
        $response->assertDontSee('Tenant A Private Ticket');

        // Direct show route check throws 403 Forbidden
        $response = $this->get(route('support.tickets.show', $ticketA->ticket_number));
        $response->assertStatus(403);
    }

    public function test_customer_cannot_view_internal_staff_notes()
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'category_id' => $this->category->id,
            'subject' => 'Dispute over booking pricing',
            'description' => 'Customer claims price difference.',
            'priority' => 'normal',
            'status' => 'in_progress',
        ]);

        // Customer message
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->tenantOwnerA->id,
            'message' => 'Please review the quoted rate of PKR 250,000.',
            'is_internal_note' => false,
        ]);

        // Super Admin / Staff internal note
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->superAdmin->id,
            'message' => 'INTERNAL STAFF SECRET: Marquee owner had applied an unauthorized manual discount in logs.',
            'is_internal_note' => true,
        ]);

        $this->actingAs($this->tenantOwnerA);

        $component = Livewire::test(TicketDetail::class, ['ticketNumber' => $ticket->ticket_number]);
        $component->assertSee('Please review the quoted rate');
        $component->assertDontSee('INTERNAL STAFF SECRET');
        $this->assertCount(1, $ticket->customerVisibleMessages);
    }

    public function test_customer_can_reply_to_ticket_and_transition_status()
    {
        $this->actingAs($this->tenantOwnerA);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'category_id' => $this->category->id,
            'subject' => 'Printer setup',
            'description' => 'Need help with thermal receipt printer.',
            'priority' => 'normal',
            'status' => 'awaiting_customer',
        ]);

        Livewire::test(TicketDetail::class, ['ticketNumber' => $ticket->ticket_number])
            ->set('replyMessage', 'I have plugged in the USB cable and turned it on.')
            ->call('sendReply')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertEquals('open', $ticket->status);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'message' => 'I have plugged in the USB cable and turned it on.',
            'is_internal_note' => false,
        ]);
    }

    public function test_customer_can_mark_resolved_and_submit_csat_rating()
    {
        $this->actingAs($this->tenantOwnerA);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'category_id' => $this->category->id,
            'subject' => 'POS setup query',
            'description' => 'Issue is resolved now.',
            'priority' => 'normal',
            'status' => 'in_progress',
        ]);

        Livewire::test(TicketDetail::class, ['ticketNumber' => $ticket->ticket_number])
            ->call('markResolved')
            ->set('rating', 5)
            ->set('feedbackComment', 'Fast and helpful technical support!')
            ->call('submitRating')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertEquals(5, $ticket->satisfaction_rating);
        $this->assertEquals('Fast and helpful technical support!', $ticket->satisfaction_comment);
    }

    public function test_customer_can_reopen_resolved_ticket()
    {
        $this->actingAs($this->tenantOwnerA);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'category_id' => $this->category->id,
            'subject' => 'LED stage lighting issue',
            'description' => 'Flickering during evening event.',
            'priority' => 'normal',
            'status' => 'resolved',
            'resolved_at' => now()->subDay(),
        ]);

        Livewire::test(TicketDetail::class, ['ticketNumber' => $ticket->ticket_number])
            ->set('reopenReason', 'The stage light flickered again today.')
            ->call('reopenTicket')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertEquals('reopened', $ticket->status);
        $this->assertNull($ticket->resolved_at);
    }

    public function test_super_admin_can_view_all_tickets_in_queue_and_filter()
    {
        $this->actingAs($this->superAdmin);

        SupportTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'category_id' => $this->category->id,
            'subject' => 'Ticket from Marquee A',
            'description' => 'Details A.',
            'priority' => 'high',
            'status' => 'open',
        ]);

        SupportTicket::create([
            'ticket_number' => 'TKT-2026-0002',
            'marquee_id' => $this->marqueeB->id,
            'user_id' => $this->tenantOwnerB->id,
            'category_id' => $this->category->id,
            'subject' => 'Ticket from Marquee B',
            'description' => 'Details B.',
            'priority' => 'low',
            'status' => 'open',
        ]);

        $response = $this->get(route('admin.support.tickets'));
        $response->assertStatus(200);
        $response->assertSee('TKT-2026-0001');
        $response->assertSee('TKT-2026-0002');
    }

    public function test_super_admin_can_assign_agent_and_post_internal_note()
    {
        $this->actingAs($this->superAdmin);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'category_id' => $this->category->id,
            'subject' => 'Database timeout',
            'description' => 'Report generates 504 gateway timeout.',
            'priority' => 'high',
            'status' => 'open',
        ]);

        Livewire::test(AdminTicketDetail::class, ['ticketNumber' => $ticket->ticket_number])
            ->set('assignedTo', $this->superAdmin->id)
            ->set('messageType', 'internal')
            ->set('replyMessage', 'Investigating slow queries on booking_addons table.')
            ->call('submitReply')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertEquals($this->superAdmin->id, $ticket->assigned_to);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->superAdmin->id,
            'is_internal_note' => true,
            'message' => 'Investigating slow queries on booking_addons table.',
        ]);
    }

    public function test_attachment_download_authorization()
    {
        Storage::fake('local');

        $path = Storage::disk('local')->put('support/attachments/test.pdf', 'dummy pdf content');

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'category_id' => $this->category->id,
            'subject' => 'Contract dispute',
            'description' => 'Attached contract file.',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->tenantOwnerA->id,
            'message' => 'Here is the contract PDF.',
            'is_internal_note' => false,
        ]);

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'ticket_message_id' => $message->id,
            'file_name' => 'contract.pdf',
            'file_path' => 'support/attachments/test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        // 1. Tenant Owner A (the ticket owner) can download
        $this->actingAs($this->tenantOwnerA);
        $response = $this->get(route('support.tickets.attachments.download', $attachment->id));
        $response->assertStatus(200);

        // 2. Tenant Owner B is denied (403)
        $this->actingAs($this->tenantOwnerB);
        $response = $this->get(route('support.tickets.attachments.download', $attachment->id));
        $response->assertStatus(403);

        // 3. Super Admin can download
        $this->actingAs($this->superAdmin);
        $response = $this->get(route('support.tickets.attachments.download', $attachment->id));
        $response->assertStatus(200);

        // 4. If attachment is on internal staff note, regular tenant owner is denied (403)
        $message->update(['is_internal_note' => true]);
        $this->actingAs($this->tenantOwnerA);
        $response = $this->get(route('support.tickets.attachments.download', $attachment->id));
        $response->assertStatus(403);
    }
}
