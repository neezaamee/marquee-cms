<?php

namespace App\Services;

use App\Models\GoodsReceivingNote;
use App\Models\GoodsReceivingNoteDetail;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseReturn;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseService
{
    protected $accountingService;
    protected $inventoryService;

    public function __construct(AccountingService $accountingService, InventoryService $inventoryService)
    {
        $this->accountingService = $accountingService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Approve a Purchase Order.
     */
    public function approvePurchaseOrder(int $poId): PurchaseOrder
    {
        return DB::transaction(function () use ($poId) {
            $po = PurchaseOrder::findOrFail($poId);

            if ($po->status !== 'Draft') {
                throw new InvalidArgumentException("Only Draft Purchase Orders can be approved.");
            }

            $po->update(['status' => 'Approved']);
            return $po;
        });
    }

    /**
     * Record a Goods Receiving Note (GRN) against a Purchase Order.
     */
    public function recordGoodsReceipt(int $poId, array $grnData, array $receivedItems): GoodsReceivingNote
    {
        return DB::transaction(function () use ($poId, $grnData, $receivedItems) {
            $po = PurchaseOrder::with('details')->findOrFail($poId);

            if (!in_array($po->status, ['Approved', 'Partially Received'])) {
                throw new InvalidArgumentException("Goods can only be received against Approved or Partially Received POs.");
            }

            $marqueeId = $po->marquee_id;
            $branchId = $po->branch_id;

            // 1. Generate unique GRN number
            $grnPrefix = 'GRN-' . date('Ymd');
            $count = GoodsReceivingNote::withTrashed()->where('marquee_id', $marqueeId)->count();
            $grnNumber = $grnPrefix . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);

            // 2. Create GRN Header
            $grn = GoodsReceivingNote::create([
                'marquee_id' => $marqueeId,
                'branch_id' => $branchId,
                'purchase_order_id' => $poId,
                'grn_number' => $grnNumber,
                'supplier_id' => $po->supplier_id,
                'received_date' => $grnData['received_date'],
                'notes' => $grnData['notes'] ?? null,
            ]);

            $allCompleted = true;

            // 3. Process item details
            foreach ($receivedItems as $recItem) {
                $itemId = $recItem['item_id'];
                $receivedQty = (float) $recItem['received_qty'];

                if ($receivedQty <= 0) {
                    continue; // Skip zero deliveries
                }

                // Find matching PO detail row
                $poDetail = $po->details->firstWhere('item_id', $itemId);

                if (!$poDetail) {
                    throw new InvalidArgumentException("Item (ID: {$itemId}) does not exist in the reference Purchase Order.");
                }

                // Create GRN item
                GoodsReceivingNoteDetail::create([
                    'goods_receiving_note_id' => $grn->id,
                    'item_id' => $itemId,
                    'ordered_qty' => $poDetail->quantity,
                    'received_qty' => $receivedQty,
                ]);

                // Update PO detail quantity
                $newReceivedQty = $poDetail->received_quantity + $receivedQty;
                $poDetail->update(['received_quantity' => $newReceivedQty]);

                // Compute stock values (needed for running_balance even with idempotency check)
                $stockService = app(\App\Services\DepartmentStockService::class);
                $invItem = \App\Models\InventoryItem::findOrFail($itemId);
                $unitCost = $invItem->average_cost ?: ($invItem->default_purchase_rate ?: 1.0);
                $prevCentralBalance = $stockService->getCentralWarehouseStock($marqueeId, $branchId, $itemId);
                $newCentralBalance = $prevCentralBalance + $receivedQty;

                // Check for duplicate ledger entry (idempotency guard)
                $alreadyLogged = \App\Models\InventoryStockLedger::where('marquee_id', $marqueeId)
                    ->where('branch_id', $branchId)
                    ->where('item_id', $itemId)
                    ->where('transaction_type', 'GRN')
                    ->where('reference_type', 'App\\Models\\GoodsReceivingNote')
                    ->where('reference_id', $grn->id)
                    ->exists();

                if (!$alreadyLogged) {
                    \App\Models\InventoryStockLedger::create([
                        'marquee_id'       => $marqueeId,
                        'branch_id'        => $branchId,
                        'item_id'          => $itemId,
                        'transaction_date' => $grnData['received_date'],
                        'transaction_type' => 'GRN',
                        'reference_type'   => 'App\\Models\\GoodsReceivingNote',
                        'reference_id'     => $grn->id,
                        'qty_in'           => $receivedQty,
                        'qty_out'          => 0.00,
                        'running_balance'  => $newCentralBalance,
                        'unit_price'       => $unitCost,
                        'total_cost'       => $receivedQty * $unitCost,
                        'created_by'       => auth()->id(),
                    ]);
                }
            }

            // Reload details to verify overall statuses
            $po->load('details');
            foreach ($po->details as $detail) {
                if ($detail->received_quantity < $detail->quantity) {
                    $allCompleted = false;
                }
            }

            // 4. Transition status
            $newStatus = $allCompleted ? 'Completed' : 'Partially Received';
            $po->update(['status' => $newStatus]);

            return $grn;
        });
    }

    /**
     * Post a Purchase Invoice to double-entry ledger accounts.
     */
    public function postPurchaseInvoice(int $invoiceId): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoiceId) {
            $invoice = PurchaseInvoice::findOrFail($invoiceId);

            if (in_array($invoice->status, ['Posted', 'Cancelled'])) {
                throw new InvalidArgumentException("This invoice has already been {$invoice->status}.");
            }

            $marqueeId = $invoice->marquee_id;

            // 1. Fetch configurable settings
            $settings = $this->inventoryService->getOrCreateSettings($marqueeId);
            $assetAccId = $settings->inventory_asset_account_id;
            $payableAccId = $settings->accounts_payable_account_id;

            if (!$assetAccId || !$payableAccId) {
                throw new InvalidArgumentException("Account mappings are not configured in Inventory Settings.");
            }

            // 2. Build double-entry Journal Voucher
            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $invoice->branch_id,
                'voucher_date' => $invoice->purchase_date->format('Y-m-d'),
                'reference' => $invoice->invoice_number,
                'notes' => "Auto-posted Purchase Invoice: {$invoice->invoice_number}. " . $invoice->notes,
                'status' => 'posted', // Mark JV as posted directly
            ];

            $items = [
                [
                    'account_id' => $assetAccId,
                    'debit' => $invoice->net_amount,
                    'credit' => 0.00,
                    'narration' => "Inventory Assets Addition - Invoice #{$invoice->invoice_number}",
                ],
                [
                    'account_id' => $payableAccId,
                    'debit' => 0.00,
                    'credit' => $invoice->net_amount,
                    'narration' => "Accounts Payable Addition - Invoice #{$invoice->invoice_number}",
                ]
            ];

            $voucher = $this->accountingService->createJournalVoucher($header, $items);

            // 3. Post transaction to supplier's ledger
            $this->inventoryService->recordSupplierTransaction(
                $marqueeId,
                $invoice->supplier_id,
                $invoice->purchase_date->format('Y-m-d'),
                0.00, // Debit
                $invoice->net_amount, // Credit (Increases payable)
                'PurchaseInvoice',
                $invoice->id,
                $voucher->voucher_no,
                "Billed under Purchase Invoice: #{$invoice->invoice_number}"
            );

            // 4. Calculate and update Weighted Average Cost (WAC) for each item in the invoice
            $invoice->load('details');
            $stockService = app(\App\Services\DepartmentStockService::class);
            foreach ($invoice->details as $detail) {
                $item = \App\Models\InventoryItem::findOrFail($detail->item_id);
                
                $currentStock = $stockService->getCentralWarehouseStock($marqueeId, $invoice->branch_id, $detail->item_id);
                $currentWac = (float) $item->average_cost ?: ((float) $item->default_purchase_rate ?: 0.0);
                $invoicedQty = (float) $detail->quantity;
                $invoicedUnitCost = (float) $detail->unit_cost;

                $totalQty = $currentStock;
                $prevStock = $currentStock - $invoicedQty;
                if ($totalQty > 0) {
                    $newWac = (($prevStock * $currentWac) + ($invoicedQty * $invoicedUnitCost)) / $totalQty;
                } else {
                    $newWac = $invoicedUnitCost;
                }

                $item->update([
                    'average_cost' => $newWac,
                    'last_purchase_cost' => $invoicedUnitCost,
                ]);
            }

            // 5. Update Invoice Status
            $invoice->update([
                'status' => 'Posted',
                'journal_voucher_id' => $voucher->id,
            ]);

            return $invoice;
        });
    }

    /**
     * Post a Purchase Return to double-entry ledger accounts.
     */
    public function postPurchaseReturn(int $returnId): PurchaseReturn
    {
        return DB::transaction(function () use ($returnId) {
            $return = PurchaseReturn::findOrFail($returnId);

            if (in_array($return->status, ['Posted', 'Cancelled'])) {
                throw new InvalidArgumentException("This return has already been {$return->status}.");
            }

            $marqueeId = $return->marquee_id;

            // 1. Fetch settings
            $settings = $this->inventoryService->getOrCreateSettings($marqueeId);
            $assetAccId = $settings->inventory_asset_account_id;
            $payableAccId = $settings->accounts_payable_account_id;

            if (!$assetAccId || !$payableAccId) {
                throw new InvalidArgumentException("Account mappings are not configured in Inventory Settings.");
            }

            // 2. Build double-entry Journal Voucher
            $header = [
                'marquee_id' => $marqueeId,
                'branch_id' => $return->branch_id,
                'voucher_date' => $return->return_date->format('Y-m-d'),
                'reference' => $return->return_number,
                'notes' => "Auto-posted Purchase Return: {$return->return_number}. " . $return->notes,
                'status' => 'posted',
            ];

            $items = [
                [
                    'account_id' => $payableAccId,
                    'debit' => $return->net_amount, // Debit decreases liabilities
                    'credit' => 0.00,
                    'narration' => "Accounts Payable Debit - Return #{$return->return_number}",
                ],
                [
                    'account_id' => $assetAccId,
                    'debit' => 0.00,
                    'credit' => $return->net_amount, // Credit decreases assets
                    'narration' => "Inventory Assets Credit - Return #{$return->return_number}",
                ]
            ];

            $voucher = $this->accountingService->createJournalVoucher($header, $items);

            // 3. Post transaction to supplier's ledger
            $this->inventoryService->recordSupplierTransaction(
                $marqueeId,
                $return->supplier_id,
                $return->return_date->format('Y-m-d'),
                $return->net_amount, // Debit (Decreases payable)
                0.00, // Credit
                'PurchaseReturn',
                $return->id,
                $voucher->voucher_no,
                "Returned under Purchase Return: #{$return->return_number}"
            );

            // Update Central Store Stock Ledger for returned items (idempotency guarded)
            $stockService = app(\App\Services\DepartmentStockService::class);
            $return->load('details.item');
            foreach ($return->details as $detail) {
                $alreadyLogged = \App\Models\InventoryStockLedger::where('marquee_id', $marqueeId)
                    ->where('branch_id', $return->branch_id)
                    ->where('item_id', $detail->item_id)
                    ->where('transaction_type', 'PurchaseReturn')
                    ->where('reference_type', 'App\\Models\\PurchaseReturn')
                    ->where('reference_id', $return->id)
                    ->exists();

                if (!$alreadyLogged) {
                    $prevCentralBalance = $stockService->getCentralWarehouseStock($marqueeId, $return->branch_id, $detail->item_id);
                    $newCentralBalance = $prevCentralBalance - $detail->quantity;

                    \App\Models\InventoryStockLedger::create([
                        'marquee_id'       => $marqueeId,
                        'branch_id'        => $return->branch_id,
                        'item_id'          => $detail->item_id,
                        'transaction_date' => $return->return_date->format('Y-m-d'),
                        'transaction_type' => 'PurchaseReturn',
                        'reference_type'   => 'App\\Models\\PurchaseReturn',
                        'reference_id'     => $return->id,
                        'qty_in'           => 0.00,
                        'qty_out'          => $detail->quantity,
                        'running_balance'  => $newCentralBalance,
                        'unit_price'       => $detail->unit_cost,
                        'total_cost'       => $detail->amount,
                        'created_by'       => auth()->id(),
                    ]);
                }
            }

            // 4. Update Return Status
            $return->update([
                'status' => 'Posted',
                'journal_voucher_id' => $voucher->id,
            ]);

            return $return;
        });
    }
}
