<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseBudget;
use App\Models\PaymentVoucher;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExpenseReportManager extends Component
{
    public $reportType = 'category_summary';
    
    // Filters
    public $branch_id;
    public $expense_category_id;
    public $supplier_id;
    public $payment_method;
    public $department;
    public $cost_center;
    public $start_date;
    public $end_date;
    public $year;

    public $reportData = [];

    public function mount()
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
        $this->year = (int)date('Y');
        $this->generateReport();
    }

    public function updatedReportType()
    {
        $this->generateReport();
    }

    /**
     * Base query for operating expense Payment Vouchers.
     * Includes all non-draft, non-cancelled payment vouchers where payee_type is expense
     * or debit_account is an Expense head, and expense_id is null (to avoid double counting).
     */
    protected function getOperatingPaymentVouchersQuery(?int $marqueeId)
    {
        return PaymentVoucher::where('marquee_id', $marqueeId)
            ->whereNull('expense_id')
            ->whereNotIn('status', [PaymentVoucher::STATUS_DRAFT, PaymentVoucher::STATUS_CANCELLED])
            ->where(function ($q) {
                $q->where('payee_type', PaymentVoucher::PAYEE_EXPENSE)
                  ->orWhereHas('debitAccount', function ($aq) {
                      $aq->where('nature', 'Expense')->orWhere('account_code', 'like', '5%');
                  });
            });
    }

    public function generateReport()
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
        $categories = ExpenseCategory::where('marquee_id', $marqueeId)->get();
        $categoriesById = $categories->keyBy('id');
        $categoriesByAccountId = $categories->whereNotNull('default_account_id')->keyBy('default_account_id');

        switch ($this->reportType) {
            case 'category_summary':
                // 1. Expense Module Records
                $expQuery = Expense::where('marquee_id', $marqueeId)
                    ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED]);
                if ($this->branch_id) { $expQuery->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $expQuery->whereDate('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $expQuery->whereDate('expense_date', '<=', $this->end_date); }
                if ($this->expense_category_id) { $expQuery->where('expense_category_id', $this->expense_category_id); }
                if ($this->supplier_id) { $expQuery->where('supplier_id', $this->supplier_id); }
                if ($this->payment_method) { $expQuery->where('payment_method', $this->payment_method); }

                $expRecords = (clone $expQuery)
                    ->select('expense_category_id', DB::raw('COUNT(id) as count'), DB::raw('SUM(total_amount_base) as total_amount'))
                    ->groupBy('expense_category_id')
                    ->with('category')
                    ->get();

                // 2. Payment Voucher Operating Expense Records
                $pvQuery = $this->getOperatingPaymentVouchersQuery($marqueeId)->with(['debitAccount']);
                if ($this->branch_id) { $pvQuery->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $pvQuery->whereDate('voucher_date', '>=', $this->start_date); }
                if ($this->end_date) { $pvQuery->whereDate('voucher_date', '<=', $this->end_date); }
                if ($this->supplier_id) { $pvQuery->where('supplier_id', $this->supplier_id); }
                if ($this->expense_category_id) {
                    $catAccount = ExpenseCategory::where('id', $this->expense_category_id)->value('default_account_id');
                    $pvQuery->where(function ($q) use ($catAccount) {
                        $q->where('payee_id', $this->expense_category_id);
                        if ($catAccount) {
                            $q->orWhere('debit_account_id', $catAccount);
                        }
                    });
                }
                if ($this->payment_method) {
                    if ($this->payment_method === 'Cash') {
                        $pvQuery->where(function($q) {
                            $q->where('payment_method', 'Cash')->orWhere('voucher_type', 'CPV');
                        });
                    } elseif ($this->payment_method === 'Bank') {
                        $pvQuery->where(function($q) {
                            $q->whereIn('payment_method', ['Bank Transfer', 'Cheque', 'Online', 'Bank'])->orWhere('voucher_type', 'BPV');
                        });
                    } elseif ($this->payment_method === 'Petty Cash') {
                        $pvQuery->where('payment_method', 'Petty Cash');
                    } else {
                        $pvQuery->whereRaw('1 = 0');
                    }
                }

                $pvRecords = $pvQuery->get();

                // 3. Merge aggregates by category name
                $categoryAggregates = [];

                foreach ($expRecords as $row) {
                    $catName = $row->category->name ?? 'Unclassified / Split';
                    if (!isset($categoryAggregates[$catName])) {
                        $categoryAggregates[$catName] = [
                            'category_name' => $catName,
                            'voucher_count' => 0,
                            'total_amount' => 0.0,
                        ];
                    }
                    $categoryAggregates[$catName]['voucher_count'] += (int)$row->count;
                    $categoryAggregates[$catName]['total_amount'] += (float)$row->total_amount;
                }

                foreach ($pvRecords as $pv) {
                    $catName = 'Operating Expenses (PV)';
                    if ($pv->payee_id && isset($categoriesById[$pv->payee_id])) {
                        $catName = $categoriesById[$pv->payee_id]->name;
                    } elseif ($pv->debit_account_id && isset($categoriesByAccountId[$pv->debit_account_id])) {
                        $catName = $categoriesByAccountId[$pv->debit_account_id]->name;
                    } elseif ($pv->debitAccount) {
                        $catName = $pv->debitAccount->name;
                    } elseif (!empty($pv->payee_name)) {
                        $catName = $pv->payee_name;
                    }

                    if (!isset($categoryAggregates[$catName])) {
                        $categoryAggregates[$catName] = [
                            'category_name' => $catName,
                            'voucher_count' => 0,
                            'total_amount' => 0.0,
                        ];
                    }
                    $categoryAggregates[$catName]['voucher_count'] += 1;
                    $categoryAggregates[$catName]['total_amount'] += (float)$pv->amount;
                }

                $totalAll = array_sum(array_column($categoryAggregates, 'total_amount'));

                // Calculate percentage share and sort descending by amount
                $result = [];
                foreach ($categoryAggregates as $catName => $row) {
                    $pct = $totalAll > 0 ? ($row['total_amount'] / $totalAll) * 100 : 0;
                    $result[] = [
                        'category_name' => $row['category_name'],
                        'voucher_count' => $row['voucher_count'],
                        'total_amount' => round($row['total_amount'], 2),
                        'percentage' => round($pct, 1),
                    ];
                }

                usort($result, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);
                $this->reportData = $result;
                break;

            case 'payment_methods':
                // 1. Expense module payment methods
                $expQuery = Expense::where('marquee_id', $marqueeId)
                    ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED]);
                if ($this->branch_id) { $expQuery->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $expQuery->whereDate('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $expQuery->whereDate('expense_date', '<=', $this->end_date); }
                if ($this->expense_category_id) { $expQuery->where('expense_category_id', $this->expense_category_id); }
                if ($this->supplier_id) { $expQuery->where('supplier_id', $this->supplier_id); }

                $expRecords = (clone $expQuery)
                    ->select('payment_method', DB::raw('COUNT(id) as count'), DB::raw('SUM(total_amount_base) as total_amount'))
                    ->groupBy('payment_method')
                    ->get();

                // 2. Payment Voucher payment methods
                $pvQuery = $this->getOperatingPaymentVouchersQuery($marqueeId);
                if ($this->branch_id) { $pvQuery->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $pvQuery->whereDate('voucher_date', '>=', $this->start_date); }
                if ($this->end_date) { $pvQuery->whereDate('voucher_date', '<=', $this->end_date); }
                if ($this->supplier_id) { $pvQuery->where('supplier_id', $this->supplier_id); }
                if ($this->expense_category_id) {
                    $catAccount = ExpenseCategory::where('id', $this->expense_category_id)->value('default_account_id');
                    $pvQuery->where(function ($q) use ($catAccount) {
                        $q->where('payee_id', $this->expense_category_id);
                        if ($catAccount) {
                            $q->orWhere('debit_account_id', $catAccount);
                        }
                    });
                }

                $pvRecords = $pvQuery->get();

                $methodAggregates = [
                    'Cash' => ['count' => 0, 'total' => 0.0],
                    'Bank' => ['count' => 0, 'total' => 0.0],
                    'Petty Cash' => ['count' => 0, 'total' => 0.0],
                    'Accounts Payable' => ['count' => 0, 'total' => 0.0],
                ];

                foreach ($expRecords as $row) {
                    $m = $row->payment_method ?: 'Cash';
                    if (in_array($m, ['Credit', 'Accounts Payable', Expense::METHOD_CREDIT])) {
                        $standardKey = 'Accounts Payable';
                    } elseif (in_array(strtolower($m), ['petty cash', 'petty_cash'])) {
                        $standardKey = 'Petty Cash';
                    } elseif (in_array(strtolower($m), ['bank', 'bank transfer', 'cheque', 'online'])) {
                        $standardKey = 'Bank';
                    } else {
                        $standardKey = 'Cash';
                    }

                    $methodAggregates[$standardKey]['count'] += (int)$row->count;
                    $methodAggregates[$standardKey]['total'] += (float)$row->total_amount;
                }

                foreach ($pvRecords as $pv) {
                    $pm = strtolower($pv->payment_method ?? '');
                    if ($pv->voucher_type === 'CPV' || $pm === 'cash') {
                        $standardKey = 'Cash';
                    } elseif ($pm === 'petty cash' || $pm === 'petty_cash') {
                        $standardKey = 'Petty Cash';
                    } else {
                        $standardKey = 'Bank';
                    }

                    $methodAggregates[$standardKey]['count'] += 1;
                    $methodAggregates[$standardKey]['total'] += (float)$pv->amount;
                }

                $totalAll = array_sum(array_column($methodAggregates, 'total'));

                $result = [];
                foreach ($methodAggregates as $methodName => $data) {
                    if ($data['count'] > 0 || $data['total'] > 0) {
                        $pct = $totalAll > 0 ? ($data['total'] / $totalAll) * 100 : 0;
                        $result[] = [
                            'payment_method' => $methodName,
                            'voucher_count' => $data['count'],
                            'total_amount' => round($data['total'], 2),
                            'percentage' => round($pct, 1),
                        ];
                    }
                }

                usort($result, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);
                $this->reportData = $result;
                break;

            case 'register':
                // 1. Expense module records
                $expQuery = Expense::where('marquee_id', $marqueeId)
                    ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
                    ->with(['category', 'branch', 'supplier', 'employee']);
                if ($this->branch_id) { $expQuery->where('branch_id', $this->branch_id); }
                if ($this->expense_category_id) { $expQuery->where('expense_category_id', $this->expense_category_id); }
                if ($this->supplier_id) { $expQuery->where('supplier_id', $this->supplier_id); }
                if ($this->payment_method) { $expQuery->where('payment_method', $this->payment_method); }
                if ($this->start_date) { $expQuery->whereDate('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $expQuery->whereDate('expense_date', '<=', $this->end_date); }

                $expenseList = $expQuery->orderBy('expense_date', 'desc')->get();
                $mergedRegister = [];

                foreach ($expenseList as $exp) {
                    $mergedRegister[] = [
                        'source' => 'Expense Voucher',
                        'source_badge' => 'Expense',
                        'expense_number' => $exp->expense_number,
                        'expense_date' => $exp->expense_date ? $exp->expense_date->format('Y-m-d') : '',
                        'branch' => ['name' => $exp->branch?->name ?? 'Main'],
                        'category' => ['name' => $exp->category?->name ?? 'Split / Direct'],
                        'supplier' => ['name' => $exp->supplier?->name ?? ($exp->employee?->name ?? '—')],
                        'payment_method' => $exp->payment_method ?: 'Cash',
                        'tax_amount' => (float)$exp->tax_amount,
                        'discount_amount' => (float)$exp->discount_amount,
                        'total_amount' => (float)$exp->total_amount,
                        'status' => ucfirst($exp->status),
                    ];
                }

                // 2. Payment Voucher Operating Expense records
                $pvQuery = $this->getOperatingPaymentVouchersQuery($marqueeId)->with(['branch', 'debitAccount', 'supplier']);
                if ($this->branch_id) { $pvQuery->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $pvQuery->whereDate('voucher_date', '>=', $this->start_date); }
                if ($this->end_date) { $pvQuery->whereDate('voucher_date', '<=', $this->end_date); }
                if ($this->supplier_id) { $pvQuery->where('supplier_id', $this->supplier_id); }
                if ($this->expense_category_id) {
                    $catAccount = ExpenseCategory::where('id', $this->expense_category_id)->value('default_account_id');
                    $pvQuery->where(function ($q) use ($catAccount) {
                        $q->where('payee_id', $this->expense_category_id);
                        if ($catAccount) {
                            $q->orWhere('debit_account_id', $catAccount);
                        }
                    });
                }
                if ($this->payment_method) {
                    if ($this->payment_method === 'Cash') {
                        $pvQuery->where(function($q) {
                            $q->where('payment_method', 'Cash')->orWhere('voucher_type', 'CPV');
                        });
                    } elseif ($this->payment_method === 'Bank') {
                        $pvQuery->where(function($q) {
                            $q->whereIn('payment_method', ['Bank Transfer', 'Cheque', 'Online', 'Bank'])->orWhere('voucher_type', 'BPV');
                        });
                    } elseif ($this->payment_method === 'Petty Cash') {
                        $pvQuery->where('payment_method', 'Petty Cash');
                    } else {
                        $pvQuery->whereRaw('1 = 0');
                    }
                }

                $pvList = $pvQuery->orderBy('voucher_date', 'desc')->get();

                foreach ($pvList as $pv) {
                    $catName = 'Operating Expense';
                    if ($pv->payee_id && isset($categoriesById[$pv->payee_id])) {
                        $catName = $categoriesById[$pv->payee_id]->name;
                    } elseif ($pv->debit_account_id && isset($categoriesByAccountId[$pv->debit_account_id])) {
                        $catName = $categoriesByAccountId[$pv->debit_account_id]->name;
                    } elseif ($pv->debitAccount) {
                        $catName = $pv->debitAccount->name;
                    } elseif (!empty($pv->payee_name)) {
                        $catName = $pv->payee_name;
                    }

                    $mergedRegister[] = [
                        'source' => 'Payment Voucher',
                        'source_badge' => $pv->voucher_type, // 'CPV' or 'BPV'
                        'expense_number' => $pv->voucher_no,
                        'expense_date' => $pv->voucher_date ? $pv->voucher_date->format('Y-m-d') : '',
                        'branch' => ['name' => $pv->branch?->name ?? 'Main'],
                        'category' => ['name' => $catName],
                        'supplier' => ['name' => $pv->payee_name ?: ($pv->supplier?->name ?? '—')],
                        'payment_method' => $pv->payment_method ?: ($pv->voucher_type === 'CPV' ? 'Cash' : 'Bank Transfer'),
                        'tax_amount' => 0.00,
                        'discount_amount' => 0.00,
                        'total_amount' => (float)$pv->amount,
                        'status' => ucfirst($pv->status),
                    ];
                }

                // Sort unified log by date descending, then voucher number descending
                usort($mergedRegister, function ($a, $b) {
                    $dCompare = strcmp($b['expense_date'], $a['expense_date']);
                    return $dCompare !== 0 ? $dCompare : strcmp($b['expense_number'], $a['expense_number']);
                });

                $this->reportData = $mergedRegister;
                break;

            case 'budget_vs_actual':
                $query = ExpenseBudget::where('marquee_id', $marqueeId)->with(['category', 'branch']);
                if ($this->branch_id) { $query->where('branch_id', $this->branch_id); }
                if ($this->expense_category_id) { $query->where('category_id', $this->expense_category_id); }
                if ($this->year) { $query->where('year', $this->year); }
                $budgets = $query->get();

                // Calculate actual expenditures combining Expense + PaymentVoucher
                $this->reportData = $budgets->map(function ($b) use ($marqueeId) {
                    $year = $b->year;
                    $month = $b->month;
                    $catId = $b->category_id;
                    $cat = $b->category;
                    $catAccId = $cat?->default_account_id;

                    // 1. Expense actual
                    $expQ = Expense::where('marquee_id', $marqueeId)
                        ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED, Expense::STATUS_CANCELLED])
                        ->where('expense_category_id', $catId);
                    if ($b->branch_id) { $expQ->where('branch_id', $b->branch_id); }
                    if ($month) {
                        $start = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
                        $end = date('Y-m-t', strtotime($start));
                        $expQ->whereBetween('expense_date', [$start, $end]);
                    } else {
                        $expQ->whereYear('expense_date', $year);
                    }
                    $expActual = (float)$expQ->sum('total_amount_base');

                    // 2. Payment voucher actual
                    $pvQ = PaymentVoucher::where('marquee_id', $marqueeId)
                        ->whereNull('expense_id')
                        ->whereNotIn('status', [PaymentVoucher::STATUS_DRAFT, PaymentVoucher::STATUS_CANCELLED])
                        ->where(function ($q) use ($catId, $catAccId) {
                            $q->where('payee_id', $catId);
                            if ($catAccId) {
                                $q->orWhere('debit_account_id', $catAccId);
                            }
                        });
                    if ($b->branch_id) { $pvQ->where('branch_id', $b->branch_id); }
                    if ($month) {
                        $start = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
                        $end = date('Y-m-t', strtotime($start));
                        $pvQ->whereBetween('voucher_date', [$start, $end]);
                    } else {
                        $pvQ->whereYear('voucher_date', $year);
                    }
                    $pvActual = (float)$pvQ->sum('amount');

                    $totalConsumed = $expActual + $pvActual;

                    return [
                        'category' => $b->category ? ['name' => $b->category->name] : ['name' => 'General'],
                        'branch' => $b->branch ? ['name' => $b->branch->name] : null,
                        'month' => $b->month,
                        'year' => $b->year,
                        'allocated_amount' => (float)$b->allocated_amount,
                        'consumed_amount' => $totalConsumed,
                    ];
                })->toArray();
                break;

            case 'utility':
                $query = Expense::where('marquee_id', $marqueeId)
                    ->whereHas('utilityBill')
                    ->with(['utilityBill', 'branch']);
                if ($this->branch_id) { $query->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $query->whereDate('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $query->whereDate('expense_date', '<=', $this->end_date); }
                $this->reportData = $query->orderBy('expense_date', 'desc')->get()->toArray();
                break;

            case 'tax_summary':
                $query = Expense::where('marquee_id', $marqueeId)
                    ->select('tax_amount', 'amount', 'total_amount', 'expense_date', 'expense_number', 'reference_number')
                    ->where('tax_amount', '>', 0);
                if ($this->branch_id) { $query->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $query->whereDate('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $query->whereDate('expense_date', '<=', $this->end_date); }
                $this->reportData = $query->orderBy('expense_date', 'desc')->get()->toArray();
                break;

            case 'cost_center':
                $query = Expense::where('marquee_id', $marqueeId)
                    ->select('cost_center', DB::raw('SUM(amount) as subtotal'), DB::raw('SUM(tax_amount) as tax'), DB::raw('SUM(total_amount_base) as total'))
                    ->whereNotNull('cost_center')
                    ->groupBy('cost_center');
                if ($this->start_date) { $query->whereDate('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $query->whereDate('expense_date', '<=', $this->end_date); }
                $this->reportData = $query->get()->toArray();
                break;
        }
    }

    public function exportCSV()
    {
        $this->generateReport();
        if (empty($this->reportData)) {
            session()->flash('error', 'No data to export.');
            return;
        }

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Expense_Report_" . time() . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM
            
            if ($this->reportType === 'category_summary') {
                fputcsv($file, ['Category Name', 'Voucher Count', 'Total Spent (PKR)', 'Percentage (%)']);
                foreach ($this->reportData as $row) {
                    fputcsv($file, [
                        $row['category_name'],
                        $row['voucher_count'],
                        $row['total_amount'],
                        $row['percentage'] . '%',
                    ]);
                }
            } elseif ($this->reportType === 'payment_methods') {
                fputcsv($file, ['Payment Method', 'Voucher Count', 'Total Amount (PKR)', 'Percentage (%)']);
                foreach ($this->reportData as $row) {
                    fputcsv($file, [
                        $row['payment_method'],
                        $row['voucher_count'],
                        $row['total_amount'],
                        $row['percentage'] . '%',
                    ]);
                }
            } elseif ($this->reportType === 'register') {
                fputcsv($file, ['Source', 'Voucher No', 'Date', 'Branch', 'Category', 'Payee / Vendor', 'Method', 'Tax (PKR)', 'Discount (PKR)', 'Total (PKR)', 'Status']);
                foreach ($this->reportData as $row) {
                    fputcsv($file, [
                        $row['source'] ?? 'Expense Voucher',
                        $row['expense_number'],
                        $row['expense_date'],
                        $row['branch']['name'] ?? 'Main',
                        $row['category']['name'] ?? '—',
                        $row['supplier']['name'] ?? '—',
                        $row['payment_method'],
                        $row['tax_amount'],
                        $row['discount_amount'],
                        $row['total_amount'],
                        $row['status'] ?? '—',
                    ]);
                }
            } elseif ($this->reportType === 'budget_vs_actual') {
                fputcsv($file, ['Category', 'Branch', 'Period', 'Allocated (PKR)', 'Consumed (PKR)', 'Remaining (PKR)']);
                foreach ($this->reportData as $row) {
                    fputcsv($file, [
                        $row['category']['name'] ?? '—',
                        $row['branch']['name'] ?? 'Company-Wide',
                        $row['month'] ? date('F', mktime(0, 0, 0, $row['month'], 10)) . ' ' . $row['year'] : 'Annual ' . $row['year'],
                        $row['allocated_amount'],
                        $row['consumed_amount'],
                        $row['allocated_amount'] - $row['consumed_amount'],
                    ]);
                }
            } elseif ($this->reportType === 'utility') {
                fputcsv($file, ['Voucher', 'Utility Type', 'Consumer No', 'Billing Period', 'Readings', 'Late Charges', 'Total Cost']);
                foreach ($this->reportData as $row) {
                    fputcsv($file, [
                        $row['expense_number'],
                        $row['utility_bill']['utility_type'] ?? '—',
                        $row['utility_bill']['consumer_number'] ?? '—',
                        $row['utility_bill']['billing_period'] ?? '—',
                        ($row['utility_bill']['previous_reading'] ?? '0') . ' to ' . ($row['utility_bill']['current_reading'] ?? '0'),
                        $row['utility_bill']['late_charges'] ?? '0',
                        $row['total_amount']
                    ]);
                }
            } elseif ($this->reportType === 'tax_summary') {
                fputcsv($file, ['Voucher No', 'Date', 'Reference', 'Subtotal', 'Tax Amount', 'Total Cost']);
                foreach ($this->reportData as $row) {
                    fputcsv($file, [
                        $row['expense_number'],
                        $row['expense_date'],
                        $row['reference_number'] ?? '—',
                        $row['amount'],
                        $row['tax_amount'],
                        $row['total_amount'],
                    ]);
                }
            } elseif ($this->reportType === 'cost_center') {
                fputcsv($file, ['Cost Center', 'Subtotal Amount', 'Tax Mapped', 'Grand Total Base']);
                foreach ($this->reportData as $row) {
                    fputcsv($file, [
                        $row['cost_center'],
                        $row['subtotal'],
                        $row['tax'],
                        $row['total'],
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $categories = ExpenseCategory::where('marquee_id', $marqueeId)->where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('marquee_id', $marqueeId)->orderBy('name')->get();

        // Calculate summary KPI cards for current view
        $reportTotal = 0;
        $reportCount = count($this->reportData);
        if ($this->reportType === 'category_summary' || $this->reportType === 'payment_methods') {
            $reportTotal = array_sum(array_column($this->reportData, 'total_amount'));
        } elseif ($this->reportType === 'register' || $this->reportType === 'utility' || $this->reportType === 'tax_summary') {
            $reportTotal = array_sum(array_column($this->reportData, 'total_amount'));
        } elseif ($this->reportType === 'budget_vs_actual') {
            $reportTotal = array_sum(array_column($this->reportData, 'consumed_amount'));
        } elseif ($this->reportType === 'cost_center') {
            $reportTotal = array_sum(array_column($this->reportData, 'total'));
        }

        return view('livewire.finance.expense-report-manager', [
            'branches' => $branches,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'reportTotal' => $reportTotal,
            'reportCount' => $reportCount,
        ]);
    }
}
