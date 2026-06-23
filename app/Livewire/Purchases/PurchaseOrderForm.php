<?php

namespace App\Livewire\Purchases;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Livewire\Component;

class PurchaseOrderForm extends Component
{
    public $editId = null;
    public $po = null;

    // Header fields
    public $po_number = '';
    public $supplier_id = '';
    public $branch_id = '';
    public $order_date = '';
    public $expected_delivery_date = '';
    public $notes = '';
    public $status = 'Draft';

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
        'supplier_id' => 'required|exists:suppliers,id',
        'branch_id' => 'required|exists:branches,id',
        'order_date' => 'required|date',
        'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
        'notes' => 'nullable|string',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:inventory_items,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0.01',
    ];

    protected $messages = [
        'items.required' => 'At least one item line must be added to the purchase order.',
        'items.*.quantity.min' => 'Quantity must be greater than zero.',
        'items.*.unit_price.min' => 'Unit price must be greater than zero.',
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
            $this->po = PurchaseOrder::with(['details.item.unit'])->findOrFail($id);
            $this->po_number = $this->po->po_number;
            $this->supplier_id = $this->po->supplier_id;
            $this->branch_id = $this->po->branch_id;
            $this->order_date = $this->po->order_date->format('Y-m-d');
            $this->expected_delivery_date = $this->po->expected_delivery_date ? $this->po->expected_delivery_date->format('Y-m-d') : '';
            $this->notes = $this->po->notes ?? '';
            $this->status = $this->po->status;

            $this->items = [];
            foreach ($this->po->details as $det) {
                $this->items[] = [
                    'item_id' => $det->item_id,
                    'item_code' => $det->item->item_code,
                    'item_name' => $det->item->name,
                    'unit' => $det->item->unit->short_code ?? 'Pcs',
                    'quantity' => $det->quantity,
                    'unit_price' => $det->unit_price,
                    'amount' => $det->amount,
                ];
            }
        } else {
            $this->order_date = date('Y-m-d');
            if ($user->branch_id) {
                $this->branch_id = $user->branch_id;
            }
            $this->items = [];
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

        // Check if item already exists in items array
        foreach ($this->items as $idx => $item) {
            if ($item['item_id'] == $this->selectedItemId) {
                $this->items[$idx]['quantity'] += floatval($this->selectedQty);
                $this->items[$idx]['amount'] = $this->items[$idx]['quantity'] * $this->items[$idx]['unit_price'];
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
            'unit_price' => floatval($this->selectedRate),
            'amount' => floatval($this->selectedQty) * floatval($this->selectedRate),
        ];

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
    }

    public function save()
    {
        if ($this->status !== 'Draft') {
            return;
        }

        $this->validate();
        $marqueeId = auth()->user()->marquee_id;

        \Illuminate\Support\Facades\DB::transaction(function () use ($marqueeId) {
            if ($this->editId) {
                $po = PurchaseOrder::findOrFail($this->editId);
                $po->update([
                    'supplier_id' => $this->supplier_id,
                    'branch_id' => $this->branch_id,
                    'order_date' => $this->order_date,
                    'expected_delivery_date' => $this->expected_delivery_date ?: null,
                    'notes' => $this->notes ?: null,
                ]);

                // Sync details
                $po->details()->delete();
            } else {
                // Generate next sequence PO
                $poYear = date('Y', strtotime($this->order_date));
                $count = PurchaseOrder::withTrashed()->where('marquee_id', $marqueeId)->count();
                $this->po_number = "PO-{$poYear}-" . str_pad($count + 1, 5, '0', STR_PAD_LEFT);

                $po = PurchaseOrder::create([
                    'marquee_id' => $marqueeId,
                    'branch_id' => $this->branch_id,
                    'po_number' => $this->po_number,
                    'supplier_id' => $this->supplier_id,
                    'order_date' => $this->order_date,
                    'expected_delivery_date' => $this->expected_delivery_date ?: null,
                    'notes' => $this->notes ?: null,
                    'status' => 'Draft',
                ]);
            }

            foreach ($this->items as $item) {
                PurchaseOrderDetail::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                ]);
            }
        });

        session()->flash('success', 'Purchase Order saved successfully.');
        return redirect()->route('purchase-orders.index');
    }

    public function approve(PurchaseService $purchaseService)
    {
        try {
            $purchaseService->approvePurchaseOrder($this->editId);
            session()->flash('success', 'Purchase Order approved successfully.');
            $this->status = 'Approved';
            if ($this->po) {
                $this->po->refresh();
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $totalSum = 0.00;
        foreach ($this->items as $item) {
            $totalSum += $item['amount'];
        }

        return view('livewire.purchases.purchase-order-form', [
            'totalSum' => $totalSum
        ])->layout('layouts.admin');
    }
}
