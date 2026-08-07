<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Department;
use App\Models\DepartmentProduction;
use App\Models\DepartmentProductionItem;
use App\Models\Employee;
use App\Models\InventoryItem;
use App\Models\Recipe;
use App\Services\DepartmentStockService;
use App\Services\RecipeService;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentProductionManager extends Component
{
    use WithPagination;

    public $filterDepartment = '';

    // Form fields
    public $department_id;
    public $batch_number;
    public $production_date;
    public $booking_id;
    public $recipe_id;
    public $produced_qty = 1.0;
    public $wastage_qty = 0.0;
    public $prepared_by;
    public $production_time;
    public $notes;

    // Production raw material items: [['item_id' => x, 'quantity' => y]]
    public $formItems = [];

    public $isFormOpen = false;
    public $isViewModalOpen = false;
    public $viewProduction = null;

    protected $paginationTheme = 'bootstrap';

    protected $rules = [
        'department_id' => 'required|exists:departments,id',
        'production_date' => 'required|date',
        'produced_qty' => 'required|numeric|min:0.01',
        'wastage_qty' => 'required|numeric|min:0',
        'prepared_by' => 'nullable|exists:employees,id',
        'notes' => 'nullable|string',
        'formItems' => 'required|array|min:1',
        'formItems.*.item_id' => 'required|exists:inventory_items,id',
        'formItems.*.quantity' => 'required|numeric|min:0.01',
    ];

    protected $messages = [
        'formItems.required' => 'Please add at least one raw material consumed.',
        'formItems.*.item_id.required' => 'Select raw material item.',
        'formItems.*.quantity.required' => 'Specify consumed quantity.',
    ];

    public function mount()
    {
        $this->production_date = now()->format('Y-m-d');

        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        // Auto-select first kitchen department
        $firstKitchen = Department::where('marquee_id', $marqueeId)
            ->where('department_type', 'Kitchen Production');
        if ($branchId) {
            $firstKitchen->where('branch_id', $branchId);
        }
        $firstKitchen = $firstKitchen->first();
        if ($firstKitchen) {
            $this->department_id = $firstKitchen->id;
        }
    }

    public function openCreateForm()
    {
        $this->resetForm();
        $this->batch_number = $this->generateBatchNumber();
        $this->addItemRow();
        $this->isFormOpen = true;
    }

    public function generateBatchNumber(): string
    {
        $marqueeId = auth()->user()->marquee_id;
        $count = DepartmentProduction::where('marquee_id', $marqueeId)->count();
        return 'BATCH-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
    }

    public function resetForm()
    {
        $this->booking_id = null;
        $this->recipe_id = null;
        $this->produced_qty = 1.0;
        $this->wastage_qty = 0.0;
        $this->prepared_by = null;
        $this->production_time = null;
        $this->notes = '';
        $this->formItems = [];
        $this->resetErrorBag();
    }

    public function addItemRow()
    {
        $this->formItems[] = ['item_id' => '', 'quantity' => 1.0];
    }

    public function removeItemRow($index)
    {
        unset($this->formItems[$index]);
        $this->formItems = array_values($this->formItems);
    }

    public function loadRecipeIngredients()
    {
        if (!$this->recipe_id) {
            return;
        }

        $recipeService = app(RecipeService::class);
        $ingredients = $recipeService->getIngredients($this->recipe_id);

        $this->formItems = [];
        foreach ($ingredients as $ingredient) {
            $this->formItems[] = [
                'item_id' => $ingredient['item_id'],
                'quantity' => $ingredient['quantity_per_head'] * $this->produced_qty,
            ];
        }

        if (empty($this->formItems)) {
            $this->addItemRow();
        }
    }

    public function save()
    {
        $this->validate();

        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        if (!$branchId) {
            $this->addError('branch_id', 'Please make sure you are logged in to a branch.');
            return;
        }

        $stockService = app(DepartmentStockService::class);
        $department = Department::findOrFail($this->department_id);

        // Validate department stock for raw materials
        foreach ($this->formItems as $idx => $formItem) {
            $currentBalance = $stockService->getDepartmentStockBalance($department->id, $formItem['item_id']);
            if ((float)$formItem['quantity'] > $currentBalance) {
                $item = InventoryItem::find($formItem['item_id']);
                $this->addError("formItems.{$idx}.quantity",
                    "Insufficient dept stock for {$item->name}. Available: " . number_format($currentBalance, 2));
                return;
            }
        }

        // Create production record
        $production = DepartmentProduction::create([
            'marquee_id' => $marqueeId,
            'branch_id' => $branchId,
            'department_id' => $this->department_id,
            'batch_number' => $this->batch_number,
            'production_date' => $this->production_date,
            'booking_id' => $this->booking_id ?: null,
            'recipe_id' => $this->recipe_id ?: null,
            'produced_qty' => $this->produced_qty,
            'wastage_qty' => $this->wastage_qty,
            'prepared_by' => $this->prepared_by ?: null,
            'approved_by' => auth()->id(),
            'production_time' => $this->production_time,
            'notes' => $this->notes,
            'created_by' => auth()->id(),
        ]);

        // Record production items
        $consumeItems = [];
        foreach ($this->formItems as $formItem) {
            DepartmentProductionItem::create([
                'department_production_id' => $production->id,
                'item_id' => $formItem['item_id'],
                'quantity' => $formItem['quantity'],
            ]);
            $consumeItems[$formItem['item_id']] = $formItem['quantity'];
        }

        // Record wastage consumption from department stock
        if ($this->wastage_qty > 0) {
            $firstItemId = $this->formItems[0]['item_id'] ?? null;
            if ($firstItemId) {
                $stockService->recordConsumption($department, [$firstItemId => $this->wastage_qty], auth()->id(), 'Wastage');
            }
        }

        // Deduct all raw material consumption from department stock ledger
        $stockService->recordConsumption($department, $consumeItems, auth()->id(), 'Consumption');

        session()->flash('message', 'Production batch logged and raw material consumption recorded.');
        $this->isFormOpen = false;
        $this->resetForm();
    }

    public function viewDetails($id)
    {
        $this->viewProduction = DepartmentProduction::with(['department', 'recipe', 'prepStaff', 'booking.customer', 'items.item.unit'])->findOrFail($id);
        $this->isViewModalOpen = true;
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        $query = DepartmentProduction::where('marquee_id', $marqueeId)
            ->with(['department', 'recipe', 'prepStaff']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($this->filterDepartment) {
            $query->where('department_id', $this->filterDepartment);
        }

        $productions = $query->orderBy('id', 'desc')->paginate(10);

        // Only Kitchen Production departments
        $departments = Department::where('marquee_id', $marqueeId)
            ->where('department_type', 'Kitchen Production');
        if ($branchId) {
            $departments->where('branch_id', $branchId);
        }
        $departments = $departments->orderBy('display_order', 'asc')->get();

        $allDepartments = Department::where('marquee_id', $marqueeId);
        if ($branchId) {
            $allDepartments->where('branch_id', $branchId);
        }
        $allDepartments = $allDepartments->orderBy('display_order', 'asc')->get();

        $recipes = Recipe::where('marquee_id', $marqueeId)->get();

        $employees = Employee::where('marquee_id', $marqueeId);
        if ($this->department_id) {
            $employees->where('department_id', $this->department_id);
        }
        if ($branchId) {
            $employees->where('branch_id', $branchId);
        }
        $employees = $employees->orderBy('name', 'asc')->get();

        $inventoryItems = InventoryItem::where('marquee_id', $marqueeId)->where('status', 'Active')->get();

        $bookings = Booking::where('marquee_id', $marqueeId)
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->with('customer')
            ->get();

        return view('livewire.department-production-manager', [
            'productions' => $productions,
            'departments' => $departments,
            'allDepartments' => $allDepartments,
            'recipes' => $recipes,
            'employees' => $employees,
            'inventoryItems' => $inventoryItems,
            'bookings' => $bookings,
        ])->layout('layouts.admin');
    }
}
