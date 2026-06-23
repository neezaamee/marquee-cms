<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryCategory;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryList extends Component
{
    use WithPagination;

    public $search = '';
    public $confirmingDeletionId = null;

    // Form fields
    public $editId = null;
    public $name = '';
    public $parent_id = '';
    public $description = '';
    public $status = 'Active';

    public $showForm = false;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'parent_id' => 'nullable|exists:inventory_categories,id',
        'description' => 'nullable|string',
        'status' => 'required|in:Active,Inactive',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id)
    {
        $category = InventoryCategory::findOrFail($id);
        $this->editId = $category->id;
        $this->name = $category->name;
        $this->parent_id = $category->parent_id ?? '';
        $this->description = $category->description ?? '';
        $this->status = $category->status;
        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->editId = null;
        $this->name = '';
        $this->parent_id = '';
        $this->description = '';
        $this->status = 'Active';
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->validate();
        $marqueeId = auth()->user()->marquee_id;

        $data = [
            'marquee_id' => $marqueeId,
            'name' => $this->name,
            'parent_id' => $this->parent_id ?: null,
            'description' => $this->description ?: null,
            'status' => $this->status,
        ];

        if ($this->editId) {
            $category = InventoryCategory::findOrFail($this->editId);
            $category->update($data);
            session()->flash('success', 'Category updated successfully.');
        } else {
            InventoryCategory::create($data);
            session()->flash('success', 'Category created successfully.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    public function deleteRecord()
    {
        if ($this->confirmingDeletionId) {
            $category = InventoryCategory::findOrFail($this->confirmingDeletionId);
            $category->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Category deleted successfully.');
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $query = InventoryCategory::where('marquee_id', $marqueeId)->with('parent');

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $categories = $query->latest()->paginate(10);
        
        // Parent choices (exclude current category to prevent self-referencing)
        $parentCategories = InventoryCategory::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->when($this->editId, function ($q) {
                $q->where('id', '!=', $this->editId);
            })
            ->orderBy('name')
            ->get();

        return view('livewire.inventory.category-list', compact('categories', 'parentCategories'))
            ->layout('layouts.admin');
    }
}
