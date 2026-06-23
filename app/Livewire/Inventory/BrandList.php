<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryBrand;
use Livewire\Component;
use Livewire\WithPagination;

class BrandList extends Component
{
    use WithPagination;

    public $search = '';
    public $confirmingDeletionId = null;

    // Form fields
    public $editId = null;
    public $name = '';
    public $description = '';
    public $status = 'Active';

    public $showForm = false;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
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
        $brand = InventoryBrand::findOrFail($id);
        $this->editId = $brand->id;
        $this->name = $brand->name;
        $this->description = $brand->description ?? '';
        $this->status = $brand->status;
        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->editId = null;
        $this->name = '';
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
            'description' => $this->description ?: null,
            'status' => $this->status,
        ];

        if ($this->editId) {
            $brand = InventoryBrand::findOrFail($this->editId);
            $brand->update($data);
            session()->flash('success', 'Brand updated successfully.');
        } else {
            InventoryBrand::create($data);
            session()->flash('success', 'Brand created successfully.');
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
            $brand = InventoryBrand::findOrFail($this->confirmingDeletionId);
            $brand->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Brand deleted successfully.');
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $query = InventoryBrand::where('marquee_id', $marqueeId);

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $brands = $query->latest()->paginate(10);

        return view('livewire.inventory.brand-list', compact('brands'))
            ->layout('layouts.admin');
    }
}
