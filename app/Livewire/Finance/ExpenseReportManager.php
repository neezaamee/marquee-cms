<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseBudget;
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

    public function generateReport()
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        switch ($this->reportType) {
            case 'category_summary':
                $query = Expense::where('marquee_id', $marqueeId)
                    ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED]);
                if ($this->branch_id) { $query->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $query->where('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $query->where('expense_date', '<=', $this->end_date); }
                
                $totalAll = (float)(clone $query)->sum('total_amount_base');

                $records = (clone $query)
                    ->select('expense_category_id', DB::raw('COUNT(id) as count'), DB::raw('SUM(total_amount_base) as total_amount'))
                    ->groupBy('expense_category_id')
                    ->with('category')
                    ->orderByDesc('total_amount')
                    ->get();

                $this->reportData = $records->map(function ($row) use ($totalAll) {
                    $pct = $totalAll > 0 ? ($row->total_amount / $totalAll) * 100 : 0;
                    return [
                        'category_name' => $row->category->name ?? 'Unclassified / Split',
                        'voucher_count' => $row->count,
                        'total_amount' => (float)$row->total_amount,
                        'percentage' => round($pct, 1),
                    ];
                })->toArray();
                break;

            case 'payment_methods':
                $query = Expense::where('marquee_id', $marqueeId)
                    ->whereNotIn('status', [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED]);
                if ($this->branch_id) { $query->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $query->where('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $query->where('expense_date', '<=', $this->end_date); }
                
                $totalAll = (float)(clone $query)->sum('total_amount_base');

                $records = (clone $query)
                    ->select('payment_method', DB::raw('COUNT(id) as count'), DB::raw('SUM(total_amount_base) as total_amount'))
                    ->groupBy('payment_method')
                    ->orderByDesc('total_amount')
                    ->get();

                $this->reportData = $records->map(function ($row) use ($totalAll) {
                    $pct = $totalAll > 0 ? ($row->total_amount / $totalAll) * 100 : 0;
                    return [
                        'payment_method' => $row->payment_method ?: 'Cash',
                        'voucher_count' => $row->count,
                        'total_amount' => (float)$row->total_amount,
                        'percentage' => round($pct, 1),
                    ];
                })->toArray();
                break;

            case 'register':
                $query = Expense::where('marquee_id', $marqueeId)->with(['category', 'branch', 'supplier']);
                if ($this->branch_id) { $query->where('branch_id', $this->branch_id); }
                if ($this->expense_category_id) { $query->where('expense_category_id', $this->expense_category_id); }
                if ($this->supplier_id) { $query->where('supplier_id', $this->supplier_id); }
                if ($this->payment_method) { $query->where('payment_method', $this->payment_method); }
                if ($this->start_date) { $query->where('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $query->where('expense_date', '<=', $this->end_date); }
                $this->reportData = $query->orderBy('expense_date', 'desc')->get()->toArray();
                break;

            case 'budget_vs_actual':
                $query = ExpenseBudget::where('marquee_id', $marqueeId)->with(['category', 'branch']);
                if ($this->branch_id) { $query->where('branch_id', $this->branch_id); }
                if ($this->expense_category_id) { $query->where('category_id', $this->expense_category_id); }
                if ($this->year) { $query->where('year', $this->year); }
                $this->reportData = $query->get()->toArray();
                break;

            case 'utility':
                $query = Expense::where('marquee_id', $marqueeId)
                    ->whereHas('utilityBill')
                    ->with(['utilityBill', 'branch']);
                if ($this->branch_id) { $query->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $query->where('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $query->where('expense_date', '<=', $this->end_date); }
                $this->reportData = $query->orderBy('expense_date', 'desc')->get()->toArray();
                break;

            case 'tax_summary':
                $query = Expense::where('marquee_id', $marqueeId)
                    ->select('tax_amount', 'amount', 'total_amount', 'expense_date', 'expense_number', 'reference_number')
                    ->where('tax_amount', '>', 0);
                if ($this->branch_id) { $query->where('branch_id', $this->branch_id); }
                if ($this->start_date) { $query->where('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $query->where('expense_date', '<=', $this->end_date); }
                $this->reportData = $query->orderBy('expense_date', 'desc')->get()->toArray();
                break;

            case 'cost_center':
                $query = Expense::where('marquee_id', $marqueeId)
                    ->select('cost_center', DB::raw('SUM(amount) as subtotal'), DB::raw('SUM(tax_amount) as tax'), DB::raw('SUM(total_amount_base) as total'))
                    ->whereNotNull('cost_center')
                    ->groupBy('cost_center');
                if ($this->start_date) { $query->where('expense_date', '>=', $this->start_date); }
                if ($this->end_date) { $query->where('expense_date', '<=', $this->end_date); }
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
                fputcsv($file, ['Voucher No', 'Date', 'Category', 'Vendor', 'Method', 'Tax', 'Discount', 'Total']);
                foreach ($this->reportData as $row) {
                    fputcsv($file, [
                        $row['expense_number'],
                        $row['expense_date'],
                        $row['category']['name'] ?? '—',
                        $row['supplier']['name'] ?? '—',
                        $row['payment_method'],
                        $row['tax_amount'],
                        $row['discount_amount'],
                        $row['total_amount'],
                    ]);
                }
            } elseif ($this->reportType === 'budget_vs_actual') {
                fputcsv($file, ['Category', 'Branch', 'Period', 'Allocated', 'Consumed', 'Remaining']);
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
