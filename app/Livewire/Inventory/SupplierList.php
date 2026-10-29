<?php

namespace App\Livewire\Inventory;

use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\SupplierLedger;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierList extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = 'all';
    public $balanceFilter = 'all';
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
        'balanceFilter' => ['except' => 'all'],
    ];

    public function mount()
    {
        if (request()->has('balanceFilter')) {
            $this->balanceFilter = request()->query('balanceFilter', 'all');
        }
    }

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

    public function updatingBalanceFilter()
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
        $isOwner = auth()->user() && (auth()->user()->isBusinessOwner() || auth()->user()->isSuperAdmin());

        // Authorization check if opening balance is being altered on an existing supplier
        if ($this->editId) {
            $existingSupplier = Supplier::findOrFail($this->editId);
            $oldOpeningBalance = (float) $existingSupplier->opening_balance;
            $newOpeningBalance = (float) $this->opening_balance;

            if (abs($oldOpeningBalance - $newOpeningBalance) > 0.0001 && !$isOwner) {
                $this->addError('opening_balance', 'Only business owners are authorized to modify the opening balance.');
                return;
            }
        }

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

        \Illuminate\Support\Facades\DB::transaction(function () use ($inventoryService, $marqueeId, $data, $isOwner) {
            if ($this->editId) {
                $supplier = Supplier::findOrFail($this->editId);
                $oldOpeningBalance = (float) $supplier->opening_balance;
                $newOpeningBalance = (float) $this->opening_balance;
                $openingBalanceChanged = abs($oldOpeningBalance - $newOpeningBalance) > 0.0001;

                $supplier->update($data);

                // Synchronize categories
                $supplier->categories()->sync($this->selectedCategories);

                if ($openingBalanceChanged && $isOwner) {
                    $openingLedger = SupplierLedger::where('supplier_id', $supplier->id)
                        ->where('reference_type', 'OpeningBalance')
                        ->first();

                    if ($openingLedger) {
                        if ($newOpeningBalance > 0) {
                            $openingLedger->update([
                                'credit' => $newOpeningBalance,
                                'description' => 'Opening balance payable (Adjusted by Owner)',
                                'updated_by' => auth()->id(),
                            ]);
                        } else {
                            $openingLedger->delete();
                        }
                    } elseif ($newOpeningBalance > 0) {
                        $earliestDate = SupplierLedger::where('supplier_id', $supplier->id)->min('transaction_date');
                        $opDate = $earliestDate ? min($earliestDate, date('Y-m-d')) : date('Y-m-d');

                        SupplierLedger::create([
                            'marquee_id' => $marqueeId,
                            'supplier_id' => $supplier->id,
                            'transaction_date' => $opDate,
                            'reference_type' => 'OpeningBalance',
                            'reference_id' => $supplier->id,
                            'voucher_no' => 'OP-BAL',
                            'supplier_invoice_no' => null,
                            'debit' => 0.00,
                            'credit' => $newOpeningBalance,
                            'running_balance' => $newOpeningBalance,
                            'description' => 'Opening balance payable (Added by Owner)',
                            'created_by' => auth()->id(),
                        ]);
                    }

                    // Automatically recalculate supplier ledger balances
                    $inventoryService->rebuildSupplierLedger($supplier->id);

                    session()->flash('success', 'Supplier profile and ledger balances updated successfully.');
                } else {
                    session()->flash('success', 'Supplier profile updated successfully.');
                }
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

    public function getFilteredSuppliersQuery()
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

        if ($this->balanceFilter === 'outstanding') {
            $query->where(function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('supplier_ledgers as sl1')
                        ->whereColumn('sl1.supplier_id', 'suppliers.id')
                        ->whereNull('sl1.deleted_at')
                        ->whereRaw('sl1.id = (SELECT MAX(sl2.id) FROM supplier_ledgers sl2 WHERE sl2.supplier_id = suppliers.id AND sl2.deleted_at IS NULL)')
                        ->where('sl1.running_balance', '>', 0.01);
                })->orWhere(function ($sub) {
                    $sub->whereDoesntHave('ledgers')->where('opening_balance', '>', 0.01);
                });
            });
        }

        return $query;
    }

    public function exportExcel()
    {
        $suppliers = $this->getFilteredSuppliersQuery()->orderBy('name', 'asc')->get();
        $filename = "Supplier_Directory_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream(function () use ($suppliers) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            $marquee = auth()->user()->marquee;
            $marqueeName = $marquee ? $marquee->name : 'Marquee CMS';

            fputcsv($handle, [$marqueeName . ' - SUPPLIER DIRECTORY']);
            fputcsv($handle, ['Exported At', date('Y-m-d H:i:s')]);
            fputcsv($handle, ['Category Filter', $this->categoryFilter === 'all' ? 'All Categories' : (SupplierCategory::find($this->categoryFilter)?->name ?? 'Selected')]);
            fputcsv($handle, ['Balance Filter', $this->balanceFilter === 'outstanding' ? 'Outstanding (> 0)' : 'All Balances']);
            if (!empty($this->search)) {
                fputcsv($handle, ['Search Query', $this->search]);
            }
            fputcsv($handle, ['Total Suppliers', $suppliers->count()]);
            fputcsv($handle, []);

            fputcsv($handle, [
                '#',
                'Supplier Code',
                'Supplier Name',
                'Categories',
                'Contact Person',
                'Mobile Number',
                'WhatsApp Number',
                'Email',
                'City',
                'Address',
                'Status',
                'Opening Balance (Rs.)',
                'Current Outstanding (Rs.)',
                'Notes'
            ]);

            $totalOutstanding = 0.0;
            foreach ($suppliers as $idx => $s) {
                $categoriesStr = $s->categories->pluck('name')->join(', ');
                $balance = (float) $s->current_balance;
                $totalOutstanding += $balance;

                fputcsv($handle, [
                    $idx + 1,
                    $s->supplier_code ?: '—',
                    $s->name,
                    $categoriesStr ?: '—',
                    $s->contact_person ?: '—',
                    $s->mobile_number ?: '—',
                    $s->whatsapp_number ?: '—',
                    $s->email ?: '—',
                    $s->city ?: '—',
                    $s->address ?: '—',
                    $s->status,
                    number_format($s->opening_balance, 2, '.', ''),
                    number_format($balance, 2, '.', ''),
                    $s->notes ?: '—',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', '', '', '', '', '', '', 'TOTAL OUTSTANDING (Rs.)', '', number_format($totalOutstanding, 2, '.', '')]);

            fclose($handle);
        }, 200, $headers);
    }

    public function render()
    {
        $marqueeId = auth()->user()->getActiveMarqueeId();
        $query = $this->getFilteredSuppliersQuery();

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
