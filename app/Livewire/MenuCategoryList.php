<?php

namespace App\Livewire;

use App\Models\MenuCategory;
use Livewire\Component;
use Livewire\WithPagination;

class MenuCategoryList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public $confirmingDeletionId = null;

    /**
     * Confirm category deletion.
     */
    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    /**
     * Delete Menu Category.
     */
    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('delete_menus'), 403);

        if ($this->confirmingDeletionId) {
            $category = MenuCategory::findOrFail($this->confirmingDeletionId);

            // Scope Check
            if (!auth()->user()->isSuperAdmin() && $category->marquee_id !== auth()->user()->marquee_id) {
                session()->flash('error', 'Unauthorized operation.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Verify if there are items inside category before deleting
            if ($category->menuItems()->count() > 0) {
                session()->flash('error', 'Cannot delete category containing menu items. Reassign or delete those items first.');
                $this->confirmingDeletionId = null;
                return;
            }

            $category->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Menu category deleted successfully.');
        }
    }

    public function render()
    {
        $query = MenuCategory::with('creator');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('category_name', 'like', '%' . $this->search . '%')
                  ->orWhere('category_code', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        $categories = $query->orderBy('sort_order', 'asc')
            ->orderBy('category_name', 'asc')
            ->paginate(15);

        return view('livewire.menu-category-list', compact('categories'));
    }
}
