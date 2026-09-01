<?php
 
namespace App\Livewire\Inventory;
 
use App\Models\InventoryUnit;
use Livewire\Component;
use Livewire\WithPagination;
 
class UnitList extends Component
{
    use WithPagination;
 
    public $search = '';
    public $statusFilter = 'all';
    public $confirmingDeletionId = null;
 
    // Form fields
    public $editId = null;
    public $name = '';
    public $short_code = '';
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
        'short_code' => 'required|string|max:10',
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
        $unit = InventoryUnit::findOrFail($id);
        $this->editId = $unit->id;
        $this->name = $unit->name;
        $this->short_code = $unit->short_code;
        $this->description = $unit->description ?? '';
        $this->status = $unit->status;
        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->editId = null;
        $this->name = '';
        $this->short_code = '';
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

        // Prevent duplicate name or short code within the same tenant
        $exists = InventoryUnit::where('marquee_id', $marqueeId)
            ->where(function ($q) {
                $q->where('name', $this->name)
                  ->orWhere('short_code', $this->short_code);
            })
            ->when($this->editId, function ($q) {
                $q->where('id', '!=', $this->editId);
            })
            ->exists();

        if ($exists) {
            $this->addError('name', 'This unit name or short code is already in use.');
            return;
        }

        $data = [
            'marquee_id' => $marqueeId,
            'name' => $this->name,
            'short_code' => $this->short_code,
            'description' => $this->description ?: null,
            'status' => $this->status,
        ];

        if ($this->editId) {
            $unit = InventoryUnit::findOrFail($this->editId);
            $unit->update($data);
            session()->flash('success', 'Unit updated successfully.');
        } else {
            InventoryUnit::create($data);
            session()->flash('success', 'Unit created successfully.');
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
            $unit = InventoryUnit::findOrFail($this->confirmingDeletionId);

            // Block deletion if unit is used in items
            if ($unit->items()->exists()) {
                session()->flash('error', 'Cannot delete this unit because it is referenced by inventory items.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Block deletion if unit is used in unit conversions
            $hasConversions = \App\Models\InventoryUnitConversion::where('from_unit_id', $unit->id)
                ->orWhere('to_unit_id', $unit->id)
                ->exists();
            if ($hasConversions) {
                session()->flash('error', 'Cannot delete this unit because it has active unit conversions.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Block deletion if unit is used in recipes
            if (\App\Models\RecipeVersionDetail::where('recipe_unit_id', $unit->id)->exists()) {
                session()->flash('error', 'Cannot delete this unit because it is referenced by recipes.');
                $this->confirmingDeletionId = null;
                return;
            }

            $unit->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Unit deleted successfully.');
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $query = InventoryUnit::where('marquee_id', $marqueeId);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('short_code', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $units = $query->latest()->paginate(10);

        return view('livewire.inventory.unit-list', compact('units'))
            ->layout('layouts.admin');
    }
}
