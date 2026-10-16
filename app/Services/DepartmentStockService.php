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
        $query = \App\Models\InventoryStockLedger::where('marquee_id', $marqueeId)
            ->where('item_id', $itemId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return (float) ($query->sum('qty_in') - $query->sum('qty_out'));
    }

    /**
     * Get the last purchase rate for an inventory item.
     * Looks up the most recent PurchaseInvoiceDetail for the item,
     * then recent InventoryStockLedger stock-in rates,
     * falling back to default_purchase_rate if no prior purchase exists.
     */
    public function getLastPurchaseRate(int $marqueeId, ?int $branchId, int $itemId): float
    {
        // 1. Look for last purchase invoice detail for this item in this branch/marquee
        $purchaseQuery = \App\Models\PurchaseInvoiceDetail::where('purchase_invoice_details.item_id', $itemId)
            ->whereHas('purchaseInvoice', function ($q) use ($marqueeId, $branchId) {
                $q->where('marquee_id', $marqueeId)
                  ->where('status', '!=', 'Cancelled');
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->join('purchase_invoices', 'purchase_invoice_details.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->orderBy('purchase_invoices.purchase_date', 'desc')
            ->orderBy('purchase_invoices.id', 'desc');

        $lastDetail = $purchaseQuery->first(['purchase_invoice_details.unit_cost']);
        if ($lastDetail && (float) $lastDetail->unit_cost > 0) {
            return (float) $lastDetail->unit_cost;
        }

        // 1b. If branch-specific check had no result, check marquee-wide purchase invoices
        if ($branchId) {
            $lastDetailMarquee = \App\Models\PurchaseInvoiceDetail::where('purchase_invoice_details.item_id', $itemId)
                ->whereHas('purchaseInvoice', function ($q) use ($marqueeId) {
                    $q->where('marquee_id', $marqueeId)
                      ->where('status', '!=', 'Cancelled');
                })
                ->join('purchase_invoices', 'purchase_invoice_details.purchase_invoice_id', '=', 'purchase_invoices.id')
                ->orderBy('purchase_invoices.purchase_date', 'desc')
                ->orderBy('purchase_invoices.id', 'desc')
                ->first(['purchase_invoice_details.unit_cost']);

            if ($lastDetailMarquee && (float) $lastDetailMarquee->unit_cost > 0) {
                return (float) $lastDetailMarquee->unit_cost;
            }
        }

        // 2. Look for last stock-in entry with rate in InventoryStockLedger
        $ledgerQuery = \App\Models\InventoryStockLedger::where('marquee_id', $marqueeId)
            ->where('item_id', $itemId)
            ->where('qty_in', '>', 0)
            ->where('unit_price', '>', 0);
        if ($branchId) {
            $ledgerQuery->where('branch_id', $branchId);
        }
        $lastLedger = $ledgerQuery->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastLedger && (float) $lastLedger->unit_price > 0) {
            return (float) $lastLedger->unit_price;
        }

        // 3. Fallback to catalog item default_purchase_rate
        $item = InventoryItem::find($itemId);
        return $item ? (float) ($item->default_purchase_rate ?: 1.0) : 1.0;
    }

    /**
     * Issue stock to a department based on a stock request.
     */
    public function issueStock(
        DepartmentStockRequest $request,
        array $issueItems, // [item_id => quantity]
        int $userId,
        ?int $receiverEmployeeId = null,
        array $customRates = []
    ): DepartmentStockIssue {
        return DB::transaction(function () use ($request, $issueItems, $userId, $receiverEmployeeId, $customRates) {
            $marqueeId = $request->marquee_id;
            $branchId = $request->branch_id;
            $departmentId = $request->department_id;

            // Generate Issue Number safely
            $maxId = DepartmentStockIssue::withTrashed()->where('marquee_id', $marqueeId)->max('id') ?? 0;
            $nextNum = $maxId + 1;
            $issueNumber = 'ISS-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
            while (DepartmentStockIssue::withTrashed()->where('marquee_id', $marqueeId)->where('issue_number', $issueNumber)->exists()) {
                $nextNum++;
                $issueNumber = 'ISS-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
            }

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

                // Use custom rate if passed, otherwise fetch the last purchase rate
                $unitPrice = 0.0;
                if (isset($customRates[$itemId]) && (float) $customRates[$itemId] > 0) {
                    $unitPrice = (float) $customRates[$itemId];
                }
                if ($unitPrice <= 0) {
                    $unitPrice = $this->getLastPurchaseRate($marqueeId, $branchId, $itemId);
                }
                if ($unitPrice <= 0) {
                    $unitPrice = (float) ($invItem->default_purchase_rate ?: 1.0);
                }

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

                // Log Central Store OUT movement (idempotency guarded)
                $centralAlreadyLogged = \App\Models\InventoryStockLedger::where('marquee_id', $marqueeId)
                    ->where('branch_id', $branchId)
                    ->where('item_id', $itemId)
                    ->where('transaction_type', 'Issue')
                    ->where('reference_type', 'App\\Models\\DepartmentStockIssue')
                    ->where('reference_id', $issue->id)
                    ->exists();

                if (!$centralAlreadyLogged) {
                    $prevCentralBalance = $this->getCentralWarehouseStock($marqueeId, $branchId, $itemId);
                    $newCentralBalance = $prevCentralBalance - $qty;

                    \App\Models\InventoryStockLedger::create([
                        'marquee_id'       => $marqueeId,
                        'branch_id'        => $branchId,
                        'item_id'          => $itemId,
                        'transaction_date' => now()->format('Y-m-d'),
                        'transaction_type' => 'Issue',
                        'reference_type'   => 'App\\Models\\DepartmentStockIssue',
                        'reference_id'     => $issue->id,
                        'qty_in'           => 0.00,
                        'qty_out'          => $qty,
                        'running_balance'  => $newCentralBalance,
                        'unit_price'       => $unitPrice,
                        'total_cost'       => $qty * $unitPrice,
                        'created_by'       => $userId,
                    ]);
                }

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

                if ($status === 'Good') {
                    // Log Central Store IN movement (idempotency guarded)
                    $centralAlreadyLogged = \App\Models\InventoryStockLedger::where('marquee_id', $marqueeId)
                        ->where('branch_id', $branchId)
                        ->where('item_id', $itemId)
                        ->where('transaction_type', 'Return')
                        ->where('reference_type', 'App\\Models\\DepartmentStockReturn')
                        ->where('reference_id', $ret->id)
                        ->exists();

                    if (!$centralAlreadyLogged) {
                        $prevCentralBalance = $this->getCentralWarehouseStock($marqueeId, $branchId, $itemId);
                        $newCentralBalance = $prevCentralBalance + $qty;

                        \App\Models\InventoryStockLedger::create([
                            'marquee_id'       => $marqueeId,
                            'branch_id'        => $branchId,
                            'item_id'          => $itemId,
                            'transaction_date' => now()->format('Y-m-d'),
                            'transaction_type' => 'Return',
                            'reference_type'   => 'App\\Models\\DepartmentStockReturn',
                            'reference_id'     => $ret->id,
                            'qty_in'           => $qty,
                            'qty_out'          => 0.00,
                            'running_balance'  => $newCentralBalance,
                            'unit_price'       => $unitPrice,
                            'total_cost'       => $qty * $unitPrice,
                            'created_by'       => $userId,
                        ]);
                    }
                }
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
