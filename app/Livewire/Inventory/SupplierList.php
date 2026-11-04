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
    public $dateRange = 'all_time'; // 'all_time', 'this_month', 'last_month', 'this_quarter', 'this_year', 'custom'
    public $dateFrom = '';
    public $dateTo = '';
    public $stockFilter = 'all'; // 'all', 'supplied_only'
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
        'dateRange' => ['except' => 'all_time'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'stockFilter' => ['except' => 'all'],
    ];

    public function mount()
    {
        if (request()->has('balanceFilter')) {
            $this->balanceFilter = request()->query('balanceFilter', 'all');
        }
        if (request()->has('dateRange')) {
            $this->dateRange = request()->query('dateRange', 'all_time');
        }
        if (request()->has('dateFrom')) {
            $this->dateFrom = request()->query('dateFrom', '');
        }
        if (request()->has('dateTo')) {
            $this->dateTo = request()->query('dateTo', '');
        }
        if (request()->has('stockFilter')) {
            $this->stockFilter = request()->query('stockFilter', 'all');
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

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingStockFilter()
    {
        $this->resetPage();
    }

    public function setDateRange(string $range)
    {
        $this->dateRange = $range;
        switch ($range) {
            case 'this_month':
                $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
                $this->dateTo = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->dateFrom = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->dateTo = now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_quarter':
                $this->dateFrom = now()->startOfQuarter()->format('Y-m-d');
                $this->dateTo = now()->endOfQuarter()->format('Y-m-d');
                break;
            case 'this_year':
                $this->dateFrom = now()->startOfYear()->format('Y-m-d');
                $this->dateTo = now()->endOfYear()->format('Y-m-d');
                break;
            case 'all_time':
                $this->dateFrom = '';
                $this->dateTo = '';
                break;
            case 'custom':
                if (empty($this->dateFrom)) {
                    $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
                }
                if (empty($this->dateTo)) {
                    $this->dateTo = now()->endOfMonth()->format('Y-m-d');
                }
                break;
        }
        $this->resetPage();
    }

    public function resetAllFilters()
    {
        $this->search = '';
        $this->categoryFilter = 'all';
        $this->balanceFilter = 'all';
        $this->dateRange = 'all_time';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->stockFilter = 'all';
        $this->resetPage();
    }

    public function getPeriodLabel(): string
    {
        if ($this->dateRange === 'all_time' && empty($this->dateFrom) && empty($this->dateTo)) {
            return 'All Time';
        }
        if ($this->dateRange === 'this_month') {
            return 'This Month (' . now()->format('M Y') . ')';
        }
        if ($this->dateRange === 'last_month') {
            return 'Last Month (' . now()->subMonth()->format('M Y') . ')';
        }
        if ($this->dateRange === 'this_quarter') {
            return 'This Quarter';
        }
        if ($this->dateRange === 'this_year') {
            return 'This Year (' . now()->format('Y') . ')';
        }
        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            return \Carbon\Carbon::parse($this->dateFrom)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($this->dateTo)->format('d M Y');
        }
        return 'Filtered Period';
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

        $cutoffDate = $this->dateTo;
        $isPastCutoff = !empty($cutoffDate) && \Carbon\Carbon::parse($cutoffDate)->startOfDay()->lt(\Carbon\Carbon::today());

        if ($this->balanceFilter === 'outstanding') {
            if ($isPastCutoff) {
                $query->where(function ($q) use ($cutoffDate) {
                    $q->whereExists(function ($sub) use ($cutoffDate) {
                        $sub->select(DB::raw(1))
                            ->from('supplier_ledgers as sl1')
                            ->whereColumn('sl1.supplier_id', 'suppliers.id')
                            ->whereNull('sl1.deleted_at')
                            ->whereDate('sl1.transaction_date', '<=', $cutoffDate)
                            ->whereRaw('sl1.id = (SELECT sl2.id FROM supplier_ledgers sl2 WHERE sl2.supplier_id = suppliers.id AND sl2.deleted_at IS NULL AND date(sl2.transaction_date) <= ? ORDER BY sl2.transaction_date DESC, (CASE WHEN sl2.reference_type = "OpeningBalance" THEN 0 ELSE 1 END) DESC, sl2.id DESC LIMIT 1)', [$cutoffDate])
                            ->where('sl1.running_balance', '>', 0.01);
                    })->orWhere(function ($sub) use ($cutoffDate) {
                        $sub->whereDoesntHave('ledgers', function ($lq) use ($cutoffDate) {
                            $lq->whereDate('transaction_date', '<=', $cutoffDate);
                        })->where('opening_balance', '>', 0.01);
                    });
                });
            } else {
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
        }

        if ($this->stockFilter === 'supplied_only') {
            $query->whereExists(function ($sub) use ($marqueeId) {
                $sub->select(DB::raw(1))
                    ->from('purchase_invoices')
                    ->whereColumn('purchase_invoices.supplier_id', 'suppliers.id')
                    ->where('purchase_invoices.marquee_id', $marqueeId)
                    ->where('purchase_invoices.status', '!=', 'Cancelled');
                if (!empty($this->dateFrom)) {
                    $sub->whereDate('purchase_invoices.purchase_date', '>=', $this->dateFrom);
                }
                if (!empty($this->dateTo)) {
                    $sub->whereDate('purchase_invoices.purchase_date', '<=', $this->dateTo);
                }
            });
        }

        return $query;
    }

    public function exportExcel()
    {
        $marqueeId = auth()->user()->getActiveMarqueeId();
        $suppliers = $this->getFilteredSuppliersQuery()->orderBy('name', 'asc')->get();
        $filename = "Supplier_Directory_Stock_Report_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $supplierIds = $suppliers->pluck('id')->toArray();

        $purchasesBySupplier = DB::table('purchase_invoices')
            ->where('marquee_id', $marqueeId)
            ->where('status', '!=', 'Cancelled')
            ->whereIn('supplier_id', $supplierIds)
            ->when($this->dateFrom, fn($q) => $q->whereDate('purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('purchase_date', '<=', $this->dateTo))
            ->select(
                'supplier_id',
                DB::raw('COUNT(id) as invoice_count'),
                DB::raw('COALESCE(SUM(net_amount), 0) as total_purchases')
            )
            ->groupBy('supplier_id')
            ->get()
            ->keyBy('supplier_id');

        $returnsBySupplier = DB::table('purchase_returns')
            ->where('marquee_id', $marqueeId)
            ->where('status', '!=', 'Cancelled')
            ->whereIn('supplier_id', $supplierIds)
            ->when($this->dateFrom, fn($q) => $q->whereDate('return_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('return_date', '<=', $this->dateTo))
            ->select(
                'supplier_id',
                DB::raw('COALESCE(SUM(net_amount), 0) as total_returns')
            )
            ->groupBy('supplier_id')
            ->get()
            ->keyBy('supplier_id');

        $cutoffDate = $this->dateTo;
        $isPastCutoff = !empty($cutoffDate) && \Carbon\Carbon::parse($cutoffDate)->startOfDay()->lt(\Carbon\Carbon::today());

        $allLastLedgers = $isPastCutoff ? SupplierLedger::whereIn('supplier_id', $supplierIds)
            ->whereDate('transaction_date', '<=', $cutoffDate)
            ->orderBy('transaction_date', 'desc')
            ->orderByRaw("CASE WHEN reference_type = 'OpeningBalance' THEN 0 ELSE 1 END DESC")
            ->orderBy('id', 'desc')
            ->get()
            ->unique('supplier_id')
            ->keyBy('supplier_id') : collect();

        $periodLabel = $this->getPeriodLabel();

        return response()->stream(function () use ($suppliers, $purchasesBySupplier, $returnsBySupplier, $periodLabel, $isPastCutoff, $allLastLedgers) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            $marquee = auth()->user()->marquee;
            $marqueeName = $marquee ? $marquee->name : 'Marquee CMS';

            fputcsv($handle, [$marqueeName . ' - SUPPLIER STOCK & PROCUREMENT REPORT']);
            fputcsv($handle, ['Exported At', date('Y-m-d H:i:s')]);
            fputcsv($handle, ['Period Filter', $periodLabel]);
            fputcsv($handle, ['Category Filter', $this->categoryFilter === 'all' ? 'All Categories' : (SupplierCategory::find($this->categoryFilter)?->name ?? 'Selected')]);
            fputcsv($handle, ['Balance Filter', $this->balanceFilter === 'outstanding' ? 'Outstanding (> 0)' : 'All Balances']);
            fputcsv($handle, ['Stock Activity Filter', $this->stockFilter === 'supplied_only' ? 'Supplied Stock Only (> 0)' : 'All Suppliers']);
            if (!empty($this->search)) {
                fputcsv($handle, ['Search Query', $this->search]);
            }
            fputcsv($handle, ['Total Suppliers Listed', $suppliers->count()]);
            fputcsv($handle, []);

            fputcsv($handle, [
                '#',
                'Supplier Code',
                'Supplier Name',
                'Categories',
                'Contact Person',
                'Mobile Number',
                'City',
                'Status',
                'Bills Count',
                'Stock Received / Purchases (Rs.)',
                'Opening Balance (Rs.)',
                $isPastCutoff ? 'Closing Payables (Rs.)' : 'Current Outstanding (Rs.)',
                'Notes'
            ]);

            $totalStockReceived = 0.0;
            $totalOutstanding = 0.0;
            $suppliersWithStockCount = 0;

            foreach ($suppliers as $idx => $s) {
                $categoriesStr = $s->categories->pluck('name')->join(', ');
                $lastL = $isPastCutoff ? $allLastLedgers->get($s->id) : null;
                $balance = $isPastCutoff
                    ? ($lastL ? (float) $lastL->running_balance : (float) $s->opening_balance)
                    : (float) $s->current_balance;
                $totalOutstanding += $balance;

                $invData = $purchasesBySupplier->get($s->id);
                $retData = $returnsBySupplier->get($s->id);
                $gross = $invData ? (float) $invData->total_purchases : 0.0;
                $ret = $retData ? (float) $retData->total_returns : 0.0;
                $stockReceived = max(0.0, $gross - $ret);
                $billCount = $invData ? (int) $invData->invoice_count : 0;

                $totalStockReceived += $stockReceived;
                if ($stockReceived > 0) {
                    $suppliersWithStockCount++;
                }

                fputcsv($handle, [
                    $idx + 1,
                    $s->supplier_code ?: '—',
                    $s->name,
                    $categoriesStr ?: '—',
                    $s->contact_person ?: '—',
                    $s->mobile_number ?: '—',
                    $s->city ?: '—',
                    $s->status,
                    $billCount,
                    number_format($stockReceived, 2, '.', ''),
                    number_format($s->opening_balance, 2, '.', ''),
                    number_format($balance, 2, '.', ''),
                    $s->notes ?: '—',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                '', '', '', '', '', '', '',
                'TOTALS:',
                "Supplied: {$suppliersWithStockCount} suppliers",
                number_format($totalStockReceived, 2, '.', ''),
                '',
                number_format($totalOutstanding, 2, '.', '')
            ]);

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

        // 1. Calculate stock received and bill counts for current page suppliers
        $supplierIds = $suppliers->pluck('id')->toArray();

        $purchasesBySupplier = DB::table('purchase_invoices')
            ->where('marquee_id', $marqueeId)
            ->where('status', '!=', 'Cancelled')
            ->whereIn('supplier_id', $supplierIds)
            ->when($this->dateFrom, fn($q) => $q->whereDate('purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('purchase_date', '<=', $this->dateTo))
            ->select(
                'supplier_id',
                DB::raw('COUNT(id) as invoice_count'),
                DB::raw('COALESCE(SUM(net_amount), 0) as total_purchases')
            )
            ->groupBy('supplier_id')
            ->get()
            ->keyBy('supplier_id');

        $returnsBySupplier = DB::table('purchase_returns')
            ->where('marquee_id', $marqueeId)
            ->where('status', '!=', 'Cancelled')
            ->whereIn('supplier_id', $supplierIds)
            ->when($this->dateFrom, fn($q) => $q->whereDate('return_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('return_date', '<=', $this->dateTo))
            ->select(
                'supplier_id',
                DB::raw('COALESCE(SUM(net_amount), 0) as total_returns')
            )
            ->groupBy('supplier_id')
            ->get()
            ->keyBy('supplier_id');

        $cutoffDate = $this->dateTo;
        $isPastCutoff = !empty($cutoffDate) && \Carbon\Carbon::parse($cutoffDate)->startOfDay()->lt(\Carbon\Carbon::today());

        if ($isPastCutoff) {
            $lastLedgers = SupplierLedger::whereIn('supplier_id', $supplierIds)
                ->whereDate('transaction_date', '<=', $cutoffDate)
                ->orderBy('transaction_date', 'desc')
                ->orderByRaw("CASE WHEN reference_type = 'OpeningBalance' THEN 0 ELSE 1 END DESC")
                ->orderBy('id', 'desc')
                ->get()
                ->unique('supplier_id')
                ->keyBy('supplier_id');

            foreach ($suppliers as $supp) {
                $invData = $purchasesBySupplier->get($supp->id);
                $retData = $returnsBySupplier->get($supp->id);
                $gross = $invData ? (float) $invData->total_purchases : 0.0;
                $ret = $retData ? (float) $retData->total_returns : 0.0;

                $supp->period_stock_received = max(0.0, $gross - $ret);
                $supp->period_invoice_count = $invData ? (int) $invData->invoice_count : 0;
                $supp->period_returns = $ret;

                $lastL = $lastLedgers->get($supp->id);
                $supp->closing_balance = $lastL ? (float) $lastL->running_balance : (float) $supp->opening_balance;
            }
        } else {
            foreach ($suppliers as $supp) {
                $invData = $purchasesBySupplier->get($supp->id);
                $retData = $returnsBySupplier->get($supp->id);
                $gross = $invData ? (float) $invData->total_purchases : 0.0;
                $ret = $retData ? (float) $retData->total_returns : 0.0;

                $supp->period_stock_received = max(0.0, $gross - $ret);
                $supp->period_invoice_count = $invData ? (int) $invData->invoice_count : 0;
                $supp->period_returns = $ret;
                $supp->closing_balance = (float) $supp->current_balance;
            }
        }

        // 2. Calculate overall totals across ALL filtered suppliers (not just page)
        $filteredSupplierIds = (clone $this->getFilteredSuppliersQuery())->pluck('id')->toArray();

        $overallPurchases = (float) DB::table('purchase_invoices')
            ->where('marquee_id', $marqueeId)
            ->where('status', '!=', 'Cancelled')
            ->whereIn('supplier_id', $filteredSupplierIds)
            ->when($this->dateFrom, fn($q) => $q->whereDate('purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('purchase_date', '<=', $this->dateTo))
            ->sum('net_amount');

        $overallReturns = (float) DB::table('purchase_returns')
            ->where('marquee_id', $marqueeId)
            ->where('status', '!=', 'Cancelled')
            ->whereIn('supplier_id', $filteredSupplierIds)
            ->when($this->dateFrom, fn($q) => $q->whereDate('return_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('return_date', '<=', $this->dateTo))
            ->sum('net_amount');

        $totalStockValueOverall = max(0.0, $overallPurchases - $overallReturns);

        $suppliersWithStockCount = DB::table('purchase_invoices')
            ->where('marquee_id', $marqueeId)
            ->where('status', '!=', 'Cancelled')
            ->whereIn('supplier_id', $filteredSupplierIds)
            ->when($this->dateFrom, fn($q) => $q->whereDate('purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('purchase_date', '<=', $this->dateTo))
            ->distinct()
            ->count('supplier_id');

        if ($isPastCutoff) {
            $filteredSuppliers = Supplier::whereIn('id', $filteredSupplierIds)->get();
            $allLastLedgers = SupplierLedger::whereIn('supplier_id', $filteredSupplierIds)
                ->whereDate('transaction_date', '<=', $cutoffDate)
                ->orderBy('transaction_date', 'desc')
                ->orderByRaw("CASE WHEN reference_type = 'OpeningBalance' THEN 0 ELSE 1 END DESC")
                ->orderBy('id', 'desc')
                ->get()
                ->unique('supplier_id')
                ->keyBy('supplier_id');

            $totalOutstandingOverall = (float) $filteredSuppliers->sum(function ($supp) use ($allLastLedgers) {
                $lastLedger = $allLastLedgers->get($supp->id);
                return $lastLedger ? (float) $lastLedger->running_balance : (float) $supp->opening_balance;
            });
        } else {
            $totalOutstandingOverall = (float) Supplier::whereIn('id', $filteredSupplierIds)->get()->sum('current_balance');
        }
        $periodLabel = $this->getPeriodLabel();

        return view('livewire.inventory.supplier-list', compact(
            'suppliers',
            'availableCategories',
            'totalStockValueOverall',
            'suppliersWithStockCount',
            'totalOutstandingOverall',
            'periodLabel',
            'isPastCutoff',
            'cutoffDate'
        ))->layout('layouts.admin');
    }
}
