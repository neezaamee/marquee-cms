<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryUnit;
use App\Models\InventoryUnitConversion;
use Livewire\Component;
use Livewire\WithPagination;

class UnitConversionList extends Component
{
    use WithPagination;

    public $search = '';
    public $confirmingDeletionId = null;

    // Form fields
    public $editId = null;
    public $inventory_item_id = '';
    public $from_unit_id = '';
    public $to_unit_id = '';
    public $factor = 1.0000;

    public $showForm = false;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    protected $rules = [
        'inventory_item_id' => 'nullable|exists:inventory_items,id',
        'from_unit_id' => 'required|exists:inventory_units,id',
        'to_unit_id' => 'required|exists:inventory_units,id',
        'factor' => 'required|numeric|min:0.0001',
    ];

    public function mount()
    {
        // View access is controlled by middleware; no explicit gate check needed
    }

    public function updatingSearch()
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
        $conversion = InventoryUnitConversion::findOrFail($id);
        $this->editId = $conversion->id;
        $this->inventory_item_id = $conversion->inventory_item_id ?? '';
        $this->from_unit_id = $conversion->from_unit_id;
        $this->to_unit_id = $conversion->to_unit_id;
        $this->factor = (float)$conversion->factor;
        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->editId = null;
        $this->inventory_item_id = '';
        $this->from_unit_id = '';
        $this->to_unit_id = '';
        $this->factor = 1.0000;
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'), 403);
        $this->validate();
        $marqueeId = auth()->user()->marquee_id;

        // Ensure source and target units are different
        if ($this->from_unit_id == $this->to_unit_id) {
            $this->addError('to_unit_id', 'Source and target units must be different.');
            return;
        }

        $itemId = $this->inventory_item_id ?: null;

        // Prevent duplicate mapping definitions (e.g. unique constraint)
        $exists = InventoryUnitConversion::where('marquee_id', $marqueeId)
            ->where('from_unit_id', $this->from_unit_id)
            ->where('to_unit_id', $this->to_unit_id)
            ->where('inventory_item_id', $itemId)
            ->when($this->editId, function ($q) {
                $q->where('id', '!=', $this->editId);
            })
            ->exists();

        if ($exists) {
            $this->addError('from_unit_id', 'A conversion mapping already exists for these units & item.');
            return;
        }

        $data = [
            'marquee_id' => $marqueeId,
            'inventory_item_id' => $itemId,
            'from_unit_id' => $this->from_unit_id,
            'to_unit_id' => $this->to_unit_id,
            'factor' => $this->factor,
        ];

        if ($this->editId) {
            $conversion = InventoryUnitConversion::findOrFail($this->editId);
            $conversion->update($data);
            session()->flash('success', 'UOM conversion updated successfully.');
        } else {
            InventoryUnitConversion::create($data);
            session()->flash('success', 'UOM conversion created successfully.');
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
            $conversion = InventoryUnitConversion::findOrFail($this->confirmingDeletionId);
            $conversion->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'UOM conversion deleted successfully.');
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        
        $query = InventoryUnitConversion::where('marquee_id', $marqueeId)
            ->with(['fromUnit', 'toUnit', 'inventoryItem']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('fromUnit', function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('short_code', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('toUnit', function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('short_code', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('inventoryItem', function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('item_code', 'like', '%' . $this->search . '%');
                });
            });
        }

        $conversions = $query->latest()->paginate(10);

        // Load active units and items for select dropdowns
        $activeUnits = InventoryUnit::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        $activeItems = InventoryItem::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();

        return view('livewire.inventory.unit-conversion-list', compact('conversions', 'activeUnits', 'activeItems'))
            ->layout('layouts.admin');
    }
}
