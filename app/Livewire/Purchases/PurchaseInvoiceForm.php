<?php

namespace App\Livewire\Purchases;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Livewire\Component;

class PurchaseInvoiceForm extends Component
{
    public $editId = null;
    public $invoice = null;

    // Header fields
    public $invoice_number = '';
    public $supplier_id = '';
    public $purchase_order_id = '';
    public $goods_receiving_note_id = '';
    public $branch_id = '';
    public $purchase_date = '';
    public $reference_number = '';
    public $notes = '';
    public $status = 'Draft';

    // Summary calculations
    public $gross_amount = 0.00;
    public $discount = 0.00;
    public $tax = 0.00;
    public $net_amount = 0.00;

    // Master list data
    public $purchaseOrders = [];
    public $goodsReceipts = [];

    // Item line grid
    public $items = [];

    // Master list data
    public $suppliers = [];
    public $branches = [];
    public $catalogItems = [];

    // Adding dynamic item selection
    public $selectedItemId = '';
    public $selectedQty = 1;
    public $selectedRate = 0.00;

    protected $rules = [
        'invoice_number' => 'required|string|max:100',
        'supplier_id' => 'required|exists:suppliers,id',
        'purchase_order_id' => 'nullable|exists:purchase_orders,id',
        'goods_receiving_note_id' => 'nullable|exists:goods_receiving_notes,id',
        'branch_id' => 'required|exists:branches,id',
        'purchase_date' => 'required|date',
        'reference_number' => 'nullable|string|max:100',
        'notes' => 'nullable|string',
        'discount' => 'required|numeric|min:0',
        'tax' => 'required|numeric|min:0',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:inventory_items,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_cost' => 'required|numeric|min:0.01',
    ];

    protected $messages = [
        'items.required' => 'At least one line item is required.',
        'items.*.quantity.min' => 'Quantity must be positive.',
        'items.*.unit_cost.min' => 'Rate must be positive.',
    ];

    public function mount($id = null)
    {
        $marqueeId = auth()->user()->marquee_id;
        $user = auth()->user();

        $this->suppliers = Supplier::where('marquee_id', $marqueeId)->where('status', 'Active')->orderBy('name')->get();
        $this->branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $this->catalogItems = InventoryItem::where('marquee_id', $marqueeId)->where('status', 'Active')->orderBy('name')->get();

        if ($id) {
            $this->editId = $id;
            $this->invoice = PurchaseInvoice::with(['details.item.unit', 'journalVoucher'])->findOrFail($id);
            $this->invoice_number = $this->invoice->invoice_number;
            $this->supplier_id = $this->invoice->supplier_id;
            $this->branch_id = $this->invoice->branch_id;
            $this->purchase_date = $this->invoice->purchase_date->format('Y-m-d');
            $this->reference_number = $this->invoice->reference_number ?? '';
            $this->notes = $this->invoice->notes ?? '';
            $this->status = $this->invoice->status;

            $this->purchase_order_id = $this->invoice->purchase_order_id ?? '';
            $this->goods_receiving_note_id = $this->invoice->goods_receiving_note_id ?? '';

            $this->gross_amount = $this->invoice->gross_amount;
            $this->discount = $this->invoice->discount;
            $this->tax = $this->invoice->tax;
            $this->net_amount = $this->invoice->net_amount;

            $this->items = [];
            foreach ($this->invoice->details as $det) {
                $this->items[] = [
                    'item_id' => $det->item_id,
                    'item_code' => $det->item->item_code,
                    'item_name' => $det->item->name,
                    'unit' => $det->item->unit->short_code ?? 'Pcs',
                    'quantity' => $det->quantity,
                    'unit_cost' => $det->unit_cost,
                    'amount' => $det->amount,
                ];
            }
        } else {
            $this->purchase_date = date('Y-m-d');
            if ($user->branch_id) {
                $this->branch_id = $user->branch_id;
            }
            $this->items = [];
            
            // Auto generate draft invoice number
            $count = PurchaseInvoice::withTrashed()->where('marquee_id', $marqueeId)->count();
            $this->invoice_number = 'PINV-' . date('Y') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        }

        $this->loadPendingReferences();
    }

    public function loadPendingReferences()
    {
        $marqueeId = auth()->user()->marquee_id;
        if ($this->supplier_id && $this->branch_id) {
            $this->purchaseOrders = \App\Models\PurchaseOrder::where('marquee_id', $marqueeId)
                ->where('supplier_id', $this->supplier_id)
                ->where('branch_id', $this->branch_id)
                ->whereIn('status', ['Approved', 'Partially Received', 'Completed'])
                ->orderBy('po_number', 'desc')
                ->get();

            $this->goodsReceipts = \App\Models\GoodsReceivingNote::where('marquee_id', $marqueeId)
                ->where('supplier_id', $this->supplier_id)
                ->where('branch_id', $this->branch_id)
                ->orderBy('grn_number', 'desc')
                ->get();
        } else {
            $this->purchaseOrders = [];
            $this->goodsReceipts = [];
        }
    }

    public function updatedSupplierId()
    {
        $this->loadPendingReferences();
    }

    public function updatedBranchId()
    {
        $this->loadPendingReferences();
    }

    public function updatedGoodsReceivingNoteId()
    {
        if ($this->goods_receiving_note_id) {
            $grn = \App\Models\GoodsReceivingNote::with(['details.item.unit', 'purchaseOrder.details'])->findOrFail($this->goods_receiving_note_id);
            $this->purchase_order_id = $grn->purchase_order_id;
            
            $this->items = [];
            foreach ($grn->details as $det) {
                // Find matching PO detail unit cost
                $poDetail = $grn->purchaseOrder ? $grn->purchaseOrder->details->firstWhere('item_id', $det->item_id) : null;
                $unitCost = $poDetail ? $poDetail->unit_price : ($det->item->default_purchase_rate ?: 0.00);

                // Fetch other already invoiced quantities for this GRN item to get remaining balance
                $alreadyInvoicedQty = \App\Models\PurchaseInvoiceDetail::whereHas('purchaseInvoice', function ($q) use ($grn) {
                        $q->where('goods_receiving_note_id', $grn->id)
                          ->where('status', '!=', 'Cancelled');
                        if ($this->editId) {
                            $q->where('id', '!=', $this->editId);
                        }
                    })
                    ->where('item_id', $det->item_id)
                    ->sum('quantity');

                $remainingQty = (float) $det->received_qty - $alreadyInvoicedQty;

                if ($remainingQty > 0) {
                    $this->items[] = [
                        'item_id' => $det->item_id,
                        'item_code' => $det->item->item_code,
                        'item_name' => $det->item->name,
                        'unit' => $det->item->unit->short_code ?? 'Pcs',
                        'quantity' => $remainingQty,
                        'unit_cost' => $unitCost,
                        'amount' => $remainingQty * $unitCost,
                    ];
                }
            }
            $this->recalculateAmounts();
        }
    }

    public function updatedSelectedItemId()
    {
        if ($this->selectedItemId) {
            $catItem = InventoryItem::find($this->selectedItemId);
            if ($catItem) {
                $this->selectedRate = $catItem->default_purchase_rate;
            }
        }
    }

    public function addLine()
    {
        $this->validate([
            'selectedItemId' => 'required|exists:inventory_items,id',
            'selectedQty' => 'required|numeric|min:0.01',
            'selectedRate' => 'required|numeric|min:0.01',
        ], [
            'selectedItemId.required' => 'Select an item to add.',
            'selectedQty.min' => 'Quantity must be positive.',
            'selectedRate.min' => 'Rate must be positive.',
        ]);

        foreach ($this->items as $idx => $item) {
            if ($item['item_id'] == $this->selectedItemId) {
                $this->items[$idx]['quantity'] += floatval($this->selectedQty);
                $this->items[$idx]['amount'] = $this->items[$idx]['quantity'] * $this->items[$idx]['unit_cost'];
                $this->recalculateAmounts();
                $this->resetLineForm();
                return;
            }
        }

        $catItem = InventoryItem::with('unit')->findOrFail($this->selectedItemId);

        $this->items[] = [
            'item_id' => $catItem->id,
            'item_code' => $catItem->item_code,
            'item_name' => $catItem->name,
            'unit' => $catItem->unit->short_code ?? 'Pcs',
            'quantity' => floatval($this->selectedQty),
            'unit_cost' => floatval($this->selectedRate),
            'amount' => floatval($this->selectedQty) * floatval($this->selectedRate),
        ];

        $this->recalculateAmounts();
        $this->resetLineForm();
    }

    public function resetLineForm()
    {
        $this->selectedItemId = '';
        $this->selectedQty = 1;
        $this->selectedRate = 0.00;
    }

    public function removeLine($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculateAmounts();
    }

    public function updatedDiscount()
    {
        $this->recalculateAmounts();
    }

    public function updatedTax()
    {
        $this->recalculateAmounts();
    }

    public function recalculateAmounts()
    {
        $this->gross_amount = 0.00;
        foreach ($this->items as $item) {
            $this->gross_amount += $item['amount'];
        }

        $disc = is_numeric($this->discount) ? floatval($this->discount) : 0.00;
        $tx = is_numeric($this->tax) ? floatval($this->tax) : 0.00;

        $this->net_amount = $this->gross_amount - $disc + $tx;
    }

    public function save()
    {
        if ($this->status !== 'Draft') {
            return;
        }

        $this->validate();

        // 3-way matching validation
        if ($this->goods_receiving_note_id) {
            $grn = \App\Models\GoodsReceivingNote::with('details')->findOrFail($this->goods_receiving_note_id);
            foreach ($this->items as $item) {
                $grnDetail = $grn->details->firstWhere('item_id', $item['item_id']);
                $receivedQty = $grnDetail ? (float) $grnDetail->received_qty : 0.00;
                
                // Fetch other already invoiced quantities for this GRN item to get remaining uninvoiced balance
                $alreadyInvoicedQty = \App\Models\PurchaseInvoiceDetail::whereHas('purchaseInvoice', function ($q) use ($grn) {
                        $q->where('goods_receiving_note_id', $grn->id)
                          ->where('status', '!=', 'Cancelled');
                        if ($this->editId) {
                            $q->where('id', '!=', $this->editId);
                        }
                    })
                    ->where('item_id', $item['item_id'])
                    ->sum('quantity');

                $remainingQty = $receivedQty - $alreadyInvoicedQty;

                if ((float)$item['quantity'] > $remainingQty) {
                    $this->addError('items', "Quantity for item '" . $item['item_name'] . "' exceeds the remaining GRN quantity (Remaining: " . number_format($remainingQty, 2) . ").");
                    return;
                }
            }
        }

        $marqueeId = auth()->user()->marquee_id;

        \Illuminate\Support\Facades\DB::transaction(function () use ($marqueeId) {
            $this->recalculateAmounts();

            if ($this->editId) {
                $invoice = PurchaseInvoice::findOrFail($this->editId);
                $invoice->update([
                    'invoice_number' => $this->invoice_number,
                    'supplier_id' => $this->supplier_id,
                    'purchase_order_id' => $this->purchase_order_id ?: null,
                    'goods_receiving_note_id' => $this->goods_receiving_note_id ?: null,
                    'branch_id' => $this->branch_id,
                    'purchase_date' => $this->purchase_date,
                    'reference_number' => $this->reference_number ?: null,
                    'notes' => $this->notes ?: null,
                    'gross_amount' => $this->gross_amount,
                    'discount' => $this->discount,
                    'tax' => $this->tax,
                    'net_amount' => $this->net_amount,
                ]);

                // Sync details
                $invoice->details()->delete();
            } else {
                $invoice = PurchaseInvoice::create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $this->branch_id,
                    'supplier_id' => $this->supplier_id,
                    'purchase_order_id' => $this->purchase_order_id ?: null,
                    'goods_receiving_note_id' => $this->goods_receiving_note_id ?: null,
                    'invoice_number' => $this->invoice_number,
                    'purchase_date' => $this->purchase_date,
                    'reference_number' => $this->reference_number ?: null,
                    'notes' => $this->notes ?: null,
                    'gross_amount' => $this->gross_amount,
                    'discount' => $this->discount,
                    'tax' => $this->tax,
                    'net_amount' => $this->net_amount,
                    'status' => 'Draft',
                ]);
            }

            foreach ($this->items as $item) {
                PurchaseInvoiceDetail::create([
                    'purchase_invoice_id' => $invoice->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'amount' => $item['amount'],
                ]);
            }
        });

        session()->flash('success', 'Purchase invoice bill saved as draft.');
        return redirect()->route('purchase-invoices.index');
    }

    public function postToAccounts(PurchaseService $purchaseService)
    {
        try {
            $purchaseService->postPurchaseInvoice($this->editId);
            session()->flash('success', 'Invoice posted successfully. Automatic Journal Voucher generated and Supplier Ledger updated.');
            $this->status = 'Posted';
            if ($this->invoice) {
                $this->invoice->refresh();
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.purchases.purchase-invoice-form')
            ->layout('layouts.admin');
    }
}
