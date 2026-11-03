<?php

namespace App\Livewire\SuperAdmin\Support;

use App\Models\Faq;
use App\Models\SupportCategory;
use Livewire\Component;
use Livewire\WithPagination;

class FaqManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public string $search = '';
    public string $categoryFilter = '';
    public string $publishedFilter = '';

    // Modal state
    public bool $isModalOpen = false;
    public ?int $editingFaqId = null;

    // Form inputs
    public ?int $categoryId = null;
    public string $question = '';
    public string $answer = '';
    public string $keywords = '';
    public string $tags = '';
    public bool $isFeatured = false;
    public bool $isPublished = true;
    public int $displayOrder = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'publishedFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $user = auth()->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->hasPermission('support.faq.manage'))) {
            abort(403, 'Unauthorized access to FAQ Management.');
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

    public function updatedPublishedFilter()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function editFaq(int $id)
    {
        $faq = Faq::findOrFail($id);
        $this->editingFaqId = $faq->id;
        $this->categoryId = $faq->category_id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->keywords = $faq->keywords ?? '';
        $this->tags = $faq->tags ?? '';
        $this->isFeatured = (bool) $faq->is_featured;
        $this->isPublished = (bool) $faq->is_published;
        $this->displayOrder = (int) $faq->display_order;
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingFaqId = null;
        $this->categoryId = null;
        $this->question = '';
        $this->answer = '';
        $this->keywords = '';
        $this->tags = '';
        $this->isFeatured = false;
        $this->isPublished = true;
        $this->displayOrder = 0;
        $this->resetValidation();
    }

    public function saveFaq()
    {
        $this->validate([
            'categoryId' => 'required|exists:support_categories,id',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'keywords' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:255',
            'displayOrder' => 'required|integer|min:0',
        ]);

        $data = [
            'category_id' => $this->categoryId,
            'question' => $this->question,
            'answer' => $this->answer,
            'keywords' => $this->keywords,
            'tags' => $this->tags,
            'is_featured' => $this->isFeatured,
            'is_published' => $this->isPublished,
            'display_order' => $this->displayOrder,
            'created_by' => auth()->id(),
        ];

        if ($this->editingFaqId) {
            $faq = Faq::findOrFail($this->editingFaqId);
            $faq->update($data);
            session()->flash('success', "FAQ updated successfully.");
        } else {
            Faq::create($data);
            session()->flash('success', "New FAQ created and published.");
        }

        $this->closeModal();
    }

    public function togglePublished(int $id)
    {
        $faq = Faq::findOrFail($id);
        $faq->update(['is_published' => !$faq->is_published]);
        session()->flash('success', "FAQ status updated.");
    }

    public function toggleFeatured(int $id)
    {
        $faq = Faq::findOrFail($id);
        $faq->update(['is_featured' => !$faq->is_featured]);
        session()->flash('success', "FAQ featured flag updated.");
    }

    public function deleteFaq(int $id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();
        session()->flash('success', "FAQ deleted successfully.");
    }

    public function render()
    {
        $categories = SupportCategory::active()->orderBy('name')->get();

        $faqs = Faq::query()
            ->with(['category', 'creator'])
            ->when($this->search, function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('question', 'like', $term)
                        ->orWhere('answer', 'like', $term)
                        ->orWhere('keywords', 'like', $term);
                });
            })
            ->when($this->categoryFilter, fn($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->publishedFilter !== '', fn($q) => $q->where('is_published', $this->publishedFilter === '1'))
            ->orderBy('display_order')
            ->latest('id')
            ->paginate(15);

        return view('livewire.super-admin.support.faq-manager', [
            'faqs' => $faqs,
            'categories' => $categories,
        ])->layout('layouts.admin');
    }
}
