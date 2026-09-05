<?php

namespace App\Livewire\Inventory;

use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Services\InventoryService;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = 'all';
    public $confirmingDeletionId = null;

    // Form fields
    public $editId = null;
    public $name = '';
    public $contact_person = '';
    public $mobile_number = '';
    public $whatsapp_number = '';
    public $email = '';
    public $address = '';
    public $city = '';
    public $notes = '';
    public $opening_balance = 0.00;
    public $status = 'Active';
    public $selectedCategories = [];

    public $showForm = false;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => 'all'],
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'contact_person' => 'nullable|string|max:255',
        'mobile_number' => ['required', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
        'whatsapp_number' => ['nullable', 'string', 'regex:/^(03\d{2}-\d{7}|0(21|42)-\d{8}|0[24-9]\d{2}-\d{7,8}|\+?92\d{9,10}|0092\d{9,10}|0[0-9]{9,10})$/'],
        'email' => 'nullable|email|max:255',
        'address' => 'nullable|string',
        'city' => 'nullable|string|max:255',
        'notes' => 'nullable|string',
        'opening_balance' => 'required|numeric|min:0',
        'status' => 'required|in:Active,Inactive',
        'selectedCategories' => 'nullable|array',
        'selectedCategories.*' => 'exists:supplier_categories,id',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id)
    {
        $supplier = Supplier::with('categories')->findOrFail($id);
        $this->editId = $supplier->id;
        $this->name = $supplier->name;
        $this->contact_person = $supplier->contact_person ?? '';
        $this->mobile_number = $supplier->mobile_number;
        $this->whatsapp_number = $supplier->whatsapp_number ?? '';
        $this->email = $supplier->email ?? '';
        $this->address = $supplier->address ?? '';
        $this->city = $supplier->city ?? '';
        $this->notes = $supplier->notes ?? '';
        $this->opening_balance = $supplier->opening_balance;
        $this->status = $supplier->status;
        $this->selectedCategories = $supplier->categories->pluck('id')->toArray();
        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->editId = null;
        $this->name = '';
        $this->contact_person = '';
        $this->mobile_number = '';
        $this->whatsapp_number = '';
        $this->email = '';
        $this->address = '';
        $this->city = '';
        $this->notes = '';
        $this->opening_balance = 0.00;
        $this->status = 'Active';
        $this->selectedCategories = [];
        $this->showForm = false;
        $this->resetErrorBag();
    }

    public function save(InventoryService $inventoryService)
    {
        $this->validate();
        $marqueeId = auth()->user()->getActiveMarqueeId();

        $data = [
            'marquee_id' => $marqueeId,
            'name' => $this->name,
            'contact_person' => $this->contact_person ?: null,
            'mobile_number' => $this->mobile_number,
            'whatsapp_number' => $this->whatsapp_number ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'notes' => $this->notes ?: null,
            'opening_balance' => $this->opening_balance,
            'status' => $this->status,
        ];

        \Illuminate\Support\Facades\DB::transaction(function () use ($inventoryService, $marqueeId, $data) {
            if ($this->editId) {
                $supplier = Supplier::findOrFail($this->editId);
                $supplier->update($data);

                // Synchronize categories
                $supplier->categories()->sync($this->selectedCategories);

                session()->flash('success', 'Supplier profile updated successfully.');
            } else {
                $data['supplier_code'] = $inventoryService->generateNextSupplierCode($marqueeId);
                $supplier = Supplier::create($data);

                // Synchronize categories
                $supplier->categories()->sync($this->selectedCategories);

                // Add ledger entry for opening balance if greater than zero
                if ($this->opening_balance > 0) {
                    $inventoryService->recordSupplierTransaction(
                        $marqueeId,
                        $supplier->id,
                        date('Y-m-d'),
                        0.00, // Debit
                        $this->opening_balance, // Credit
                        'OpeningBalance',
                        $supplier->id,
                        'OP-BAL',
                        'Opening balance payable'
                    );
                }
                session()->flash('success', 'Supplier created successfully.');
            }
        });

        $this->resetForm();
        $this->resetPage();
    }

    public function confirmDeletion(int $id)
    {
        $this->confirmingDeletionId = $id;
    }

    public function deleteRecord()
    {
        if ($this->confirmingDeletionId) {
            $supplier = Supplier::findOrFail($this->confirmingDeletionId);
            $supplier->delete();
            $this->confirmingDeletionId = null;
            session()->flash('success', 'Supplier deleted successfully.');
        }
    }

    public function render()
    {
        $marqueeId = auth()->user()->getActiveMarqueeId();
        $query = Supplier::where('marquee_id', $marqueeId)->with(['categories']);

        if (!empty($this->search)) {
            $cleanDigits = preg_replace('/[^0-9]/', '', $this->search);
            $query->where(function ($q) use ($cleanDigits) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('supplier_code', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_person', 'like', '%' . $this->search . '%');

                if (!empty($cleanDigits)) {
                    $q->orWhere('mobile_number', 'like', '%' . $cleanDigits . '%');
                }
            });
        }

        if ($this->categoryFilter !== 'all' && !empty($this->categoryFilter)) {
            $query->whereHas('categories', function ($q) {
                $q->where('supplier_categories.id', $this->categoryFilter);
            });
        }

        $suppliers = $query->latest()->paginate(10);

        $availableCategories = SupplierCategory::where('marquee_id', $marqueeId)
            ->where('status', 'Active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return view('livewire.inventory.supplier-list', compact('suppliers', 'availableCategories'))
            ->layout('layouts.admin');
    }
}
