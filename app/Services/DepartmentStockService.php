<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DepartmentStockRequest;
use App\Models\DepartmentStockRequestItem;
use App\Models\DepartmentStockIssue;
use App\Models\DepartmentStockIssueItem;
use App\Models\DepartmentStockReturn;
use App\Models\DepartmentStockReturnItem;
use App\Models\DepartmentStockLedger;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class DepartmentStockService
{
    /**
     * Get the current stock balance of an item inside a department.
     */
    public function getDepartmentStockBalance(int $departmentId, int $itemId): float
    {
        $lastLedger = DepartmentStockLedger::where('department_id', $departmentId)
            ->where('item_id', $itemId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $lastLedger ? (float) $lastLedger->running_balance : 0.0;
    }

    /**
     * Get the current stock balance of an item in the Central Warehouse.
     */
    public function getCentralWarehouseStock(int $marqueeId, ?int $branchId, int $itemId): float
    {
        // 1. Total Received via GRN
        $grnQuery = DB::table('goods_receiving_note_details')
            ->join('goods_receiving_notes', 'goods_receiving_notes.id', '=', 'goods_receiving_note_details.goods_receiving_note_id')
            ->where('goods_receiving_note_details.item_id', $itemId)
            ->where('goods_receiving_notes.marquee_id', $marqueeId)
            ->whereNull('goods_receiving_notes.deleted_at');

        if ($branchId) {
            $grnQuery->where('goods_receiving_notes.branch_id', $branchId);
        }
        $totalReceived = $grnQuery->sum('goods_receiving_note_details.received_qty');

        // 2. Total Returned to Suppliers
        $returnQuery = DB::table('purchase_return_details')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_details.purchase_return_id')
            ->where('purchase_return_details.item_id', $itemId)
            ->where('purchase_returns.marquee_id', $marqueeId)
            ->where('purchase_returns.status', 'Posted')
            ->whereNull('purchase_returns.deleted_at');

        if ($branchId) {
            $returnQuery->where('purchase_returns.branch_id', $branchId);
        }
        $totalReturned = $returnQuery->sum('purchase_return_details.quantity');

        // 3. Total Issued to Departments
        $issuedQuery = DB::table('department_stock_issue_items')
            ->join('department_stock_issues', 'department_stock_issues.id', '=', 'department_stock_issue_items.department_stock_issue_id')
            ->where('department_stock_issue_items.item_id', $itemId)
            ->where('department_stock_issues.marquee_id', $marqueeId)
            ->whereNull('department_stock_issues.deleted_at');

        if ($branchId) {
            $issuedQuery->where('department_stock_issues.branch_id', $branchId);
        }
        $totalIssued = $issuedQuery->sum('department_stock_issue_items.quantity');

        // 4. Total Returned from Departments (Good stock only)
        $deptReturnQuery = DB::table('department_stock_return_items')
            ->join('department_stock_returns', 'department_stock_returns.id', '=', 'department_stock_return_items.department_stock_return_id')
            ->where('department_stock_return_items.item_id', $itemId)
            ->where('department_stock_returns.marquee_id', $marqueeId)
            ->where('department_stock_returns.status', 'Received')
            ->where('department_stock_return_items.status', 'Good')
            ->whereNull('department_stock_returns.deleted_at');

        if ($branchId) {
            $deptReturnQuery->where('department_stock_returns.branch_id', $branchId);
        }
        $totalDeptReturned = $deptReturnQuery->sum('department_stock_return_items.quantity');

        return (float) ($totalReceived - $totalReturned - $totalIssued + $totalDeptReturned);
    }

    /**
     * Issue stock to a department based on a stock request.
     */
    public function issueStock(
        DepartmentStockRequest $request,
        array $issueItems, // [item_id => quantity]
        int $userId,
        ?int $receiverEmployeeId = null
    ): DepartmentStockIssue {
        return DB::transaction(function () use ($request, $issueItems, $userId, $receiverEmployeeId) {
            $marqueeId = $request->marquee_id;
            $branchId = $request->branch_id;
            $departmentId = $request->department_id;

            // Generate Issue Number
            $issueCount = DepartmentStockIssue::where('marquee_id', $marqueeId)->count();
            $issueNumber = 'ISS-' . str_pad($issueCount + 1, 5, '0', STR_PAD_LEFT);

            // Create Issue Record
            $issue = DepartmentStockIssue::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'department_stock_request_id' => $request->id,
                'issue_number' => $issueNumber,
                'issue_date' => now()->format('Y-m-d'),
                'issued_by' => $userId,
                'received_by' => $receiverEmployeeId,
            ]);

            $fullyCompleted = true;

            foreach ($issueItems as $itemId => $qty) {
                if ($qty <= 0) {
                    continue;
                }

                $invItem = InventoryItem::findOrFail($itemId);
                $unitPrice = $invItem->default_purchase_rate ?: 1.0;

                // Create Issue Item
                DepartmentStockIssueItem::create([
                    'department_stock_issue_id' => $issue->id,
                    'item_id' => $itemId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                ]);

                // Create Department Ledger Entry (qty_in increases running balance)
                $prevBalance = $this->getDepartmentStockBalance($departmentId, $itemId);
                $newBalance = $prevBalance + $qty;

                DepartmentStockLedger::create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'item_id' => $itemId,
                    'transaction_date' => now()->format('Y-m-d'),
                    'transaction_type' => 'Issue',
                    'reference_type' => 'DepartmentStockIssue',
                    'reference_id' => $issue->id,
                    'qty_in' => $qty,
                    'qty_out' => 0,
                    'running_balance' => $newBalance,
                    'unit_price' => $unitPrice,
                    'total_cost' => $qty * $unitPrice,
                    'created_by' => $userId,
                ]);

                // Update Request Item status
                $requestItem = DepartmentStockRequestItem::where('department_stock_request_id', $request->id)
                    ->where('item_id', $itemId)
                    ->first();

                if ($requestItem) {
                    $newIssuedQty = $requestItem->issued_qty + $qty;
                    $requestItem->update([
                        'issued_qty' => $newIssuedQty,
                    ]);

                    if ($newIssuedQty < $requestItem->approved_qty) {
                        $fullyCompleted = false;
                    }
                }
            }

            // Update request status
            $request->update([
                'status' => $fullyCompleted ? 'Completed' : 'Partially Issued',
            ]);

            return $issue;
        });
    }

    /**
     * Process return of stock from a department.
     */
    public function returnStock(
        Department $department,
        array $returnItems, // [item_id => ['quantity' => x, 'status' => 'Good'/'Damaged']]
        int $userId,
        int $returnedByEmployeeId
    ): DepartmentStockReturn {
        return DB::transaction(function () use ($department, $returnItems, $userId, $returnedByEmployeeId) {
            $marqueeId = $department->marquee_id;
            $branchId = $department->branch_id;
            $departmentId = $department->id;

            // Generate Return Number
            $returnCount = DepartmentStockReturn::where('marquee_id', $marqueeId)->count();
            $returnNumber = 'RET-' . str_pad($returnCount + 1, 5, '0', STR_PAD_LEFT);

            // Create Return Record
            $ret = DepartmentStockReturn::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'return_number' => $returnNumber,
                'return_date' => now()->format('Y-m-d'),
                'returned_by' => $returnedByEmployeeId,
                'received_by' => $userId,
                'status' => 'Received', // Auto-receive return
            ]);

            foreach ($returnItems as $itemId => $data) {
                $qty = (float) $data['quantity'];
                if ($qty <= 0) {
                    continue;
                }

                $status = $data['status'] ?? 'Good';
                $invItem = InventoryItem::findOrFail($itemId);
                $unitPrice = $invItem->default_purchase_rate ?: 1.0;

                // Create Return Item
                DepartmentStockReturnItem::create([
                    'department_stock_return_id' => $ret->id,
                    'item_id' => $itemId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'status' => $status,
                ]);

                // Create Department Ledger Entry (qty_out decreases running balance)
                $prevBalance = $this->getDepartmentStockBalance($departmentId, $itemId);
                $newBalance = $prevBalance - $qty;

                DepartmentStockLedger::create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'item_id' => $itemId,
                    'transaction_date' => now()->format('Y-m-d'),
                    'transaction_type' => 'Return',
                    'reference_type' => 'DepartmentStockReturn',
                    'reference_id' => $ret->id,
                    'qty_in' => 0,
                    'qty_out' => $qty,
                    'running_balance' => $newBalance,
                    'unit_price' => $unitPrice,
                    'total_cost' => $qty * $unitPrice,
                    'created_by' => $userId,
                ]);
            }

            return $ret;
        });
    }

    /**
     * Record inventory consumption/wastage inside the department.
     */
    public function recordConsumption(
        Department $department,
        array $consumeItems, // [item_id => quantity]
        int $userId,
        string $type = 'Consumption' // Consumption, Wastage
    ): void {
        DB::transaction(function () use ($department, $consumeItems, $userId, $type) {
            $marqueeId = $department->marquee_id;
            $branchId = $department->branch_id;
            $departmentId = $department->id;

            foreach ($consumeItems as $itemId => $qty) {
                if ($qty <= 0) {
                    continue;
                }

                $invItem = InventoryItem::findOrFail($itemId);
                $unitPrice = $invItem->default_purchase_rate ?: 1.0;

                $prevBalance = $this->getDepartmentStockBalance($departmentId, $itemId);
                $newBalance = $prevBalance - $qty;

                // Create Department Ledger Entry
                DepartmentStockLedger::create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branchId,
                    'department_id' => $departmentId,
                    'item_id' => $itemId,
                    'transaction_date' => now()->format('Y-m-d'),
                    'transaction_type' => $type,
                    'qty_in' => 0,
                    'qty_out' => $qty,
                    'running_balance' => $newBalance,
                    'unit_price' => $unitPrice,
                    'total_cost' => $qty * $unitPrice,
                    'created_by' => $userId,
                ]);
            }
        });
    }
}
