<?php

namespace App\Livewire\Administration;

use App\Models\Permission;
use Livewire\Component;
use Livewire\WithPagination;

class PermissionsManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $confirmingDeletionId = null;

    // Form Modal State
    public $isModalOpen = false;
    public $isEditMode = false;
    public $selectedId = null;

    // Form Fields
    public $name = '';
    public $label = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

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
        $this->label = '';
        $this->resetErrorBag();
    }

    public function editPermission($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $permission = Permission::findOrFail($id);
        
        $this->selectedId = $permission->id;
        $this->name = $permission->name;
        $this->label = $permission->label;

        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function savePermission()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $rules = [
            'name' => [
                'required',
                'string',
                'regex:/^[a-z0-9_\-\.]+$/', // supports dot notation e.g., 'event-types.create' or underscores
                'max:60',
                $this->isEditMode ? 'unique:permissions,name,' . $this->selectedId : 'unique:permissions,name',
            ],
            'label' => 'required|string|max:255',
        ];

        $messages = [
            'name.regex' => 'The permission identifier must be lowercase letters, numbers, dots, dashes, or underscores only (e.g. view_bookings, event-types.edit).',
        ];

        $this->validate($rules, $messages);

        if ($this->isEditMode && $this->selectedId) {
            $permission = Permission::findOrFail($this->selectedId);
            $permission->update([
                'name' => $this->name,
                'label' => $this->label,
            ]);
            session()->flash('success', 'Permission updated successfully.');
        } else {
            Permission::create([
                'name' => $this->name,
                'label' => $this->label,
            ]);
            session()->flash('success', 'Permission created successfully.');
        }

        $this->closeModal();
        $this->resetPage();
    }

    public function confirmDeletion($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $this->confirmingDeletionId = $id;
    }

    public function deletePermission()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($this->confirmingDeletionId) {
            $permission = Permission::findOrFail($this->confirmingDeletionId);
            
            // Delete matches from pivot implicitly through database cascade constraint, 
            // but we can also detach manually just in case
            $permission->roles()->detach();
            $permission->delete();

            session()->flash('success', 'Permission deleted successfully.');
            $this->confirmingDeletionId = null;
            $this->resetPage();
        }
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'), 403);

        $query = Permission::query();

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('label', 'like', $term);
            });
        }

        $permissions = $query->orderBy('label', 'asc')->paginate(15);

        return view('livewire.administration.permissions-manager', [
            'permissions' => $permissions,
        ])->layout('layouts.admin');
    }
}
