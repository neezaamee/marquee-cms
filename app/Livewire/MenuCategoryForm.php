<?php

namespace App\Livewire;

use App\Models\MenuCategory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MenuCategoryForm extends Component
{
    public $isEditMode = false;
    public $categoryId = null;

    // Fields
    public $category_name = '';
    public $category_code = '';
    public $description = '';
    public $sort_order = 0;
    public $status = 'Active';

    public function mount($menuCategory = null)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission($menuCategory ? 'edit_menus' : 'create_menus'), 403);

        if ($menuCategory) {
            $this->isEditMode = true;
            $this->categoryId = $menuCategory->id;
            $this->category_name = $menuCategory->category_name;
            $this->category_code = $menuCategory->category_code;
            $this->description = $menuCategory->description ?? '';
            $this->sort_order = $menuCategory->sort_order;
            $this->status = $menuCategory->status;
        }
    }

    protected function rules()
    {
        $marqueeId = auth()->user()->marquee_id;

        return [
            'category_name' => 'required|string|max:255',
            'category_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('menu_categories', 'category_code')
                    ->ignore($this->categoryId)
                    ->where('marquee_id', $marqueeId)
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:Active,Inactive',
        ];
    }

    protected $messages = [
        'category_code.unique' => 'This category code is already registered in your Marquee database.',
    ];

    /**
     * Save menu category.
     */
    public function save()
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission($this->isEditMode ? 'edit_menus' : 'create_menus'), 403);

        $validatedData = $this->validate();

        $categoryData = [
            'marquee_id' => auth()->user()->marquee_id,
            'category_name' => $this->category_name,
            'category_code' => $this->category_code,
            'description' => $this->description,
            'sort_order' => $this->sort_order ?: 0,
            'status' => $this->status,
        ];

        if ($this->isEditMode) {
            $category = MenuCategory::findOrFail($this->categoryId);

            if (!auth()->user()->isSuperAdmin() && $category->marquee_id !== auth()->user()->marquee_id) {
                abort(403, 'Unauthorized.');
            }

            $category->update($categoryData);
            session()->flash('success', 'Menu category updated successfully.');
        } else {
            MenuCategory::create($categoryData);
            session()->flash('success', 'Menu category created successfully.');
        }

        return redirect()->route('menu-categories.index');
    }

    public function render()
    {
        return view('livewire.menu-category-form');
    }
}
