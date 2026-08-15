<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentAttendance;
use App\Models\DepartmentProduction;
use App\Models\DepartmentStockLedger;
use App\Models\InventoryItem;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentReports extends Component
{
    use WithPagination;

    public $reportType = 'consumption'; // 'consumption', 'attendance', 'production', 'ledger_summary'
    public $dateFrom;
    public $dateTo;
    public $filterDepartment = '';
    public $filterBranch = '';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->filterBranch = auth()->user()->branch_id ?: '';
    }

    public function updatedReportType()
    {
        $this->resetPage();
    }

    public function exportCsv()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = $this->filterBranch ?: auth()->user()->branch_id;

        $fileName = 'department-' . $this->reportType . '-report-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $output = fopen('php://output', 'w');

        if ($this->reportType === 'consumption') {
            fputcsv($output, ['Date', 'Department', 'Item Code', 'Item Name', 'Transaction Type', 'Quantity', 'Reference']);
            $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                ->whereBetween('transaction_date', [$this->dateFrom, $this->dateTo])
                ->with(['department', 'inventoryItem']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);

            foreach ($query->lazy() as $row) {
                $qty = $row->qty_in > 0 ? $row->qty_in : $row->qty_out;
                $ref = $row->reference_type ? (str_replace(['App\\Models\\', 'DepartmentStock'], '', $row->reference_type) . ' #' . $row->reference_id) : '—';
                fputcsv($output, [
                    $row->transaction_date->format('Y-m-d'),
                    $row->department->name ?? 'N/A',
                    $row->inventoryItem->item_code ?? 'N/A',
                    $row->inventoryItem->name ?? 'N/A',
                    $row->transaction_type,
                    $qty,
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
                    $row->date->format('Y-m-d'),
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
                    $row->production_date->format('Y-m-d'),
                    $row->batch_number,
                    $row->department->name ?? 'N/A',
                    $row->recipe->name ?? 'N/A',
                    $row->produced_qty,
                    $row->wastage_qty,
                    $row->prepStaff->name ?? 'System',
                ]);
            }
        }

        fclose($output);
        exit;
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = $this->filterBranch ?: auth()->user()->branch_id;

        $departments = Department::where('marquee_id', $marqueeId);
        if ($branchId) $departments->where('branch_id', $branchId);
        $departments = $departments->orderBy('name', 'asc')->get();

        $branches = Branch::where('marquee_id', $marqueeId)->get();

        $reportData = null;

        if ($this->reportType === 'consumption') {
            $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                ->whereBetween('transaction_date', [$this->dateFrom, $this->dateTo])
                ->with(['department', 'inventoryItem']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->orderBy('transaction_date', 'desc')->paginate(15);

        } elseif ($this->reportType === 'attendance') {
            $query = DepartmentAttendance::where('marquee_id', $marqueeId)
                ->whereBetween('date', [$this->dateFrom, $this->dateTo])
                ->with(['department', 'employee']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->orderBy('date', 'desc')->paginate(15);

        } elseif ($this->reportType === 'production') {
            $query = DepartmentProduction::where('marquee_id', $marqueeId)
                ->whereBetween('production_date', [$this->dateFrom, $this->dateTo])
                ->with(['department', 'recipe', 'prepStaff']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->orderBy('production_date', 'desc')->paginate(15);

        } elseif ($this->reportType === 'ledger_summary') {
            $query = DepartmentStockLedger::where('marquee_id', $marqueeId)
                ->whereBetween('transaction_date', [$this->dateFrom, $this->dateTo])
                ->selectRaw('department_id, item_id, 
                    SUM(CASE WHEN transaction_type = "Issue" THEN qty_in ELSE 0 END) as total_issued,
                    SUM(CASE WHEN transaction_type = "Return" THEN qty_out ELSE 0 END) as total_returned,
                    SUM(CASE WHEN transaction_type = "Consumption" THEN qty_out ELSE 0 END) as total_consumed,
                    SUM(CASE WHEN transaction_type = "Wastage" THEN qty_out ELSE 0 END) as total_wastage')
                ->groupBy('department_id', 'item_id')
                ->with(['department', 'inventoryItem']);
            if ($branchId) $query->where('branch_id', $branchId);
            if ($this->filterDepartment) $query->where('department_id', $this->filterDepartment);
            $reportData = $query->paginate(15);
        }

        return view('livewire.department-reports', [
            'reportData' => $reportData,
            'departments' => $departments,
            'branches' => $branches,
        ])->layout('layouts.admin');
    }
}
