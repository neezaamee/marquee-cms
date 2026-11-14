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
        $this->filterBranch = auth()->user()->branch_id ?: '';
    }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
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
        $branchId = $this->filterBranch ?: auth()->user()->branch_id;

        $fileName = 'department-' . $this->reportType . '-report-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($marqueeId, $branchId) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fputs($output, "\xEF\xBB\xBF");

            if ($this->reportType === 'consumption') {
                fputcsv($output, ['Date', 'Department', 'Item Code', 'Item Name', 'Transaction Type', 'Quantity', 'Unit Rate (Rs.)', 'Total Value (Rs.)', 'Reference']);
                $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                    ->whereBetween('transaction_date', [$this->dateFrom, $this->dateTo])
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
                    ->whereBetween('date', [$this->dateFrom, $this->dateTo])
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
                    ->whereBetween('production_date', [$this->dateFrom, $this->dateTo])
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
                        $row->wastage_qty,
                        $row->prepStaff->name ?? 'System',
                    ]);
                }
            } elseif ($this->reportType === 'ledger_summary') {
                fputcsv($output, ['Department', 'Item Code', 'Item Name', 'Total Issued', 'Total Returned', 'Total Consumed', 'Total Wastage', 'Net Balance', 'Total Issued Value (Rs.)']);
                $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                    ->whereBetween('transaction_date', [$this->dateFrom, $this->dateTo])
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
        $branchId = $this->filterBranch ?: auth()->user()->branch_id;

        $marquee = $marqueeId ? Marquee::find($marqueeId) : null;

        $departments = Department::where('marquee_id', $marqueeId);
        if ($branchId) $departments->where('branch_id', $branchId);
        $departments = $departments->orderBy('name', 'asc')->get();

        $branches = Branch::where('marquee_id', $marqueeId)->get();

        // Monetary & quantity summary metrics for stock issued
        $todayQuery = DepartmentStockLedger::where('marquee_id', $marqueeId)
            ->where('transaction_date', today()->toDateString())
            ->where('transaction_type', 'Issue');
        if ($branchId) $todayQuery->where('branch_id', $branchId);
        if ($this->filterDepartment) $todayQuery->where('department_id', $this->filterDepartment);
        $todayIssuedValue = (float) $todayQuery->sum('total_cost');
        $todayIssuedQty = (float) $todayQuery->sum('qty_in');

        $periodQuery = DepartmentStockLedger::where('marquee_id', $marqueeId)
            ->whereBetween('transaction_date', [$this->dateFrom, $this->dateTo])
            ->where('transaction_type', 'Issue');
        if ($branchId) $periodQuery->where('branch_id', $branchId);
        if ($this->filterDepartment) $periodQuery->where('department_id', $this->filterDepartment);
        $periodIssuedValue = (float) $periodQuery->sum('total_cost');
        $periodIssuedQty = (float) $periodQuery->sum('qty_in');

        $pageSize = ($this->perPage === 'all' || $this->perPage == -1) ? 5000 : (int) $this->perPage;
        $reportData = null;

        if ($this->reportType === 'consumption') {
            $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                ->whereBetween('transaction_date', [$this->dateFrom, $this->dateTo])
                ->with(['department', 'inventoryItem']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->orderBy('transaction_date', 'desc')->paginate($pageSize);

        } elseif ($this->reportType === 'attendance') {
            $query = DepartmentAttendance::where('marquee_id', $marqueeId)
                ->whereBetween('date', [$this->dateFrom, $this->dateTo])
                ->with(['department', 'employee']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->orderBy('date', 'desc')->paginate($pageSize);

        } elseif ($this->reportType === 'production') {
            $query = DepartmentProduction::where('marquee_id', $marqueeId)
                ->whereBetween('production_date', [$this->dateFrom, $this->dateTo])
                ->with(['department', 'recipe', 'prepStaff']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->orderBy('production_date', 'desc')->paginate($pageSize);

        } elseif ($this->reportType === 'ledger_summary') {
            $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                ->whereBetween('transaction_date', [$this->dateFrom, $this->dateTo])
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
        ])->layout('layouts.admin');
    }
}
