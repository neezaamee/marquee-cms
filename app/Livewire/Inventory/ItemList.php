<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryBrand;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Services\InventoryService;
use Livewire\Component;
use Livewire\WithPagination;

class ItemList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterCategory = '';
    public $confirmingDeletionId = null;

    // Form fields
    public $editId = null;
    public $name = '';
    public $category_id = '';
    public $unit_id = '';
    public $brand_id = '';
    public $description = '';
    public $minimum_stock_level = 0.00;
    public $reorder_level = 0.00;
    public $default_purchase_rate = 0.00;
    public $status = 'Active';

    public $showForm = false;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategory' => ['except' => ''],
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'category_id' => 'required|exists:inventory_categories,id',
        'unit_id' => 'required|exists:inventory_units,id',
        'brand_id' => 'nullable|exists:inventory_brands,id',
        'description' => 'nullable|string',
        'minimum_stock_level' => 'required|numeric|min:0',
        'reorder_level' => 'required|numeric|min:0',
        'default_purchase_rate' => 'required|numeric|min:0',
        'status' => 'required|in:Active,Inactive',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterCategory()
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
        $item = InventoryItem::findOrFail($id);
        $this->editId = $item->id;
        $this->name = $item->name;
        $this->category_id = $item->category_id;
        $this->unit_id = $item->unit_id;
        $this->brand_id = $item->brand_id ?? '';
        $this->description = $item->description ?? '';
        $this->minimum_stock_level = $item->minimum_stock_level;
        $this->reorder_level = $item->reorder_level;
        $this->default_purchase_rate = $item->default_purchase_rate;
        $this->status = $item->status;
        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->editId = null;
        $this->name = '';
        $this->category_id = '';
        $this->unit_id = '';
        $this->brand_id = '';
        $this->description = '';
        $this->minimum_stock_level = 0.00;
        $this->reorder_level = 0.00;
        $this->default_purchase_rate = 0.00;
        $this->status = 'Active';
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save(InventoryService $inventoryService)
    {
        $this->validate();
        $marqueeId = auth()->user()->marquee_id;

        $data = [
            'marquee_id' => $marqueeId,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'brand_id' => $this->brand_id ?: null,
            'description' => $this->description ?: null,
            'minimum_stock_level' => $this->minimum_stock_level,
            'reorder_level' => $this->reorder_level,
            'default_purchase_rate' => $this->default_purchase_rate,
            'status' => $this->status,
        ];

        if ($this->editId) {
            $item = InventoryItem::findOrFail($this->editId);
            $item->update($data);
            session()->flash('success', 'Item updated successfully.');
        } else {
            // Generate unique code on creation
            $data['item_code'] = $inventoryService->generateNextItemCode($marqueeId);
            InventoryItem::create($data);
            session()->flash('success', 'Item cataloged successfully.');
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
            $item = InventoryItem::findOrFail($this->confirmingDeletionId);
            $item->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Item deleted successfully.');
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        
        $query = InventoryItem::where('marquee_id', $marqueeId)
            ->with(['category', 'unit', 'brand']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('item_code', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterCategory)) {
            $query->where('category_id', $this->filterCategory);
        }

        $items = $query->latest()->paginate(10);

        // Fetch select options
        $categories = InventoryCategory::where('marquee_id', $marqueeId)->where('status', 'Active')->orderBy('name')->get();
        $units = InventoryUnit::where('marquee_id', $marqueeId)->where('status', 'Active')->orderBy('name')->get();
        $brands = InventoryBrand::where('marquee_id', $marqueeId)->where('status', 'Active')->orderBy('name')->get();

        return view('livewire.inventory.item-list', compact('items', 'categories', 'units', 'brands'))
            ->layout('layouts.admin');
    }
}
