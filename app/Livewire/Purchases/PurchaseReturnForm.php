<?php

namespace App\Livewire\Purchases;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Livewire\Component;

class PurchaseReturnForm extends Component
{
    public $editId = null;
    public $return = null;

    // Header fields
    public $return_number = '';
    public $supplier_id = '';
    public $branch_id = '';
    public $purchase_invoice_id = '';
    public $return_date = '';
    public $reason = '';
    public $notes = '';
    public $status = 'Draft';

    // Summary calculations
    public $gross_amount = 0.00;
    public $tax = 0.00;
    public $net_amount = 0.00;

    // Item line grid
    public $items = [];

    // Master list data
    public $suppliers = [];
    public $branches = [];
    public $catalogItems = [];
    public $invoices = [];

    // Adding dynamic item selection
    public $selectedItemId = '';
    public $selectedQty = 1;
    public $selectedRate = 0.00;

    protected $rules = [
        'supplier_id' => 'required|exists:suppliers,id',
        'branch_id' => 'required|exists:branches,id',
        'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
        'return_date' => 'required|date',
        'reason' => 'nullable|string|max:255',
        'notes' => 'nullable|string',
        'tax' => 'required|numeric|min:0',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:inventory_items,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_cost' => 'required|numeric|min:0.01',
    ];

    protected $messages = [
        'items.required' => 'At least one line item is required to return.',
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

        // Get posted invoices for reference
        $this->invoices = PurchaseInvoice::where('marquee_id', $marqueeId)->where('status', 'Posted')->orderBy('invoice_number')->get();

        if ($id) {
            $this->editId = $id;
            $this->return = PurchaseReturn::with(['details.item.unit', 'journalVoucher'])->findOrFail($id);
            $this->return_number = $this->return->return_number;
            $this->supplier_id = $this->return->supplier_id;
            $this->branch_id = $this->return->branch_id;
            $this->purchase_invoice_id = $this->return->purchase_invoice_id ?? '';
            $this->return_date = $this->return->return_date->format('Y-m-d');
            $this->reason = $this->return->reason ?? '';
            $this->notes = $this->return->notes ?? '';
            $this->status = $this->return->status;

            $this->gross_amount = $this->return->gross_amount;
            $this->tax = $this->return->tax;
            $this->net_amount = $this->return->net_amount;

            $this->items = [];
            foreach ($this->return->details as $det) {
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
            $this->return_date = date('Y-m-d');
            if ($user->branch_id) {
                $this->branch_id = $user->branch_id;
            }
            $this->items = [];

            // Auto generate draft return code
            $count = PurchaseReturn::withTrashed()->where('marquee_id', $marqueeId)->count();
            $this->return_number = 'RET-' . date('Y') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
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

    public function updatedPurchaseInvoiceId()
    {
        if ($this->purchase_invoice_id) {
            $inv = PurchaseInvoice::with(['details.item.unit'])->find($this->purchase_invoice_id);
            if ($inv) {
                $this->supplier_id = $inv->supplier_id;
                $this->branch_id = $inv->branch_id;

                $this->items = [];
                foreach ($inv->details as $det) {
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
                $this->recalculateAmounts();
            }
        }
    }

    public function addLine()
    {
        $this->validate([
            'selectedItemId' => 'required|exists:inventory_items,id',
            'selectedQty' => 'required|numeric|min:0.01',
            'selectedRate' => 'required|numeric|min:0.01',
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

    public function updatedItems($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) === 2) {
            $index = $parts[0];
            $field = $parts[1];
            if ($field === 'quantity' || $field === 'unit_cost') {
                $qty = floatval($this->items[$index]['quantity'] ?? 0);
                $cost = floatval($this->items[$index]['unit_cost'] ?? 0);
                $this->items[$index]['amount'] = $qty * $cost;
                $this->recalculateAmounts();
            }
        }
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

        $tx = is_numeric($this->tax) ? floatval($this->tax) : 0.00;
        $this->net_amount = $this->gross_amount + $tx;
    }

    public function save()
    {
        if ($this->status !== 'Draft') {
            return;
        }

        $this->validate();
        $marqueeId = auth()->user()->marquee_id;

        \Illuminate\Support\Facades\DB::transaction(function () use ($marqueeId) {
            $this->recalculateAmounts();

            if ($this->editId) {
                $return = PurchaseReturn::findOrFail($this->editId);
                $return->update([
                    'return_number' => $this->return_number,
                    'supplier_id' => $this->supplier_id,
                    'branch_id' => $this->branch_id,
                    'purchase_invoice_id' => $this->purchase_invoice_id ?: null,
                    'return_date' => $this->return_date,
                    'reason' => $this->reason ?: null,
                    'notes' => $this->notes ?: null,
                    'gross_amount' => $this->gross_amount,
                    'tax' => $this->tax,
                    'net_amount' => $this->net_amount,
                ]);

                $return->details()->delete();
            } else {
                $return = PurchaseReturn::create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $this->branch_id,
                    'supplier_id' => $this->supplier_id,
                    'purchase_invoice_id' => $this->purchase_invoice_id ?: null,
                    'return_number' => $this->return_number,
                    'return_date' => $this->return_date,
                    'reason' => $this->reason ?: null,
                    'notes' => $this->notes ?: null,
                    'gross_amount' => $this->gross_amount,
                    'tax' => $this->tax,
                    'net_amount' => $this->net_amount,
                    'status' => 'Draft',
                ]);
            }

            foreach ($this->items as $item) {
                PurchaseReturnDetail::create([
                    'purchase_return_id' => $return->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'amount' => $item['amount'],
                ]);
            }
        });

        session()->flash('success', 'Purchase Return saved successfully as draft.');
        return redirect()->route('purchase-returns.index');
    }

    public function postToAccounts(PurchaseService $purchaseService)
    {
        try {
            $purchaseService->postPurchaseReturn($this->editId);
            session()->flash('success', 'Purchase Return posted to ledger accounts. Supplier payable balance updated.');
            $this->status = 'Posted';
            if ($this->return) {
                $this->return->refresh();
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.purchases.purchase-return-form')
            ->layout('layouts.admin');
    }
}
