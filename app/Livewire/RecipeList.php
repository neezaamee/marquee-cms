<?php

namespace App\Livewire;

use App\Models\MenuItem;
use App\Models\Recipe;
use App\Models\RecipeDetail;
use App\Models\InventoryItem;
use App\Models\Booking;
use App\Services\RecipeService;
use Livewire\Component;

class RecipeList extends Component
{
    // Calculator States
    public $calcMenuItemId;
    public $calcGuestCount = 100;
    public $calcResults = [];

    // Recipe Manage States
    public $selectedMenuItemId;
    public $recipeDescription;
    public $recipeDetails = []; // index => [inventory_item_id, quantity_per_head]

    protected $rules = [
        'recipeDescription' => 'nullable|string|max:500',
        'recipeDetails.*.inventory_item_id' => 'required|exists:inventory_items,id',
        'recipeDetails.*.quantity_per_head' => 'required|numeric|min:0.0001',
    ];

    public function mount()
    {
        $marqueeId = auth()->user()->marquee_id;
        $firstItem = MenuItem::where('marquee_id', $marqueeId)->first();
        if ($firstItem) {
            $this->selectedMenuItemId = $firstItem->id;
            $this->calcMenuItemId = $firstItem->id;
            $this->loadRecipeForSelected();
        }
        $this->calculateRequirements();
    }

    public function updatedSelectedMenuItemId()
    {
        $this->loadRecipeForSelected();
    }

    public function updatedCalcMenuItemId()
    {
        $this->calculateRequirements();
    }

    public function updatedCalcGuestCount()
    {
        $this->calculateRequirements();
    }

    public function loadRecipeForSelected()
    {
        $this->recipeDetails = [];
        $this->recipeDescription = '';

        if (!$this->selectedMenuItemId) {
            return;
        }

        $recipe = Recipe::where('menu_item_id', $this->selectedMenuItemId)->with('details')->first();
        if ($recipe) {
            $this->recipeDescription = $recipe->description;
            foreach ($recipe->details as $detail) {
                $this->recipeDetails[] = [
                    'inventory_item_id' => $detail->inventory_item_id,
                    'quantity_per_head' => $detail->quantity_per_head,
                ];
            }
        } else {
            // Default with one blank row
            $this->recipeDetails[] = [
                'inventory_item_id' => '',
                'quantity_per_head' => 0.1000,
            ];
        }
    }

    public function addDetailRow()
    {
        $this->recipeDetails[] = [
            'inventory_item_id' => '',
            'quantity_per_head' => 0.1000,
        ];
    }

    public function removeDetailRow($index)
    {
        unset($this->recipeDetails[$index]);
        $this->recipeDetails = array_values($this->recipeDetails);
    }

    public function saveRecipe()
    {
        $this->validate();

        $recipe = Recipe::updateOrCreate(
            ['menu_item_id' => $this->selectedMenuItemId],
            [
                'marquee_id' => auth()->user()->marquee_id,
                'description' => $this->recipeDescription,
            ]
        );

        // Sync details
        $recipe->details()->delete();
        foreach ($this->recipeDetails as $row) {
            RecipeDetail::create([
                'recipe_id' => $recipe->id,
                'inventory_item_id' => $row['inventory_item_id'],
                'quantity_per_head' => $row['quantity_per_head'],
            ]);
        }

        session()->flash('success', 'Recipe for ' . $recipe->menuItem->item_name . ' saved successfully.');
        $this->calculateRequirements();
    }

    public function calculateRequirements()
    {
        $this->calcResults = [];
        if (!$this->calcMenuItemId || $this->calcGuestCount <= 0) {
            return;
        }

        // Mock a booking record to run the service
        $booking = new Booking();
        $booking->guest_count = (int) $this->calcGuestCount;
        
        // Define relation mock
        $menuItem = MenuItem::find($this->calcMenuItemId);
        if ($menuItem) {
            // Service computes based on customized item
            $recipeService = new RecipeService();
            // Since we mocked Booking, we can simulate the service logic directly to avoid writing DB booking
            $recipe = $menuItem->recipe;
            if ($recipe) {
                foreach ($recipe->details as $detail) {
                    $invItem = $detail->inventoryItem;
                    if ($invItem) {
                        $this->calcResults[] = [
                            'name' => $invItem->name,
                            'required_qty' => $detail->quantity_per_head * $this->calcGuestCount,
                            'unit' => $invItem->unit ? $invItem->unit->short_code : 'Pcs',
                        ];
                    }
                }
            }
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $menuItems = MenuItem::where('marquee_id', $marqueeId)->where('status', 'Active')->get();
        $inventoryItems = InventoryItem::where('marquee_id', $marqueeId)->where('status', 'Active')->get();

        return view('livewire.recipe-list', [
            'menuItems' => $menuItems,
            'inventoryItems' => $inventoryItems,
        ])->layout('layouts.admin');
    }
}
