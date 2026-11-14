<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentAttendance;
use App\Models\DepartmentProduction;
use App\Models\DepartmentStockLedger;
use App\Models\InventoryItem;
use App\Models\Marquee;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentReports extends Component
{
    use WithPagination;

    public $reportType = 'consumption'; // 'consumption', 'attendance', 'production', 'ledger_summary'
    public $dateFrom;
    public $dateTo;
    public $filterDepartment = '';
    public $filterBranch = '';
    public $perPage = 25; // 15, 25, 50, 100, 'all'

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $user = auth()->user();
        if ($user && $user->branch_id && !$user->isSuperAdmin() && !$user->isBusinessOwner()) {
            $this->filterBranch = $user->branch_id;
        } else {
            $this->filterBranch = '';
        }
    }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function getEffectiveBranchId(): ?int
    {
        if ($this->filterBranch) {
            return (int) $this->filterBranch;
        }
        $user = auth()->user();
        if ($user && $user->branch_id && !$user->isSuperAdmin() && !$user->isBusinessOwner()) {
            return (int) $user->branch_id;
        }
        return null;
    }

    public function updatedReportType()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment()
    {
        $this->resetPage();
    }

    public function updatedFilterBranch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        $marqueeId = $this->getMarqueeId();
        $branchId = $this->getEffectiveBranchId();
        $startDateTime = $this->dateFrom ? $this->dateFrom . ' 00:00:00' : now()->startOfMonth()->format('Y-m-d 00:00:00');
        $endDateTime = $this->dateTo ? $this->dateTo . ' 23:59:59' : now()->format('Y-m-d 23:59:59');

        $fileName = 'department-' . $this->reportType . '-report-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($marqueeId, $branchId, $startDateTime, $endDateTime) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fputs($output, "\xEF\xBB\xBF");

            if ($this->reportType === 'consumption') {
                fputcsv($output, ['Date', 'Department', 'Item Code', 'Item Name', 'Transaction Type', 'Quantity', 'Unit Rate (Rs.)', 'Total Value (Rs.)', 'Reference']);
                $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                    ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
                    ->with(['department', 'inventoryItem']);
                if ($branchId) $query->where('branch_id', $branchId);
                if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);

                foreach ($query->lazy() as $row) {
                    $qty = $row->qty_in > 0 ? $row->qty_in : $row->qty_out;
                    $ref = $row->reference_type ? (str_replace(['App\\Models\\', 'DepartmentStock'], '', $row->reference_type) . ' #' . $row->reference_id) : '—';
                    fputcsv($output, [
                        $row->transaction_date ? $row->transaction_date->format('Y-m-d') : '—',
                        $row->department->name ?? 'N/A',
                        $row->inventoryItem->item_code ?? 'N/A',
                        $row->inventoryItem->name ?? 'N/A',
                        $row->transaction_type,
                        $qty,
                        number_format($row->unit_price ?? 0, 2, '.', ''),
                        number_format($row->total_cost ?? 0, 2, '.', ''),
                        $ref,
                    ]);
                }
            } elseif ($this->reportType === 'attendance') {
                fputcsv($output, ['Date', 'Department', 'Employee Code', 'Employee Name', 'Check In', 'Check Out', 'Status']);
                $query = DepartmentAttendance::where('marquee_id', $marqueeId)
                    ->whereBetween('date', [$startDateTime, $endDateTime])
                    ->with(['department', 'employee']);
                if ($branchId) $query->where('branch_id', $branchId);
                if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);

                foreach ($query->lazy() as $row) {
                    fputcsv($output, [
                        $row->date ? $row->date->format('Y-m-d') : '—',
                        $row->department->name ?? 'N/A',
                        $row->employee->employee_id ?? 'N/A',
                        $row->employee->name ?? 'N/A',
                        $row->check_in ?? '—',
                        $row->check_out ?? '—',
                        $row->status,
                    ]);
                }
            } elseif ($this->reportType === 'production') {
                fputcsv($output, ['Date', 'Batch No', 'Department', 'Recipe', 'Produced Qty', 'Wastage Qty', 'Prepared By']);
                $query = DepartmentProduction::where('marquee_id', $marqueeId)
                    ->whereBetween('production_date', [$startDateTime, $endDateTime])
                    ->with(['department', 'recipe', 'prepStaff']);
                if ($branchId) $query->where('branch_id', $branchId);
                if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);

                foreach ($query->lazy() as $row) {
                    fputcsv($output, [
                        $row->production_date ? $row->production_date->format('Y-m-d') : '—',
                        $row->batch_number,
                        $row->department->name ?? 'N/A',
                        $row->recipe->name ?? 'N/A',
                        $row->produced_qty,
                        $row->legacy_finished_good_wastage_qty ?? $row->wastage_qty ?? 0,
                        $row->prepStaff->name ?? 'System',
                    ]);
                }
            } elseif ($this->reportType === 'ledger_summary') {
                fputcsv($output, ['Department', 'Item Code', 'Item Name', 'Total Issued', 'Total Returned', 'Total Consumed', 'Total Wastage', 'Net Balance', 'Total Issued Value (Rs.)']);
                $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                    ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
                    ->selectRaw("department_id, item_id, 
                        SUM(CASE WHEN transaction_type = 'Issue' THEN qty_in ELSE 0 END) as total_issued,
                        SUM(CASE WHEN transaction_type = 'Return' THEN qty_out ELSE 0 END) as total_returned,
                        SUM(CASE WHEN transaction_type = 'Consumption' THEN qty_out ELSE 0 END) as total_consumed,
                        SUM(CASE WHEN transaction_type = 'Wastage' THEN qty_out ELSE 0 END) as total_wastage,
                        SUM(CASE WHEN transaction_type = 'Issue' THEN total_cost ELSE 0 END) as total_issued_value")
                    ->groupBy('department_id', 'item_id')
                    ->with(['department', 'inventoryItem']);
                if ($branchId) $query->where('branch_id', $branchId);
                if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);

                foreach ($query->lazy() as $row) {
                    $netBalance = $row->total_issued - $row->total_returned - $row->total_consumed - $row->total_wastage;
                    fputcsv($output, [
                        $row->department->name ?? 'N/A',
                        $row->inventoryItem->item_code ?? 'N/A',
                        $row->inventoryItem->name ?? 'N/A',
                        $row->total_issued,
                        $row->total_returned,
                        $row->total_consumed,
                        $row->total_wastage,
                        $netBalance,
                        number_format($row->total_issued_value ?? 0, 2, '.', ''),
                    ]);
                }
            }

            fclose($output);
        }, $fileName, $headers);
    }

    public function render()
    {
        $marqueeId = $this->getMarqueeId();
        $branchId = $this->getEffectiveBranchId();
        $startDateTime = $this->dateFrom ? $this->dateFrom . ' 00:00:00' : now()->startOfMonth()->format('Y-m-d 00:00:00');
        $endDateTime = $this->dateTo ? $this->dateTo . ' 23:59:59' : now()->format('Y-m-d 23:59:59');

        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        $departments = Department::where('marquee_id', $marqueeId);
        if ($branchId) $departments->where('branch_id', $branchId);
        $departments = $departments->orderBy('name', 'asc')->get();

        $branches = Branch::where('marquee_id', $marqueeId)->get();

        // Monetary & quantity summary metrics for stock issued (Today)
        $todayQuery = DepartmentStockLedger::where('marquee_id', $marqueeId)
            ->whereDate('transaction_date', today()->toDateString())
            ->where('transaction_type', 'Issue');
        if ($branchId) $todayQuery->where('branch_id', $branchId);
        if ($this->filterDepartment) $todayQuery->where('department_id', $this->filterDepartment);
        $todayIssuedValue = (float) $todayQuery->sum('total_cost');
        $todayIssuedQty = (float) $todayQuery->sum('qty_in');

        // Comprehensive Filtered Period Metrics for Stock Movements
        $consumptionAggregates = DepartmentStockLedger::where('marquee_id', $marqueeId)
            ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->selectRaw("
                COUNT(*) as total_rows,
                SUM(CASE WHEN qty_in > 0 THEN qty_in ELSE qty_out END) as total_qty,
                SUM(total_cost) as total_value,
                SUM(CASE WHEN transaction_type = 'Issue' THEN total_cost ELSE 0 END) as issued_value,
                SUM(CASE WHEN transaction_type = 'Issue' THEN qty_in ELSE 0 END) as issued_qty,
                SUM(CASE WHEN transaction_type = 'Return' THEN qty_out ELSE 0 END) as return_qty,
                SUM(CASE WHEN transaction_type = 'Consumption' THEN qty_out ELSE 0 END) as consumed_qty,
                SUM(CASE WHEN transaction_type = 'Wastage' THEN qty_out ELSE 0 END) as wastage_qty
            ")->first();

        $periodTotalRows = (int) ($consumptionAggregates->total_rows ?? 0);
        $periodTotalQty = (float) ($consumptionAggregates->total_qty ?? 0);
        $periodTotalValue = (float) ($consumptionAggregates->total_value ?? 0);
        $periodIssuedValue = (float) ($consumptionAggregates->issued_value ?? 0);
        $periodIssuedQty = (float) ($consumptionAggregates->issued_qty ?? 0);

        // Comprehensive Filtered Period Metrics for Department Stock Balance Summary
        $ledgerTotals = DepartmentStockLedger::where('marquee_id', $marqueeId)
            ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->selectRaw("
                SUM(CASE WHEN transaction_type = 'Issue' THEN qty_in ELSE 0 END) as total_issued,
                SUM(CASE WHEN transaction_type = 'Return' THEN qty_out ELSE 0 END) as total_returned,
                SUM(CASE WHEN transaction_type = 'Consumption' THEN qty_out ELSE 0 END) as total_consumed,
                SUM(CASE WHEN transaction_type = 'Wastage' THEN qty_out ELSE 0 END) as total_wastage,
                SUM(CASE WHEN transaction_type = 'Issue' THEN total_cost ELSE 0 END) as total_issued_value
            ")->first();

        $ledgerTotalIssuedQty = (float) ($ledgerTotals->total_issued ?? 0);
        $ledgerTotalReturnedQty = (float) ($ledgerTotals->total_returned ?? 0);
        $ledgerTotalConsumedQty = (float) ($ledgerTotals->total_consumed ?? 0);
        $ledgerTotalWastageQty = (float) ($ledgerTotals->total_wastage ?? 0);
        $ledgerTotalIssuedValue = (float) ($ledgerTotals->total_issued_value ?? 0);
        $ledgerNetBalance = $ledgerTotalIssuedQty - $ledgerTotalReturnedQty - $ledgerTotalConsumedQty - $ledgerTotalWastageQty;

        // Comprehensive Filtered Period Metrics for Kitchen Production
        $productionTotals = DepartmentProduction::where('marquee_id', $marqueeId)
            ->whereBetween('production_date', [$startDateTime, $endDateTime])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->selectRaw("
                COUNT(*) as total_batches,
                SUM(produced_qty) as total_produced,
                SUM(COALESCE(legacy_finished_good_wastage_qty, 0)) as total_wastage
            ")->first();

        // Comprehensive Filtered Period Metrics for Attendance
        $attendanceTotals = DepartmentAttendance::where('marquee_id', $marqueeId)
            ->whereBetween('date', [$startDateTime, $endDateTime])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->selectRaw("
                COUNT(*) as total_logs,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
            ")->first();

        $pageSize = ($this->perPage === 'all' || $this->perPage == -1) ? 5000 : (int) $this->perPage;
        $reportData = null;

        if ($this->reportType === 'consumption') {
            $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
                ->with(['department', 'inventoryItem']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->orderBy('transaction_date', 'desc')->paginate($pageSize);

        } elseif ($this->reportType === 'attendance') {
            $query = DepartmentAttendance::where('marquee_id', $marqueeId)
                ->whereBetween('date', [$startDateTime, $endDateTime])
                ->with(['department', 'employee']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->orderBy('date', 'desc')->paginate($pageSize);

        } elseif ($this->reportType === 'production') {
            $query = DepartmentProduction::where('marquee_id', $marqueeId)
                ->whereBetween('production_date', [$startDateTime, $endDateTime])
                ->with(['department', 'recipe', 'prepStaff']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->orderBy('production_date', 'desc')->paginate($pageSize);

        } elseif ($this->reportType === 'ledger_summary') {
            $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                ->whereBetween('transaction_date', [$startDateTime, $endDateTime])
                ->selectRaw("department_id, item_id, 
                    SUM(CASE WHEN transaction_type = 'Issue' THEN qty_in ELSE 0 END) as total_issued,
                    SUM(CASE WHEN transaction_type = 'Return' THEN qty_out ELSE 0 END) as total_returned,
                    SUM(CASE WHEN transaction_type = 'Consumption' THEN qty_out ELSE 0 END) as total_consumed,
                    SUM(CASE WHEN transaction_type = 'Wastage' THEN qty_out ELSE 0 END) as total_wastage,
                    SUM(CASE WHEN transaction_type = 'Issue' THEN total_cost ELSE 0 END) as total_issued_value")
                ->groupBy('department_id', 'item_id')
                ->with(['department', 'inventoryItem']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->paginate($pageSize);
        }

        return view('livewire.department-reports', [
            'reportData' => $reportData,
            'departments' => $departments,
            'branches' => $branches,
            'marquee' => $marquee,
            'todayIssuedValue' => $todayIssuedValue,
            'todayIssuedQty' => $todayIssuedQty,
            'periodIssuedValue' => $periodIssuedValue,
            'periodIssuedQty' => $periodIssuedQty,
            'periodTotalValue' => $periodTotalValue,
            'periodTotalQty' => $periodTotalQty,
            'periodTotalRows' => $periodTotalRows,
            'ledgerTotals' => $ledgerTotals,
            'ledgerTotalIssuedQty' => $ledgerTotalIssuedQty,
            'ledgerTotalReturnedQty' => $ledgerTotalReturnedQty,
            'ledgerTotalConsumedQty' => $ledgerTotalConsumedQty,
            'ledgerTotalWastageQty' => $ledgerTotalWastageQty,
            'ledgerTotalIssuedValue' => $ledgerTotalIssuedValue,
            'ledgerNetBalance' => $ledgerNetBalance,
            'productionTotals' => $productionTotals,
            'attendanceTotals' => $attendanceTotals,
        ])->layout('layouts.admin');
    }
}
