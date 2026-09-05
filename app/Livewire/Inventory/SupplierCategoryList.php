<?php

namespace App\Livewire\Inventory;

use App\Models\SupplierCategory;
use App\Services\InventoryService;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierCategoryList extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = 'all';

    public $confirmingDeletionId = null;

    // Form fields
    public $editId = null;

    public $name = '';

    public $code = '';

    public $description = '';

    public $status = 'Active';

    public $sort_order = 0;

    public $showForm = false;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function create(InventoryService $inventoryService)
    {
        $this->authorizeAction();
        $this->resetForm();
        $marqueeId = auth()->user()->getActiveMarqueeId();
        $this->code = $inventoryService->generateNextSupplierCategoryCode($marqueeId);
        $this->showForm = true;
    }

    public function edit(int $id)
    {
        $this->authorizeAction();
        $category = SupplierCategory::findOrFail($id);
        $this->editId = $category->id;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description ?? '';
        $this->status = $category->status;
        $this->sort_order = $category->sort_order ?? 0;
        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->editId = null;
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->status = 'Active';
        $this->sort_order = 0;
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save(InventoryService $inventoryService)
    {
        $this->authorizeAction();
        $marqueeId = auth()->user()->getActiveMarqueeId();

        $this->name = trim($this->name);
        $this->code = strtoupper(trim($this->code));
        $this->description = trim($this->description);

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($marqueeId) {
                    $exists = SupplierCategory::where('marquee_id', $marqueeId)
                        ->where('name', $value)
                        ->when($this->editId, fn ($q) => $q->where('id', '!=', $this->editId))
                        ->exists();
                    if ($exists) {
                        $fail('A supplier category with this name already exists.');
                    }
                },
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($marqueeId) {
                    $exists = SupplierCategory::where('marquee_id', $marqueeId)
                        ->where('code', $value)
                        ->when($this->editId, fn ($q) => $q->where('id', '!=', $this->editId))
                        ->exists();
                    if ($exists) {
                        $fail('A supplier category with this code already exists.');
                    }
                },
            ],
            'status' => 'required|in:Active,Inactive',
            'sort_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        $data = [
            'marquee_id' => $marqueeId,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description ?: null,
            'status' => $this->status,
            'sort_order' => (int) $this->sort_order,
        ];

        if ($this->editId) {
            $category = SupplierCategory::findOrFail($this->editId);
            $category->update($data);
            session()->flash('success', "Supplier category '{$category->name}' updated successfully.");
        } else {
            SupplierCategory::create($data);
            session()->flash('success', 'Supplier category created successfully.');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function toggleStatus(int $id)
    {
        $this->authorizeAction();
        $category = SupplierCategory::findOrFail($id);
        $newStatus = $category->status === 'Active' ? 'Inactive' : 'Active';
        $category->update(['status' => $newStatus]);
        session()->flash('success', "Category '{$category->name}' status set to {$newStatus}.");
    }

    public function confirmDeletion(int $id)
    {
        $this->authorizeAction();
        $this->confirmingDeletionId = $id;
    }

    public function deleteRecord()
    {
        $this->authorizeAction();
        if ($this->confirmingDeletionId) {
            $category = SupplierCategory::withCount('suppliers')->findOrFail($this->confirmingDeletionId);

            // Block deletion if category has active suppliers assigned
            if ($category->suppliers_count > 0) {
                session()->flash('error', "Cannot delete category '{$category->name}' because it is assigned to {$category->suppliers_count} supplier(s). Please unassign or deactivate it instead.");
                $this->confirmingDeletionId = null;

                return;
            }

            $category->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', "Supplier category '{$category->name}' deleted successfully.");
        }
    }

    public function seedDefaults(InventoryService $inventoryService)
    {
        $this->authorizeAction();
        $marqueeId = auth()->user()->getActiveMarqueeId();
        $inventoryService->seedDefaultSupplierCategories($marqueeId);
        session()->flash('success', 'Default procurement supplier categories synchronized successfully.');
        $this->resetPage();
    }

    protected function authorizeAction(): void
    {
        $user = auth()->user();
        abort_unless(
            $user->isSuperAdmin() ||
            $user->isBusinessOwner() ||
            $user->hasPermission('manage_inventory') ||
            $user->hasPermission('manage_supplier_categories') ||
            $user->hasPermission('create_supplier_categories') ||
            $user->hasPermission('edit_supplier_categories'),
            403
        );
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user->getActiveMarqueeId();

        $baseQuery = SupplierCategory::where('marquee_id', $marqueeId);

        // Metrics
        $totalCount = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->where('status', 'Active')->count();
        $inactiveCount = (clone $baseQuery)->where('status', 'Inactive')->count();
        $assignedCount = (clone $baseQuery)->has('suppliers')->count();

        // Filtered query
        $query = (clone $baseQuery)->withCount('suppliers');

        if (! empty($this->search)) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $categories = $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc')->paginate(10);

        return view('livewire.inventory.supplier-category-list', compact(
            'categories',
            'totalCount',
            'activeCount',
            'inactiveCount',
            'assignedCount'
        ))->layout('layouts.admin');
    }
}
