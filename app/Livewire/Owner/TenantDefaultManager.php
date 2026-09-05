<?php

namespace App\Livewire\Owner;

use App\Models\Department;
use App\Models\EventType;
use App\Models\ExpenseCategory;
use App\Models\GlobalDefaultMaster;
use App\Models\InventoryCategory;
use App\Models\InventoryUnit;
use App\Models\MenuCategory;
use Livewire\Component;
use Livewire\WithPagination;

class TenantDefaultManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $activeCategory = 'event_types';
    public $search = '';

    // Form Modal State
    public $isModalOpen = false;
    public $name = '';
    public $code = '';
    public $description = '';

    protected $queryString = [
        'activeCategory' => ['except' => 'event_types'],
        'search' => ['except' => ''],
    ];

    public function updatedSearch() { $this->resetPage(); }

    public function setCategory($cat)
    {
        $this->activeCategory = $cat;
        $this->search = '';
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->resetErrorBag();
    }

    /**
     * Import missing global default templates into the tenant's database tables.
     */
    public function importGlobalDefaults()
    {
        $user = auth()->user();
        if (!$user || !$user->marquee_id) {
            session()->flash('error', 'No active marquee tenant found.');
            return;
        }

        $marqueeId = $user->marquee_id;
        $importedCount = 0;

        // 1. Event Types
        $globalEventTypes = GlobalDefaultMaster::active()->category('event_type')->get();
        foreach ($globalEventTypes as $gt) {
            $exists = EventType::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $marqueeId)->where('event_type_name', $gt->name)->exists();
            if (!$exists) {
                $extra = $gt->extra_attributes ?? [];
                EventType::create([
                    'marquee_id' => $marqueeId,
                    'event_type_name' => $gt->name,
                    'event_type_code' => $gt->code,
                    'description' => $gt->description,
                    'color_code' => $extra['color'] ?? '#3b82f6',
                    'status' => 'active',
                ]);
                $importedCount++;
            }
        }

        // 2. Menu Categories
        $globalMenuCats = GlobalDefaultMaster::active()->category('menu_category')->get();
        foreach ($globalMenuCats as $gt) {
            $exists = MenuCategory::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $marqueeId)->where('category_name', $gt->name)->exists();
            if (!$exists) {
                MenuCategory::create([
                    'marquee_id' => $marqueeId,
                    'category_name' => $gt->name,
                    'category_code' => $gt->code,
                    'description' => $gt->description,
                    'status' => 'Active',
                ]);
                $importedCount++;
            }
        }

        // 3. Inventory Categories
        $globalInvCats = GlobalDefaultMaster::active()->category('inventory_category')->get();
        foreach ($globalInvCats as $gt) {
            $exists = InventoryCategory::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $marqueeId)->where('name', $gt->name)->exists();
            if (!$exists) {
                InventoryCategory::create([
                    'marquee_id' => $marqueeId,
                    'name' => $gt->name,
                    'code' => $gt->code,
                    'description' => $gt->description,
                    'status' => 'Active',
                ]);
                $importedCount++;
            }
        }

        // 4. Inventory Units
        $globalUnits = GlobalDefaultMaster::active()->category('inventory_unit')->get();
        foreach ($globalUnits as $gt) {
            $extra = $gt->extra_attributes ?? [];
            $sCode = $extra['short_code'] ?? $gt->code;
            
            $exists = InventoryUnit::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $marqueeId)
                ->where(function ($query) use ($gt, $sCode) {
                    $query->where('name', $gt->name)
                          ->orWhere('short_code', $sCode);
                })
                ->exists();

            if (!$exists) {
                InventoryUnit::create([
                    'marquee_id' => $marqueeId,
                    'name' => $gt->name,
                    'short_code' => $sCode,
                    'description' => $gt->description,
                    'status' => 'Active',
                ]);
                $importedCount++;
            }
        }

        // 5. Expense Categories
        $globalExpenseCats = GlobalDefaultMaster::active()->category('expense_category')->get();
        foreach ($globalExpenseCats as $gt) {
            $exists = ExpenseCategory::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $marqueeId)->where('name', $gt->name)->exists();
            if (!$exists) {
                ExpenseCategory::create([
                    'marquee_id' => $marqueeId,
                    'name' => $gt->name,
                    'category_code' => $gt->code ?: ('EXP-' . strtoupper(substr(md5($gt->name), 0, 4))),
                    'description' => $gt->description,
                    'is_active' => true,
                ]);
                $importedCount++;
            }
        }

        // 6. Departments
        $branch = \App\Models\Branch::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $marqueeId)->first();
        if ($branch) {
            $globalDepartments = GlobalDefaultMaster::active()->category('department_type')->get();
            foreach ($globalDepartments as $gt) {
                $exists = Department::withoutGlobalScope('tenant')->withTrashed()->where('marquee_id', $marqueeId)->where('name', $gt->name)->exists();
                if (!$exists) {
                    Department::create([
                        'marquee_id' => $marqueeId,
                        'branch_id' => $branch->id,
                        'name' => $gt->name,
                        'department_code' => $gt->code ?: ('DEP-' . strtoupper(substr(md5($gt->name), 0, 4))),
                        'department_type' => 'Operations',
                        'description' => $gt->description,
                        'status' => 'Active',
                    ]);
                    $importedCount++;
                }
            }
        }

        // 7. Seed Default Chart of Accounts & Active Financial Year
        app(\App\Services\AccountingService::class)->seedTenantDefaultAccounts($marqueeId);

        session()->flash('success', "Imported {$importedCount} missing global default records and verified Chart of Accounts for your tenant setup.");
        $this->resetPage();
    }

    public function saveCustomRecord()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $marqueeId = auth()->user()->marquee_id;

        if ($this->activeCategory === 'event_types') {
            EventType::create([
                'marquee_id' => $marqueeId,
                'event_type_name' => $this->name,
                'event_type_code' => $this->code,
                'description' => $this->description,
                'status' => 'active',
            ]);
        } elseif ($this->activeCategory === 'menu_categories') {
            MenuCategory::create([
                'marquee_id' => $marqueeId,
                'category_name' => $this->name,
                'category_code' => $this->code,
                'description' => $this->description,
                'status' => 'Active',
            ]);
        } elseif ($this->activeCategory === 'inventory_categories') {
            InventoryCategory::create([
                'marquee_id' => $marqueeId,
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'status' => 'Active',
            ]);
        } elseif ($this->activeCategory === 'units') {
            InventoryUnit::create([
                'marquee_id' => $marqueeId,
                'name' => $this->name,
                'short_code' => $this->code ?: $this->name,
                'description' => $this->description,
                'status' => 'Active',
            ]);
        } elseif ($this->activeCategory === 'expense_categories') {
            ExpenseCategory::create([
                'marquee_id' => $marqueeId,
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'status' => 'active',
            ]);
        } elseif ($this->activeCategory === 'departments') {
            Department::create([
                'marquee_id' => $marqueeId,
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'status' => 'active',
            ]);
        }

        session()->flash('success', 'Custom master record added to your marquee settings.');
        $this->closeModal();
        $this->resetPage();
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;

        $items = collect();

        if ($this->activeCategory === 'event_types') {
            $q = EventType::where('marquee_id', $marqueeId);
            if (!empty($this->search)) $q->where('event_type_name', 'like', "%{$this->search}%");
            $items = $q->paginate(12);
        } elseif ($this->activeCategory === 'menu_categories') {
            $q = MenuCategory::where('marquee_id', $marqueeId);
            if (!empty($this->search)) $q->where('category_name', 'like', "%{$this->search}%");
            $items = $q->paginate(12);
        } elseif ($this->activeCategory === 'inventory_categories') {
            $q = InventoryCategory::where('marquee_id', $marqueeId);
            if (!empty($this->search)) $q->where('name', 'like', "%{$this->search}%");
            $items = $q->paginate(12);
        } elseif ($this->activeCategory === 'units') {
            $q = InventoryUnit::where('marquee_id', $marqueeId);
            if (!empty($this->search)) $q->where('name', 'like', "%{$this->search}%");
            $items = $q->paginate(12);
        } elseif ($this->activeCategory === 'expense_categories') {
            $q = ExpenseCategory::where('marquee_id', $marqueeId);
            if (!empty($this->search)) $q->where('name', 'like', "%{$this->search}%");
            $items = $q->paginate(12);
        } elseif ($this->activeCategory === 'departments') {
            $q = Department::where('marquee_id', $marqueeId);
            if (!empty($this->search)) $q->where('name', 'like', "%{$this->search}%");
            $items = $q->paginate(12);
        }

        return view('livewire.owner.tenant-default-manager', [
            'items' => $items,
        ])->layout('layouts.admin');
    }
}
