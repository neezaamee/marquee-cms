<?php

namespace App\Livewire;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class MenuItemForm extends Component
{
    use WithFileUploads;

    public $isEditMode = false;
    public $itemId = null;

    // Fields
    public $category_id = '';
    public $item_name = '';
    public $urdu_name = '';
    public $item_code = '';
    public $description = '';
    public $unit = 'Per Plate';
    public $base_cost = '';
    public $selling_price = '';
    public $image = null; // New uploaded image
    public $existingImage = null; // Current image path
    public $status = 'Active';

    // Dropdowns
    public $categories = [];

    public function mount($menuItem = null)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission($menuItem ? 'edit_menus' : 'create_menus'), 403);

        $marqueeId = $user->marquee_id;
        $this->categories = MenuCategory::where('status', 'Active')->orderBy('sort_order')->orderBy('category_name')->get();

        if ($menuItem) {
            $this->isEditMode = true;
            $this->itemId = $menuItem->id;
            $this->category_id = $menuItem->category_id;
            $this->item_name = $menuItem->item_name;
            $this->urdu_name = $menuItem->urdu_name ?? '';
            $this->item_code = $menuItem->item_code;
            $this->description = $menuItem->description ?? '';
            $this->unit = $menuItem->unit;
            $this->base_cost = $menuItem->base_cost;
            $this->selling_price = $menuItem->selling_price;
            $this->existingImage = $menuItem->image;
            $this->status = $menuItem->status;
        }
    }

    protected function rules()
    {
        $marqueeId = auth()->user()->marquee_id;

        return [
            'category_id' => [
                'required',
                Rule::exists('menu_categories', 'id')->where('marquee_id', $marqueeId)->whereNull('deleted_at'),
            ],
            'item_name' => 'required|string|max:255',
            'urdu_name' => 'nullable|string|max:255',
            'item_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('menu_items', 'item_code')
                    ->ignore($this->itemId)
                    ->where('marquee_id', $marqueeId)
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'base_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048', // 2MB max
            'status' => 'required|in:Active,Inactive',
        ];
    }

    protected $messages = [
        'item_code.unique' => 'This menu item code is already registered in your Marquee database.',
        'category_id.exists' => 'The selected category is invalid or belongs to another Marquee.',
    ];

    /**
     * Save menu item.
     */
    public function save()
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission($this->isEditMode ? 'edit_menus' : 'create_menus'), 403);

        $validatedData = $this->validate();

        $itemData = [
            'marquee_id' => auth()->user()->marquee_id,
            'category_id' => $this->category_id,
            'item_name' => $this->item_name,
            'urdu_name' => $this->urdu_name ?: null,
            'item_code' => $this->item_code,
            'description' => $this->description,
            'unit' => $this->unit,
            'base_cost' => $this->base_cost ?: null,
            'selling_price' => $this->selling_price,
            'status' => $this->status,
        ];

        // Handle image upload
        if ($this->image) {
            $path = $this->image->store('menu_items', 'public');
            $itemData['image'] = $path;
        } else {
            $itemData['image'] = $this->existingImage;
        }

        if ($this->isEditMode) {
            $item = MenuItem::findOrFail($this->itemId);

            if (!auth()->user()->isSuperAdmin() && $item->marquee_id !== auth()->user()->marquee_id) {
                abort(403, 'Unauthorized.');
            }

            $item->update($itemData);
            session()->flash('success', 'Menu item updated successfully.');
        } else {
            MenuItem::create($itemData);
            session()->flash('success', 'Menu item created successfully.');
        }

        return redirect()->route('menu-items.index');
    }

    public function render()
    {
        return view('livewire.menu-item-form');
    }
}
