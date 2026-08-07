<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\DepartmentAttendance;
use App\Models\DepartmentProduction;
use App\Models\DepartmentStockIssue;
use App\Models\DepartmentStockLedger;
use App\Models\DepartmentStockRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DepartmentDashboard extends Component
{
    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $branchId = auth()->user()->branch_id;

        // Base queries
        $deptQuery = Department::where('marquee_id', $marqueeId);
        $empQuery = Employee::where('marquee_id', $marqueeId);
        $reqQuery = DepartmentStockRequest::where('marquee_id', $marqueeId);
        $issueQuery = DepartmentStockIssue::where('marquee_id', $marqueeId);
        $prodQuery = DepartmentProduction::where('marquee_id', $marqueeId);
        $attQuery = DepartmentAttendance::where('marquee_id', $marqueeId)->where('date', now()->format('Y-m-d'));

        if ($branchId) {
            $deptQuery->where('branch_id', $branchId);
            $empQuery->where('branch_id', $branchId);
            $reqQuery->where('branch_id', $branchId);
            $issueQuery->where('branch_id', $branchId);
            $prodQuery->where('branch_id', $branchId);
            $attQuery->where('branch_id', $branchId);
        }

        // Metrics
        $totalDepartments = (clone $deptQuery)->count();
        $totalEmployees = (clone $empQuery)->whereNotNull('department_id')->count();

        $todayExpectedAttendance = (clone $empQuery)->whereNotNull('department_id')->where('status', 'active')->count();
        $todayPresentCount = (clone $attQuery)->where('status', 'Present')->count();
        $attendancePercentage = $todayExpectedAttendance > 0 ? round(($todayPresentCount / $todayExpectedAttendance) * 100, 1) : 0;

        $pendingRequestsCount = (clone $reqQuery)->whereIn('status', ['Pending', 'Submitted'])->count();
        $approvedRequestsCount = (clone $reqQuery)->where('status', 'Approved')->count();

        $todayDispatches = (clone $issueQuery)->where('issue_date', now()->format('Y-m-d'))->count();
        $todayProductionBatches = (clone $prodQuery)->where('production_date', now()->format('Y-m-d'))->count();

        // Recent Requests
        $recentRequests = (clone $reqQuery)->with(['department', 'requester'])
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // Top Consuming Departments (from ledgers)
        $topConsumingDepartments = DepartmentStockLedger::where('marquee_id', $marqueeId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('transaction_type', 'Issue')
            ->select('department_id', DB::raw('SUM(qty_in) as total_issued_units'), DB::raw('COUNT(DISTINCT item_id) as item_types'))
            ->groupBy('department_id')
            ->with('department')
            ->orderByDesc('total_issued_units')
            ->take(5)
            ->get();

        // Departments overview table
        $departmentsOverview = (clone $deptQuery)->with(['manager', 'employees'])
            ->withCount(['employees', 'stockRequests' => function ($q) {
                $q->whereIn('status', ['Pending', 'Submitted']);
            }])
            ->orderBy('display_order', 'asc')
            ->get();

        return view('livewire.department-dashboard', [
            'totalDepartments' => $totalDepartments,
            'totalEmployees' => $totalEmployees,
            'todayPresentCount' => $todayPresentCount,
            'todayExpectedAttendance' => $todayExpectedAttendance,
            'attendancePercentage' => $attendancePercentage,
            'pendingRequestsCount' => $pendingRequestsCount,
            'approvedRequestsCount' => $approvedRequestsCount,
            'todayDispatches' => $todayDispatches,
            'todayProductionBatches' => $todayProductionBatches,
            'recentRequests' => $recentRequests,
            'topConsumingDepartments' => $topConsumingDepartments,
            'departmentsOverview' => $departmentsOverview,
        ])->layout('layouts.admin');
    }
}
