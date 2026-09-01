<?php
 
namespace App\Livewire\Inventory;
 
use App\Models\InventoryCategory;
use Livewire\Component;
use Livewire\WithPagination;
 
class CategoryList extends Component
{
    use WithPagination;
 
    public $search = '';
    public $statusFilter = 'all';
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
        'statusFilter' => ['except' => 'all'],
    ];
 
    protected $rules = [
        'name' => 'required|string|max:255',
        'parent_id' => 'nullable|exists:inventory_categories,id',
        'description' => 'nullable|string',
        'status' => 'required|in:Active,Inactive',
    ];
 
    public function mount()
    {
        // View access is controlled by middleware; no explicit gate check needed
    }
 
    public function updatingSearch()
    {
        $this->resetPage();
    }
 
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'), 403);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id)
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'), 403);
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
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'), 403);
        $this->validate();
        $marqueeId = auth()->user()->marquee_id;

        // Prevent duplicate names under same tenant
        $exists = InventoryCategory::where('marquee_id', $marqueeId)
            ->where('name', $this->name)
            ->when($this->editId, function ($q) {
                $q->where('id', '!=', $this->editId);
            })
            ->exists();

        if ($exists) {
            $this->addError('name', 'This category name is already in use.');
            return;
        }

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
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'), 403);
        $this->confirmingDeletionId = $id;
    }

    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'), 403);
        if ($this->confirmingDeletionId) {
            $category = InventoryCategory::findOrFail($this->confirmingDeletionId);

            // Block deletion if category has children sub-categories
            if ($category->children()->exists()) {
                session()->flash('error', 'Cannot delete this category because it has sub-categories.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Block deletion if category has items associated
            if ($category->items()->exists()) {
                session()->flash('error', 'Cannot delete this category because it is referenced by inventory items.');
                $this->confirmingDeletionId = null;
                return;
            }

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

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
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
