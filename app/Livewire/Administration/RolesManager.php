<?php

namespace App\Livewire\Administration;

use App\Models\Role;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class RolesManager extends Component
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
    public $description = '';

    // Protected built-in roles list
    protected $builtinRoles = [
        'super_admin',
        'business_owner',
        'owner',
        'area_manager',
        'branch_manager',
        'accountant',
        'booking_officer',
        'store_keeper',
        'staff'
    ];

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
        $this->description = '';
        $this->resetErrorBag();
    }

    public function editRole($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $role = Role::findOrFail($id);
        
        // Prevent editing built-in role name for security, label and description can be edited
        $this->selectedId = $role->id;
        $this->name = $role->name;
        $this->label = $role->label;
        $this->description = $role->description;

        $this->isEditMode = true;
        $this->isModalOpen = true;
    }

    public function saveRole()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $isBuiltin = in_array($this->name, $this->builtinRoles);

        $rules = [
            'name' => [
                'required',
                'string',
                'regex:/^[a-z0-9_]+$/',
                'max:50',
                $this->isEditMode ? 'unique:roles,name,' . $this->selectedId : 'unique:roles,name',
            ],
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ];

        $messages = [
            'name.regex' => 'The role name must be lowercase, numbers, and underscores only (e.g. branch_officer).',
        ];

        $this->validate($rules, $messages);

        if ($this->isEditMode && $this->selectedId) {
            $role = Role::findOrFail($this->selectedId);
            
            // Check if name is changing for a built-in role
            if (in_array($role->name, $this->builtinRoles) && $role->name !== $this->name) {
                session()->flash('error', 'Cannot change the system name of a built-in role.');
                return;
            }

            $role->update([
                'name' => $this->name,
                'label' => $this->label,
                'description' => $this->description,
            ]);
            session()->flash('success', 'Role updated successfully.');
        } else {
            Role::create([
                'name' => $this->name,
                'label' => $this->label,
                'description' => $this->description,
            ]);
            session()->flash('success', 'Role created successfully.');
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

    public function deleteRole()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($this->confirmingDeletionId) {
            $role = Role::findOrFail($this->confirmingDeletionId);

            // Prevent deleting built-in roles
            if (in_array($role->name, $this->builtinRoles)) {
                session()->flash('error', 'Cannot delete built-in system roles.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Prevent deleting roles with users
            if (User::where('role_id', $role->id)->exists()) {
                session()->flash('error', 'Cannot delete this role because users are currently assigned to it.');
                $this->confirmingDeletionId = null;
                return;
            }

            $role->delete();
            session()->flash('success', 'Role deleted successfully.');
            $this->confirmingDeletionId = null;
            $this->resetPage();
        }
    }

    public function render()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_staff'), 403);

        $query = Role::query()->withCount('users');

        if (!empty($this->search)) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('label', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        $roles = $query->orderBy('label', 'asc')->paginate(10);

        return view('livewire.administration.roles-manager', [
            'roles' => $roles,
            'builtinRoles' => $this->builtinRoles,
        ])->layout('layouts.admin');
    }
}
