<?php

namespace App\Livewire;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Package;
use App\Services\PackagePricingService;
use Livewire\Component;

class PackageBuilder extends Component
{
    public Package $package;

    // Search and Category Tabs
    public $searchQuery = '';
    public $selectedCategory = '';

    // Selected items array
    public $selectedItems = [];

    // Live Calculations variables
    public $previewGuests = 100;

    public function mount(Package $package)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('edit_packages'), 403);

        if (!auth()->user()->isSuperAdmin() && $package->marquee_id !== auth()->user()->marquee_id) {
            abort(403);
        }

        $this->package = $package;
        $this->previewGuests = $package->minimum_guests ?: 100;
        $this->loadSelectedItems();
    }

    /**
     * Load current items from package pivot relation.
     */
    protected function loadSelectedItems()
    {
        $this->package->load('menuItems.category');
        $this->selectedItems = [];

        foreach ($this->package->menuItems as $item) {
            $this->selectedItems[$item->id] = [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'item_code' => $item->item_code,
                'base_cost' => (float)($item->base_cost ?? 0),
                'selling_price' => (float)$item->selling_price,
                'quantity' => (float)($item->pivot->quantity ?? 1.00),
                'display_order' => (int)($item->pivot->display_order ?? 0),
                'category_name' => $item->category->category_name ?? 'Uncategorized',
            ];
        }

        // Sort items by display order
        uasort($this->selectedItems, fn($a, $b) => $a['display_order'] <=> $b['display_order']);
    }

    /**
     * Add a menu item to the package.
     */
    public function addItem(int $itemId)
    {
        $item = MenuItem::findOrFail($itemId);

        if ($item->marquee_id !== auth()->user()->marquee_id && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        if (array_key_exists($itemId, $this->selectedItems)) {
            return;
        }

        $nextOrder = count($this->selectedItems) + 1;

        $this->package->menuItems()->attach($itemId, [
            'quantity' => 1.00,
            'display_order' => $nextOrder,
        ]);

        $this->loadSelectedItems();
        session()->flash('builder_success', "{$item->item_name} added to package.");
    }

    /**
     * Remove a menu item from the package.
     */
    public function removeItem(int $itemId)
    {
        $this->package->menuItems()->detach($itemId);
        $this->loadSelectedItems();
        $this->reorderSequence();
        session()->flash('builder_success', 'Item removed from package.');
    }

    /**
     * Update item quantity.
     */
    public function updateQuantity(int $itemId, $quantity)
    {
        $qty = (float) $quantity;
        if ($qty <= 0) {
            $qty = 1.00;
        }

        $this->package->menuItems()->updateExistingPivot($itemId, [
            'quantity' => $qty,
        ]);

        $this->loadSelectedItems();
    }

    /**
     * Move item up in the sorting order.
     */
    public function moveUp(int $itemId)
    {
        $items = array_values($this->selectedItems);
        $index = -1;

        for ($i = 0; $i < count($items); $i++) {
            if ($items[$i]['id'] === $itemId) {
                $index = $i;
                break;
            }
        }

        if ($index > 0) {
            // Swap display orders
            $tempOrder = $items[$index]['display_order'];
            $items[$index]['display_order'] = $items[$index - 1]['display_order'];
            $items[$index - 1]['display_order'] = $tempOrder;

            // Update database
            $this->package->menuItems()->updateExistingPivot($items[$index]['id'], ['display_order' => $items[$index]['display_order']]);
            $this->package->menuItems()->updateExistingPivot($items[$index - 1]['id'], ['display_order' => $items[$index - 1]['display_order']]);

            $this->loadSelectedItems();
        }
    }

    /**
     * Move item down in the sorting order.
     */
    public function moveDown(int $itemId)
    {
        $items = array_values($this->selectedItems);
        $index = -1;

        for ($i = 0; $i < count($items); $i++) {
            if ($items[$i]['id'] === $itemId) {
                $index = $i;
                break;
            }
        }

        if ($index >= 0 && $index < count($items) - 1) {
            // Swap display orders
            $tempOrder = $items[$index]['display_order'];
            $items[$index]['display_order'] = $items[$index + 1]['display_order'];
            $items[$index + 1]['display_order'] = $tempOrder;

            // Update database
            $this->package->menuItems()->updateExistingPivot($items[$index]['id'], ['display_order' => $items[$index]['display_order']]);
            $this->package->menuItems()->updateExistingPivot($items[$index + 1]['id'], ['display_order' => $items[$index + 1]['display_order']]);

            $this->loadSelectedItems();
        }
    }

    /**
     * Recalculate display orders to be sequential.
     */
    protected function reorderSequence()
    {
        $order = 1;
        foreach ($this->selectedItems as $itemId => $item) {
            $this->package->menuItems()->updateExistingPivot($itemId, [
                'display_order' => $order++,
            ]);
        }
        $this->loadSelectedItems();
    }

    public function render(PackagePricingService $service)
    {
        $marqueeId = auth()->user()->marquee_id;

        // Load categories for filter tabs
        $categories = MenuCategory::where('status', 'Active')->orderBy('sort_order')->get();

        // Query available items (excluding already selected items)
        $selectedIds = array_keys($this->selectedItems);

        $availableQuery = MenuItem::with('category')
            ->where('status', 'Active')
            ->whereNotIn('id', $selectedIds);

        // Search text
        if (!empty($this->searchQuery)) {
            $availableQuery->where(function ($q) {
                $q->where('item_name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('item_code', 'like', '%' . $this->searchQuery . '%');
            });
        }

        // Category tab filter
        if (!empty($this->selectedCategory)) {
            $availableQuery->where('category_id', $this->selectedCategory);
        }

        $availableItems = $availableQuery->orderBy('item_name')->get();

        // Calculate pricing preview details
        $quoteDetails = $service->calculateQuote($this->package->id, (int)$this->previewGuests);

        return view('livewire.package-builder', compact('categories', 'availableItems', 'quoteDetails'));
    }
}
