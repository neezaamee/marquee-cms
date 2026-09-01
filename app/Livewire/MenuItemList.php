<?php

namespace App\Livewire;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Livewire\Component;
use Livewire\WithPagination;

class MenuItemList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterCategory = '';
    public $filterStatus = '';
    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterCategory() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    public $confirmingDeletionId = null;

    /**
     * Confirm item deletion.
     */
    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    /**
     * Delete Menu Item.
     */
    public function deleteRecord()
    {
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('delete_menus'), 403);

        if ($this->confirmingDeletionId) {
            $item = MenuItem::findOrFail($this->confirmingDeletionId);

            // Scope Check
            if (!auth()->user()->isSuperAdmin() && $item->marquee_id !== auth()->user()->marquee_id) {
                session()->flash('error', 'Unauthorized operation.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Block deletion if referenced by packages
            if ($item->packages()->exists()) {
                session()->flash('error', 'Cannot delete this menu item because it is included in one or more packages.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Block deletion if referenced by recipe
            if ($item->recipe()->exists()) {
                session()->flash('error', 'Cannot delete this menu item because an active recipe is linked to it.');
                $this->confirmingDeletionId = null;
                return;
            }

            // Block deletion if referenced in past/active bookings
            if (\App\Models\BookingMenuItem::where('menu_item_id', $item->id)->exists()) {
                session()->flash('error', 'Cannot delete this menu item because it is recorded in existing bookings. Deactivate it instead.');
                $this->confirmingDeletionId = null;
                return;
            }

            $item->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Menu item deleted successfully.');
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        // Fetch categories for filtering dropdown
        $categories = MenuCategory::orderBy('sort_order')->orderBy('category_name')->get();

        // Build Menu Items Query
        $query = MenuItem::with(['category', 'creator']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('item_name', 'like', '%' . $this->search . '%')
                  ->orWhere('item_code', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterCategory)) {
            $query->where('category_id', $this->filterCategory);
        }

        if (!empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        $menuItems = $query->orderBy('item_name', 'asc')
            ->paginate(15);

        return view('livewire.menu-item-list', compact('menuItems', 'categories'));
    }
}
