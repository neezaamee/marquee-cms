<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Support\FaqManager;
use App\Livewire\SuperAdmin\Support\KbArticleManager;
use App\Livewire\Support\ArticleView;
use App\Livewire\Support\HelpCenterDashboard;
use App\Models\Faq;
use App\Models\KbArticle;
use App\Models\Marquee;
use App\Models\Role;
use App\Models\SupportCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SupportHelpModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupportHelpCenterTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $businessOwner;
    protected $staffUser;
    protected $marquee;
    protected $category;
    protected $faq;
    protected $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SupportHelpModuleSeeder::class);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $ownerRole = Role::where('name', 'business_owner')->first();
        $staffRole = Role::where('name', 'staff')->first();

        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'email' => 'superadmin_support@marquee.com',
        ]);

        $this->marquee = Marquee::factory()->create([
            'name' => 'Royal Palm Grand Ballroom',
            'business_type' => 'marquee',
            'is_setup_completed' => true,
        ]);

        $this->businessOwner = User::factory()->create([
            'role_id' => $ownerRole->id,
            'marquee_id' => $this->marquee->id,
            'email' => 'owner_support@marquee.com',
        ]);
        $this->businessOwner->ownedMarquees()->attach($this->marquee->id);

        $this->staffUser = User::factory()->create([
            'role_id' => $staffRole->id,
            'marquee_id' => $this->marquee->id,
            'email' => 'staff_support@marquee.com',
        ]);

        $this->category = SupportCategory::where('slug', 'getting-started')->first();
        $this->faq = Faq::where('category_id', $this->category->id)->first();
        $this->article = KbArticle::where('category_id', $this->category->id)->first();
    }

    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get(route('support.index'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_tenant_user_can_access_help_center()
    {
        $response = $this->actingAs($this->businessOwner)->get(route('support.index'));
        $response->assertOk()
            ->assertSee('MarqueeCMS Help Center')
            ->assertSee('How can we assist your business today?');
    }

    public function test_help_center_search_filters_articles_and_faqs()
    {
        Livewire::actingAs($this->businessOwner)
            ->test(HelpCenterDashboard::class)
            ->set('searchQuery', 'double-booking')
            ->assertSee('How can I lock and confirm an event slot to prevent double-booking?')
            ->set('searchQuery', 'non_existent_search_query_term_12345')
            ->assertSee('No documentation articles found')
            ->assertSee('No FAQs match your search');
    }

    public function test_help_center_category_filtering()
    {
        $bookingCat = SupportCategory::where('slug', 'booking-management')->first();

        Livewire::actingAs($this->businessOwner)
            ->test(HelpCenterDashboard::class)
            ->call('setCategory', $bookingCat->slug)
            ->assertSet('selectedCategorySlug', $bookingCat->slug)
            ->assertSee('Filtering results for:');
    }

    public function test_voting_helpful_on_faq_increments_counter()
    {
        $initialHelpful = $this->faq->helpful_count;

        Livewire::actingAs($this->businessOwner)
            ->test(HelpCenterDashboard::class)
            ->call('voteFaq', $this->faq->id, true);

        $this->faq->refresh();
        $this->assertEquals($initialHelpful + 1, $this->faq->helpful_count);
    }

    public function test_article_view_increments_views_and_renders_content()
    {
        $initialViews = $this->article->views_count;

        $response = $this->actingAs($this->businessOwner)
            ->get(route('support.article.show', $this->article->slug));

        $response->assertOk()
            ->assertSee($this->article->title)
            ->assertSee('Was this guide helpful to you?');

        $this->article->refresh();
        $this->assertEquals($initialViews + 1, $this->article->views_count);
    }

    public function test_article_view_records_helpful_vote_feedback()
    {
        Livewire::actingAs($this->businessOwner)
            ->test(ArticleView::class, ['slug' => $this->article->slug])
            ->call('submitVote', true)
            ->assertSet('hasVoted', true)
            ->assertSet('userVote', true);

        $this->assertDatabaseHas('kb_article_feedbacks', [
            'article_id' => $this->article->id,
            'user_id' => $this->businessOwner->id,
            'is_helpful' => true,
        ]);
    }

    public function test_tenant_cannot_view_internal_only_articles_directly()
    {
        $internalArticle = KbArticle::create([
            'category_id' => $this->category->id,
            'title' => 'Internal Support Escalation Protocol',
            'slug' => 'internal-support-protocol',
            'content' => '<p>Confidential steps for internal engineering team.</p>',
            'is_internal_only' => true,
            'status' => 'published',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->businessOwner)
            ->get(route('support.article.show', $internalArticle->slug))
            ->assertNotFound();
    }

    public function test_super_admin_can_view_internal_only_articles()
    {
        $internalArticle = KbArticle::create([
            'category_id' => $this->category->id,
            'title' => 'Internal Super Admin Runbook',
            'slug' => 'internal-super-admin-runbook',
            'content' => '<p>Database server maintenance and failover steps.</p>',
            'is_internal_only' => true,
            'status' => 'published',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('support.article.show', $internalArticle->slug))
            ->assertOk()
            ->assertSee('Internal Super Admin Runbook');
    }

    public function test_super_admin_can_manage_faqs()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(FaqManager::class)
            ->set('categoryId', $this->category->id)
            ->set('question', 'How to configure custom hall sound equipment?')
            ->set('answer', '<p>Sound equipment can be assigned as an Extra Service.</p>')
            ->set('isFeatured', true)
            ->set('isPublished', true)
            ->set('displayOrder', 10)
            ->call('saveFaq')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('faqs', [
            'question' => 'How to configure custom hall sound equipment?',
            'is_featured' => true,
            'is_published' => true,
        ]);
    }

    public function test_super_admin_can_manage_kb_articles()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(KbArticleManager::class)
            ->set('categoryId', $this->category->id)
            ->set('title', 'Kitchen Production Management Workflow')
            ->set('slug', 'kitchen-production-management-workflow')
            ->set('summary', 'How kitchen slips synchronize with booking menus.')
            ->set('content', '<p>Kitchen staff view portion sizes and production requirements.</p>')
            ->set('status', 'published')
            ->set('readingTimeMinutes', 3)
            ->call('saveArticle')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kb_articles', [
            'slug' => 'kitchen-production-management-workflow',
            'status' => 'published',
        ]);
    }

    public function test_non_admin_cannot_access_faq_or_kb_management()
    {
        $this->actingAs($this->staffUser)
            ->get(route('admin.support.faqs'))
            ->assertForbidden();

        $this->actingAs($this->staffUser)
            ->get(route('admin.support.kb'))
            ->assertForbidden();
    }
}
