<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryUnit;
use Livewire\Component;
use Livewire\WithPagination;

class UnitList extends Component
{
    use WithPagination;

    public $search = '';
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
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'short_code' => 'required|string|max:10',
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
        $this->validate();
        $marqueeId = auth()->user()->marquee_id;

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
        $this->confirmingDeletionId = $id;
    }

    public function deleteRecord()
    {
        if ($this->confirmingDeletionId) {
            $unit = InventoryUnit::findOrFail($this->confirmingDeletionId);
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

        $units = $query->latest()->paginate(10);

        return view('livewire.inventory.unit-list', compact('units'))
            ->layout('layouts.admin');
    }
}
