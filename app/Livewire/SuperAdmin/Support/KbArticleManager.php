<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\KbArticle;
use App\Models\SupportCategory;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class KbArticleManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = '';

    // Modal state
    public bool $isModalOpen = false;
    public ?int $editingArticleId = null;

    // Form fields
    public ?int $categoryId = null;
    public string $title = '';
    public string $slug = '';
    public string $summary = '';
    public string $content = '';
    public string $tags = '';
    public bool $isInternalOnly = false;
    public string $status = 'published';
    public int $readingTimeMinutes = 2;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $user = auth()->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->hasPermission('support.kb.manage'))) {
            abort(403, 'Unauthorized access to Knowledge Base Management.');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedTitle()
    {
        if (!$this->editingArticleId && empty($this->slug)) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function editArticle(int $id)
    {
        $article = KbArticle::findOrFail($id);
        $this->editingArticleId = $article->id;
        $this->categoryId = $article->category_id;
        $this->title = $article->title;
        $this->slug = $article->slug;
        $this->summary = $article->summary ?? '';
        $this->content = $article->content;
        $this->tags = $article->tags ?? '';
        $this->isInternalOnly = (bool) $article->is_internal_only;
        $this->status = $article->status;
        $this->readingTimeMinutes = (int) $article->reading_time_minutes;
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingArticleId = null;
        $this->categoryId = null;
        $this->title = '';
        $this->slug = '';
        $this->summary = '';
        $this->content = '';
        $this->tags = '';
        $this->isInternalOnly = false;
        $this->status = 'published';
        $this->readingTimeMinutes = 2;
        $this->resetValidation();
    }

    public function saveArticle()
    {
        $slugRule = 'required|string|max:255|unique:kb_articles,slug';
        if ($this->editingArticleId) {
            $slugRule .= ',' . $this->editingArticleId;
        }

        $this->validate([
            'categoryId' => 'required|exists:support_categories,id',
            'title' => 'required|string|max:255',
            'slug' => $slugRule,
            'summary' => 'nullable|string|max:500',
            'content' => 'required|string',
            'tags' => 'nullable|string|max:255',
            'status' => 'required|in:draft,review,published,archived',
            'readingTimeMinutes' => 'required|integer|min:1|max:60',
        ]);

        $data = [
            'category_id' => $this->categoryId,
            'title' => $this->title,
            'slug' => Str::slug($this->slug),
            'summary' => $this->summary,
            'content' => $this->content,
            'tags' => $this->tags,
            'is_internal_only' => $this->isInternalOnly,
            'status' => $this->status,
            'reading_time_minutes' => $this->readingTimeMinutes,
            'created_by' => auth()->id(),
            'published_at' => ($this->status === 'published') ? now() : null,
        ];

        if ($this->editingArticleId) {
            $article = KbArticle::findOrFail($this->editingArticleId);
            $article->update($data);
            session()->flash('success', "Knowledge base article updated successfully.");
        } else {
            KbArticle::create($data);
            session()->flash('success', "New knowledge base article published.");
        }

        $this->closeModal();
    }

    public function toggleInternal(int $id)
    {
        $article = KbArticle::findOrFail($id);
        $article->update(['is_internal_only' => !$article->is_internal_only]);
        session()->flash('success', "Internal visibility flag updated.");
    }

    public function deleteArticle(int $id)
    {
        $article = KbArticle::findOrFail($id);
        $article->delete();
        session()->flash('success', "Article moved to trash.");
    }

    public function render()
    {
        $categories = SupportCategory::active()->orderBy('name')->get();

        $articles = KbArticle::query()
            ->with(['category', 'creator'])
            ->when($this->search, function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', $term)
                        ->orWhere('summary', 'like', $term)
                        ->orWhere('tags', 'like', $term);
                });
            })
            ->when($this->categoryFilter, fn($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(15);

        return view('livewire.super-admin.support.kb-article-manager', [
            'articles' => $articles,
            'categories' => $categories,
        ])->layout('layouts.admin');
    }
}
