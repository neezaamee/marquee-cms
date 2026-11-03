<?php

namespace App\Livewire\Support;

use App\Models\Faq;
use App\Models\KbArticle;
use App\Models\SupportCategory;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;

class HelpCenterDashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Search and Filtering State
    public string $searchQuery = '';
    public string $selectedCategorySlug = '';
    public string $activeTab = 'all'; // 'all', 'articles', 'faqs'

    // FAQ Votes Tracker (to prevent duplicate clicks in same session)
    public array $votedFaqIds = [];

    protected $queryString = [
        'searchQuery' => ['except' => ''],
        'selectedCategorySlug' => ['except' => ''],
        'activeTab' => ['except' => 'all'],
    ];

    public function mount()
    {
        $this->votedFaqIds = Session::get('support_voted_faqs', []);
    }

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    public function setCategory(string $slug)
    {
        $this->selectedCategorySlug = ($this->selectedCategorySlug === $slug) ? '' : $slug;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->searchQuery = '';
        $this->selectedCategorySlug = '';
        $this->activeTab = 'all';
        $this->resetPage();
    }

    /**
     * Submit helpfulness vote for an FAQ.
     */
    public function voteFaq(int $faqId, bool $isHelpful)
    {
        if (in_array($faqId, $this->votedFaqIds)) {
            session()->flash('faq_message_' . $faqId, 'You have already voted on this FAQ.');
            return;
        }

        $faq = Faq::find($faqId);
        if ($faq) {
            if ($isHelpful) {
                $faq->voteHelpful();
            } else {
                $faq->voteUnhelpful();
            }

            $this->votedFaqIds[] = $faqId;
            Session::put('support_voted_faqs', $this->votedFaqIds);
            session()->flash('faq_message_' . $faqId, 'Thank you for your feedback!');
        }
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        // Fetch categories with active articles/faqs counts
        $categories = SupportCategory::active()
            ->withCount(['faqs' => fn($q) => $q->published()])
            ->withCount(['articles' => fn($q) => $isSuperAdmin ? $q->published() : $q->publicForTenants()])
            ->get();

        // Selected category model if filtered
        $activeCategory = null;
        if ($this->selectedCategorySlug) {
            $activeCategory = $categories->firstWhere('slug', $this->selectedCategorySlug);
        }

        // Query Knowledge Base Articles
        $articlesQuery = KbArticle::query();
        if ($isSuperAdmin) {
            $articlesQuery->published();
        } else {
            $articlesQuery->publicForTenants();
        }

        if ($activeCategory) {
            $articlesQuery->where('category_id', $activeCategory->id);
        }

        if (trim($this->searchQuery) !== '') {
            $term = '%' . trim($this->searchQuery) . '%';
            $articlesQuery->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('summary', 'like', $term)
                  ->orWhere('content', 'like', $term)
                  ->orWhere('tags', 'like', $term);
            });
        }

        $articles = $articlesQuery->with('category')->latest('published_at')->paginate(6, ['*'], 'articlesPage');

        // Query FAQs
        $faqsQuery = Faq::published();
        if ($activeCategory) {
            $faqsQuery->where('category_id', $activeCategory->id);
        }

        if (trim($this->searchQuery) !== '') {
            $term = '%' . trim($this->searchQuery) . '%';
            $faqsQuery->where(function ($q) use ($term) {
                $q->where('question', 'like', $term)
                  ->orWhere('answer', 'like', $term)
                  ->orWhere('keywords', 'like', $term)
                  ->orWhere('tags', 'like', $term);
            });
        }

        $faqs = $faqsQuery->with('category')->get();

        // Featured popular FAQs
        $featuredFaqs = Faq::published()->featured()->with('category')->take(5)->get();

        return view('livewire.support.help-center-dashboard', [
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'articles' => $articles,
            'faqs' => $faqs,
            'featuredFaqs' => $featuredFaqs,
        ])->layout('layouts.admin');
    }
}
