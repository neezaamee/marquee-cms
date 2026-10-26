<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Services\AccountingService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class GeneralLedger extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $isSaas = false;

    public $account_id = '';
    public $financial_year_id = '';
    public $branch_id = '';
    public $startDate = '';
    public $endDate = '';

    // Search within entries & pagination size
    public $entrySearch = '';
    public $perPage = 25;

    public $ledgerData = null;

    protected $queryString = [
        'account_id' => ['except' => ''],
        'financial_year_id' => ['except' => ''],
        'branch_id' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 25],
    ];

    public function getMarqueeId(): ?int
    {
        if ($this->isSaas) {
            return null;
        }
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function mount(?AccountingService $accountingService = null)
    {
        $marqueeId = $this->getMarqueeId();
        $user = auth()->user();

        // Enforce user branch scope
        if (!$this->isSaas && $user && $user->branch_id && !$user->isSuperAdmin()) {
            $this->branch_id = $user->branch_id;
        }

        // Get default active financial year if not supplied via URL
        if (!$this->financial_year_id) {
            $fyQuery = FinancialYear::where('status', 'active');
            if ($this->isSaas) {
                $fyQuery->whereNull('marquee_id');
            } else {
                $fyQuery->where('marquee_id', $marqueeId);
            }
            
            $activeFy = $fyQuery->where('is_default', true)->first() 
                ?? (clone $fyQuery)->orderBy('start_date', 'desc')->first();

            if ($activeFy) {
                $this->financial_year_id = $activeFy->id;
                if (!$this->startDate) {
                    $this->startDate = $activeFy->start_date->format('Y-m-d');
                }
                if (!$this->endDate) {
                    $fyEnd = $activeFy->end_date->format('Y-m-d');
                    $today = date('Y-m-d');
                    $this->endDate = $today < $fyEnd ? $today : $fyEnd;
                }
            }
        }

        // Auto-load if parameters were provided via shared link / deep link
        if ($this->account_id && $this->financial_year_id && $this->startDate && $this->endDate) {
            $service = $accountingService ?? app(AccountingService::class);
            $this->generateReport($service);
        }
    }

    public function updatedFinancialYearId()
    {
        if ($this->financial_year_id) {
            $fy = FinancialYear::find($this->financial_year_id);
            if ($fy) {
                $this->startDate = $fy->start_date->format('Y-m-d');
                $this->endDate = $fy->end_date->format('Y-m-d');
            }
        }
        $this->ledgerData = null;
        $this->resetPage();
    }

    public function updatedAccountId()
    {
        $this->ledgerData = null;
        $this->resetPage();
    }

    public function updatedEntrySearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function generateReport(AccountingService $accountingService)
    {
        $this->validate([
            'account_id' => 'required|exists:accounts,id',
            'financial_year_id' => 'required|exists:financial_years,id',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $this->resetPage();

        try {
            $this->ledgerData = $accountingService->getGeneralLedger(
                (int)$this->account_id,
                $this->startDate,
                $this->endDate,
                $this->branch_id ? (int)$this->branch_id : null,
                (int)$this->financial_year_id
            );
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->ledgerData = null;
        }
    }

    public function exportExcel(AccountingService $accountingService)
    {
        if (!$this->account_id || !$this->financial_year_id || !$this->startDate || !$this->endDate) {
            session()->flash('error', 'Please select required filters and generate the report before exporting.');
            return;
        }

        $ledgerData = $this->ledgerData;
        if (!$ledgerData) {
            try {
                $ledgerData = $accountingService->getGeneralLedger(
                    (int)$this->account_id,
                    $this->startDate,
                    $this->endDate,
                    $this->branch_id ? (int)$this->branch_id : null,
                    (int)$this->financial_year_id
                );
            } catch (\Exception $e) {
                session()->flash('error', $e->getMessage());
                return;
            }
        }

        if (!$ledgerData || empty($ledgerData['account'])) {
            session()->flash('error', 'No ledger data available to export.');
            return;
        }

        $account = $ledgerData['account'];
        $accountCode = $account->account_code;
        $branchName = $this->isSaas 
            ? 'SaaS Platform' 
            : ($this->branch_id ? (Branch::find($this->branch_id)?->name ?? 'Branch') : 'All Branches / Central');
            
        $fy = FinancialYear::find($this->financial_year_id);
        $fyName = $fy?->name ?? 'N/A';

        $filename = "General_Ledger_{$accountCode}_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($ledgerData, $account, $branchName, $fyName) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Microsoft Excel
            fputs($handle, "\xEF\xBB\xBF");

            // Header Meta
            fputcsv($handle, ['GENERAL LEDGER STATEMENT']);
            fputcsv($handle, ['Account Code & Name', "[{$account->account_code}] {$account->name}"]);
            fputcsv($handle, ['Account Nature', strtoupper($account->nature)]);
            fputcsv($handle, ['Financial Year', $fyName]);
            fputcsv($handle, ['Branch', $branchName]);
            fputcsv($handle, ['Period', "{$this->startDate} to {$this->endDate}"]);
            fputcsv($handle, ['Generated At', date('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            // Summary Totals
            fputcsv($handle, ['Opening Balance', number_format($ledgerData['opening_balance'], 2, '.', '')]);
            fputcsv($handle, ['Total Debits', number_format($ledgerData['total_debit'], 2, '.', '')]);
            fputcsv($handle, ['Total Credits', number_format($ledgerData['total_credit'], 2, '.', '')]);
            fputcsv($handle, ['Closing Balance', number_format($ledgerData['closing_balance'], 2, '.', '')]);
            fputcsv($handle, []);

            // Columns
            fputcsv($handle, ['Date', 'Voucher No', 'Reference', 'Narration', 'Debit', 'Credit', 'Running Balance']);

            // Opening Balance Row
            fputcsv($handle, [
                $this->startDate,
                '—',
                '—',
                'Opening Balance Brought Forward',
                $ledgerData['opening_balance_debit'] > 0 ? number_format($ledgerData['opening_balance_debit'], 2, '.', '') : '0.00',
                $ledgerData['opening_balance_credit'] > 0 ? number_format($ledgerData['opening_balance_credit'], 2, '.', '') : '0.00',
                number_format($ledgerData['opening_balance'], 2, '.', '')
            ]);

            // Entries
            foreach ($ledgerData['entries'] as $entry) {
                fputcsv($handle, [
                    $entry['voucher_date'],
                    $entry['voucher_no'],
                    $entry['reference'] ?? '',
                    $entry['narration'] ?? '',
                    $entry['debit'] > 0 ? number_format($entry['debit'], 2, '.', '') : '0.00',
                    $entry['credit'] > 0 ? number_format($entry['credit'], 2, '.', '') : '0.00',
                    number_format($entry['running_balance'], 2, '.', '')
                ]);
            }

            // Totals Row
            fputcsv($handle, [
                '',
                '',
                '',
                'Closing Balances / Totals',
                number_format($ledgerData['total_debit'], 2, '.', ''),
                number_format($ledgerData['total_credit'], 2, '.', ''),
                number_format($ledgerData['closing_balance'], 2, '.', '')
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();
        $user = auth()->user();

        // Get leaf accounts
        $accountsQuery = Account::whereDoesntHave('children')
            ->where('is_active', true)
            ->orderBy('account_code');
            
        if ($this->isSaas) {
            $accountsQuery->whereNull('marquee_id');
        } else {
            $accountsQuery->where('marquee_id', $marqueeId);
        }
        $accounts = $accountsQuery->get();

        // Format accounts for fast client-side searchable select
        $accountsFormatted = $accounts->map(function ($acc) {
            return [
                'id' => (int)$acc->id,
                'code' => (string)$acc->account_code,
                'name' => (string)$acc->name,
                'nature' => (string)$acc->nature,
                'label' => "[{$acc->account_code}] {$acc->name} ({$acc->nature})",
            ];
        })->values()->toArray();

        $fyQuery = FinancialYear::orderBy('start_date', 'desc');
        if ($this->isSaas) {
            $fyQuery->whereNull('marquee_id');
        } else {
            $fyQuery->where('marquee_id', $marqueeId);
        }
        $financialYears = $fyQuery->get();
        
        $branches = collect();
        if (!$this->isSaas) {
            if ($user && $user->branch_id && !$user->isSuperAdmin()) {
                $branches = Branch::where('id', $user->branch_id)->get();
            } else {
                $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
            }
        }

        // Handle pagination & filtered entries
        $paginatedEntries = null;
        $totalEntriesCount = 0;

        if ($this->ledgerData && isset($this->ledgerData['entries'])) {
            $entries = $this->ledgerData['entries'];

            if (!empty($this->entrySearch)) {
                $searchTerm = strtolower(trim($this->entrySearch));
                $entries = array_filter($entries, function ($item) use ($searchTerm) {
                    return str_contains(strtolower($item['voucher_no'] ?? ''), $searchTerm)
                        || str_contains(strtolower($item['reference'] ?? ''), $searchTerm)
                        || str_contains(strtolower($item['narration'] ?? ''), $searchTerm);
                });
                $entries = array_values($entries);
            }

            $totalEntriesCount = count($entries);
            $perPage = $this->perPage === 'all' ? max($totalEntriesCount, 1) : (int)$this->perPage;
            $currentPage = $this->getPage();

            $maxPage = max((int)ceil($totalEntriesCount / $perPage), 1);
            if ($currentPage > $maxPage) {
                $currentPage = $maxPage;
                $this->setPage($currentPage);
            }

            $offset = ($currentPage - 1) * $perPage;
            $pageItems = array_slice($entries, $offset, $perPage);

            $paginatedEntries = new LengthAwarePaginator(
                $pageItems,
                $totalEntriesCount,
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        }

        return view('livewire.finance.general-ledger', [
            'accounts' => $accounts,
            'accountsFormatted' => $accountsFormatted,
            'financialYears' => $financialYears,
            'branches' => $branches,
            'paginatedEntries' => $paginatedEntries,
            'totalEntriesCount' => $totalEntriesCount,
        ]);
    }
}
