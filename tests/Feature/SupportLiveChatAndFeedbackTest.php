<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Support\ChatInbox;
use App\Livewire\SuperAdmin\Support\FeedbackManager;
use App\Livewire\Support\FeatureRequestPortal;
use App\Livewire\Support\FeedbackForm;
use App\Livewire\Support\LiveChat;
use App\Models\ChatMessage;
use App\Models\FeatureRequest;
use App\Models\FeatureRequestVote;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SupportCategory;
use App\Models\SupportConversation;
use App\Models\SupportFeedback;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SupportHelpModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupportLiveChatAndFeedbackTest extends TestCase
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
            'email' => 'superadmin_chat@marquee.com',
        ]);

        $this->marqueeA = Marquee::factory()->create([
            'name' => 'Grand Marquee Alpha',
            'business_type' => 'marquee',
            'is_setup_completed' => true,
        ]);

        $this->tenantOwnerA = User::factory()->create([
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marqueeA->id,
            'email' => 'owner_chat_a@marquee.com',
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
            'email' => 'owner_chat_b@marquee.com',
        ]);
        $this->tenantOwnerB->ownedMarquees()->attach($this->marqueeB->id);

        $this->category = SupportCategory::first();
    }

    public function test_unauthenticated_user_cannot_access_chat_or_feedback()
    {
        $this->get(route('support.chat'))->assertRedirect('/login');
        $this->get(route('support.feedback'))->assertRedirect('/login');
        $this->get(route('support.feature-requests.index'))->assertRedirect('/login');
        $this->get(route('admin.support.chat'))->assertRedirect('/login');
        $this->get(route('admin.support.feedback'))->assertRedirect('/login');
    }

    public function test_tenant_can_start_chat_and_send_messages()
    {
        $this->actingAs($this->tenantOwnerA);

        Livewire::test(LiveChat::class)
            ->set('newSubject', 'Kitchen screen not syncing')
            ->set('newMessage', 'The chef display terminal is not receiving updated menu items.')
            ->call('startNewChat')
            ->assertHasNoErrors();

        $conversation = SupportConversation::where('user_id', $this->tenantOwnerA->id)->first();
        $this->assertNotNull($conversation);
        $this->assertEquals('CHAT-2026-0001', $conversation->conversation_number);
        $this->assertEquals($this->marqueeA->id, $conversation->marquee_id);
        $this->assertEquals('waiting_agent', $conversation->status);

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->tenantOwnerA->id,
            'message' => 'The chef display terminal is not receiving updated menu items.',
        ]);

        // Send second message in same conversation
        Livewire::test(LiveChat::class)
            ->set('activeConversationId', $conversation->id)
            ->set('newMessage', 'Also verified the local Wi-Fi router is working.')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertCount(2, $conversation->messages);
    }

    public function test_chat_messages_marked_as_read_on_refresh()
    {
        $conversation = SupportConversation::create([
            'conversation_number' => 'CHAT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'subject' => 'Printer setup',
            'status' => 'waiting_customer',
            'last_message_at' => now(),
        ]);

        // Staff message that is currently unread by customer
        $staffMsg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->superAdmin->id,
            'message' => 'Please restart the thermal printer service.',
            'is_read' => false,
        ]);

        $this->actingAs($this->tenantOwnerA);

        Livewire::test(LiveChat::class)
            ->set('activeConversationId', $conversation->id)
            ->call('refreshMessages');

        $staffMsg->refresh();
        $this->assertTrue($staffMsg->is_read);
    }

    public function test_tenant_isolation_prevents_viewing_other_tenants_chats()
    {
        $conversationA = SupportConversation::create([
            'conversation_number' => 'CHAT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'subject' => 'Private financial query',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        // Tenant B loads live chat
        $this->actingAs($this->tenantOwnerB);

        $component = Livewire::test(LiveChat::class);
        $component->assertDontSee('CHAT-2026-0001');
        $component->assertDontSee('Private financial query');

        // Tenant B tries to send message into Tenant A's chat
        $component->set('activeConversationId', $conversationA->id)
            ->set('newMessage', 'Trying to spy on tenant A')
            ->call('sendMessage')
            ->assertStatus(403);
    }

    public function test_super_admin_can_view_all_chats_and_reply()
    {
        $conversation = SupportConversation::create([
            'conversation_number' => 'CHAT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'subject' => 'Booking date conflict',
            'status' => 'waiting_agent',
            'last_message_at' => now(),
        ]);

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->tenantOwnerA->id,
            'message' => 'Need help resolving hall booking conflict on Dec 25.',
            'is_read' => false,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ChatInbox::class)
            ->set('activeConversationId', $conversation->id)
            ->set('replyMessage', 'Looking into your reservation logs right now.')
            ->call('sendReply')
            ->assertHasNoErrors();

        $conversation->refresh();
        $this->assertEquals('waiting_customer', $conversation->status);
        $this->assertEquals($this->superAdmin->id, $conversation->assigned_to);

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->superAdmin->id,
            'message' => 'Looking into your reservation logs right now.',
        ]);
    }

    public function test_super_admin_can_convert_chat_to_ticket_with_transcript()
    {
        $conversation = SupportConversation::create([
            'conversation_number' => 'CHAT-2026-0001',
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'subject' => 'Complex API integration',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->tenantOwnerA->id,
            'message' => 'We need custom webhook endpoints for our banking partner.',
            'is_read' => true,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(ChatInbox::class)
            ->set('activeConversationId', $conversation->id)
            ->call('convertToTicket')
            ->assertHasNoErrors()
            ->assertRedirect();

        $conversation->refresh();
        $this->assertEquals('closed', $conversation->status);
        $this->assertNotNull($conversation->converted_ticket_id);

        $ticket = SupportTicket::find($conversation->converted_ticket_id);
        $this->assertNotNull($ticket);
        $this->assertEquals('TKT-2026-0001', $ticket->ticket_number);
        $this->assertEquals($this->marqueeA->id, $ticket->marquee_id);
        $this->assertStringContainsString('CHAT CONVERSATION TRANSCRIPT', $ticket->description);
        $this->assertStringContainsString('We need custom webhook endpoints', $ticket->description);
    }

    public function test_tenant_can_submit_feedback_and_view_in_history()
    {
        $this->actingAs($this->tenantOwnerA);

        Livewire::test(FeedbackForm::class)
            ->set('feedbackType', 'suggestion')
            ->set('subject', 'Enable dark mode theme on POS touch screen')
            ->set('module', 'pos')
            ->set('rating', 5)
            ->set('description', 'Late night staff would benefit greatly from a dark mode UI.')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('support_feedbacks', [
            'user_id' => $this->tenantOwnerA->id,
            'marquee_id' => $this->marqueeA->id,
            'feedback_type' => 'suggestion',
            'subject' => 'Enable dark mode theme on POS touch screen',
            'rating' => 5,
            'status' => 'new',
        ]);
    }

    public function test_super_admin_can_moderate_feedback_and_provide_response()
    {
        $feedback = SupportFeedback::create([
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'feedback_type' => 'ux',
            'subject' => 'Add customer search by CNIC',
            'description' => 'Makes searching returning clients much quicker.',
            'rating' => 4,
            'module' => 'bookings',
            'status' => 'new',
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(FeedbackManager::class)
            ->call('editFeedback', $feedback->id)
            ->set('feedbackStatus', 'planned')
            ->set('feedbackAdminResponse', 'Great suggestion! This is planned for the next release.')
            ->call('saveFeedback')
            ->assertHasNoErrors();

        $feedback->refresh();
        $this->assertEquals('planned', $feedback->status);
        $this->assertEquals('Great suggestion! This is planned for the next release.', $feedback->admin_response);
        $this->assertEquals($this->superAdmin->id, $feedback->reviewed_by);
    }

    public function test_tenant_can_submit_feature_request_and_upvote()
    {
        $this->actingAs($this->tenantOwnerA);

        Livewire::test(FeatureRequestPortal::class)
            ->set('newTitle', 'Export kitchen inventory to Excel')
            ->set('newModule', 'kitchen')
            ->set('newDescription', 'Need weekly spreadsheets for food procurement suppliers.')
            ->call('submitIdea')
            ->assertHasNoErrors();

        $feature = FeatureRequest::first();
        $this->assertNotNull($feature);
        $this->assertEquals('Export kitchen inventory to Excel', $feature->title);
        $this->assertEquals(1, $feature->votes_count);

        $this->assertDatabaseHas('feature_request_votes', [
            'feature_request_id' => $feature->id,
            'user_id' => $this->tenantOwnerA->id,
        ]);
    }

    public function test_vote_deduplication_and_toggle_behavior()
    {
        $feature = FeatureRequest::create([
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'title' => 'Bulk booking price discount engine',
            'description' => 'Allow discounts on multiple wedding days.',
            'module' => 'bookings',
            'status' => 'submitted',
            'votes_count' => 1,
        ]);
        FeatureRequestVote::create([
            'feature_request_id' => $feature->id,
            'user_id' => $this->tenantOwnerA->id,
            'marquee_id' => $this->marqueeA->id,
        ]);

        // Tenant B upvotes the idea
        $this->actingAs($this->tenantOwnerB);

        $portal = Livewire::test(FeatureRequestPortal::class);
        $portal->call('toggleVote', $feature->id)->assertHasNoErrors();

        $feature->refresh();
        $this->assertEquals(2, $feature->votes_count);
        $this->assertTrue($feature->isVotedBy($this->tenantOwnerB->id));

        // Tenant B calls toggleVote again -> should remove vote (deduplicated toggle)
        $portal->call('toggleVote', $feature->id)->assertHasNoErrors();

        $feature->refresh();
        $this->assertEquals(1, $feature->votes_count);
        $this->assertFalse($feature->isVotedBy($this->tenantOwnerB->id));
    }

    public function test_super_admin_can_moderate_feature_request_status_and_roadmap_notes()
    {
        $feature = FeatureRequest::create([
            'marquee_id' => $this->marqueeA->id,
            'user_id' => $this->tenantOwnerA->id,
            'title' => 'Integration with FBR POS invoice verification',
            'description' => 'Realtime QR code printout for tax invoices.',
            'module' => 'pos',
            'status' => 'under_review',
            'votes_count' => 12,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(FeedbackManager::class)
            ->call('editFeature', $feature->id)
            ->set('featureStatus', 'released')
            ->set('featureTargetRelease', 'v1.38.0')
            ->set('featurePublicNotes', 'Now officially released with full FBR API sandbox and production support.')
            ->set('featureAdminNotes', 'Completed via PR #104.')
            ->call('saveFeature')
            ->assertHasNoErrors();

        $feature->refresh();
        $this->assertEquals('released', $feature->status);
        $this->assertEquals('v1.38.0', $feature->target_release);
        $this->assertNotNull($feature->released_at);
        $this->assertEquals('Now officially released with full FBR API sandbox and production support.', $feature->public_notes);
    }
}

