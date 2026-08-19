<?php

namespace App\Livewire\SuperAdmin;

use App\Models\GlobalDefaultMaster;
use Livewire\Component;
use Livewire\WithPagination;

class GlobalDefaultManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Active Category & Filters
    public $activeCategory = 'event_type';
    public $search = '';
    public $filterStatus = '';

    // Form Modal State
    public $isModalOpen = false;
    public $isEditMode = false;
    public $selectedId = null;

    // Form Fields
    public $name = '';
    public $code = '';
    public $description = '';
    public $short_code = '';
    public $color_code = '#3b82f6';
    public $is_active = true;

    protected $queryString = [
        'activeCategory' => ['except' => 'event_type'],
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }

    public function setCategory($category)
    {
        $this->activeCategory = $category;
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
        $this->closeModal();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->selectedId = null;
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->short_code = '';
        $this->color_code = '#3b82f6';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function editMaster($id)
    {
        $master = GlobalDefaultMaster::findOrFail($id);
        $this->selectedId = $master->id;
        $this->activeCategory = $master->category_type;
        $this->name = $master->name;
        $this->code = $master->code;
        $this->description = $master->description;
        $this->is_active = (bool) $master->is_active;

        $extra = $master->extra_attributes ?? [];
        $this->short_code = $extra['short_code'] ?? '';
        $this->color_code = $extra['color'] ?? '#3b82f6';

        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function saveMaster()
    {
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            session()->flash('error', 'Unauthorized access.');
            return;
        }

        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ];

        $this->validate($rules);

        $extraAttributes = [];
        if ($this->activeCategory === 'inventory_unit' && !empty($this->short_code)) {
            $extraAttributes['short_code'] = $this->short_code;
        }
        if ($this->activeCategory === 'event_type' && !empty($this->color_code)) {
            $extraAttributes['color'] = $this->color_code;
        }

        if ($this->isEditMode && $this->selectedId) {
            $master = GlobalDefaultMaster::findOrFail($this->selectedId);
            $master->update([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'extra_attributes' => $extraAttributes,
                'is_active' => $this->is_active,
            ]);
            session()->flash('success', 'Global master default updated successfully.');
        } else {
            GlobalDefaultMaster::create([
                'category_type' => $this->activeCategory,
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'extra_attributes' => $extraAttributes,
                'is_active' => $this->is_active,
                'created_by' => auth()->id(),
            ]);
            session()->flash('success', 'New global master default created successfully.');
        }

        $this->closeModal();
        $this->resetPage();
    }

    public function toggleStatus($id)
    {
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            session()->flash('error', 'Unauthorized access.');
            return;
        }

        $master = GlobalDefaultMaster::findOrFail($id);
        $master->update([
            'is_active' => !$master->is_active
        ]);

        session()->flash('success', 'Master record status updated.');
    }

    public function deleteMaster($id)
    {
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            session()->flash('error', 'Unauthorized access.');
            return;
        }

        $master = GlobalDefaultMaster::findOrFail($id);
        $master->delete();

        session()->flash('success', 'Global master default deleted.');
        $this->resetPage();
    }

    public function render()
    {
        // Metric counts across categories
        $metrics = [
            'total' => GlobalDefaultMaster::count(),
            'active' => GlobalDefaultMaster::where('is_active', true)->count(),
            'event_type' => GlobalDefaultMaster::where('category_type', 'event_type')->count(),
            'menu_category' => GlobalDefaultMaster::where('category_type', 'menu_category')->count(),
            'inventory_category' => GlobalDefaultMaster::where('category_type', 'inventory_category')->count(),
            'inventory_unit' => GlobalDefaultMaster::where('category_type', 'inventory_unit')->count(),
            'expense_category' => GlobalDefaultMaster::where('category_type', 'expense_category')->count(),
            'department_type' => GlobalDefaultMaster::where('category_type', 'department_type')->count(),
            'vendor_type' => GlobalDefaultMaster::where('category_type', 'vendor_type')->count(),
            'customer_type' => GlobalDefaultMaster::where('category_type', 'customer_type')->count(),
            'payment_method' => GlobalDefaultMaster::where('category_type', 'payment_method')->count(),
        ];

        $query = GlobalDefaultMaster::where('category_type', $this->activeCategory);

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', (bool)$this->filterStatus);
        }

        $masters = $query->orderBy('name', 'asc')->paginate(12);

        return view('livewire.super-admin.global-default-manager', [
            'masters' => $masters,
            'metrics' => $metrics,
        ])->layout('layouts.admin');
    }
}
