<?php

namespace App\Livewire\Purchases;

use App\Models\GoodsReceivingNote;
use App\Models\PurchaseOrder;
use App\Services\PurchaseService;
use Livewire\Component;

class GoodsReceivingForm extends Component
{
    public $editId = null;
    public $grn = null;

    // Header fields
    public $grn_number = '';
    public $purchase_order_id = '';
    public $received_date = '';
    public $notes = '';

    // dynamic details of PO items
    public $items = [];

    // Pending PO choices
    public $pendingPOs = [];

    protected $rules = [
        'purchase_order_id' => 'required|exists:purchase_orders,id',
        'received_date' => 'required|date',
        'notes' => 'nullable|string',
        'items' => 'required|array|min:1',
        'items.*.received_qty' => 'required|numeric|min:0',
    ];

    public function mount($id = null, $po = null)
    {
        $marqueeId = auth()->user()->marquee_id;
        $this->received_date = date('Y-m-d');

        if ($id) {
            $this->editId = $id;
            $this->grn = GoodsReceivingNote::with(['purchaseOrder', 'supplier', 'branch', 'details.item.unit'])->findOrFail($id);
            $this->grn_number = $this->grn->grn_number;
            $this->purchase_order_id = $this->grn->purchase_order_id;
            $this->received_date = $this->grn->received_date->format('Y-m-d');
            $this->notes = $this->grn->notes ?? '';

            $this->items = [];
            foreach ($this->grn->details as $det) {
                $this->items[] = [
                    'item_id' => $det->item_id,
                    'item_code' => $det->item->item_code,
                    'item_name' => $det->item->name,
                    'unit' => $det->item->unit->short_code ?? 'Pcs',
                    'ordered_qty' => $det->ordered_qty,
                    'received_qty' => $det->received_qty,
                ];
            }
        } else {
            $this->pendingPOs = PurchaseOrder::where('marquee_id', $marqueeId)
                ->whereIn('status', ['Approved', 'Partially Received'])
                ->orderBy('po_number')
                ->get();

            if ($po) {
                $this->purchase_order_id = $po;
                $this->updatedPurchaseOrderId();
            }
        }
    }

    public function updatedPurchaseOrderId()
    {
        if ($this->purchase_order_id) {
            $poSelected = PurchaseOrder::with(['details.item.unit'])->findOrFail($this->purchase_order_id);
            
            $this->items = [];
            foreach ($poSelected->details as $det) {
                $remaining = max(0, $det->quantity - $det->received_quantity);
                $this->items[] = [
                    'item_id' => $det->item_id,
                    'item_code' => $det->item->item_code,
                    'item_name' => $det->item->name,
                    'unit' => $det->item->unit->short_code ?? 'Pcs',
                    'ordered_qty' => $det->quantity,
                    'already_received' => $det->received_quantity,
                    'remaining' => $remaining,
                    'received_qty' => $remaining, // Default to remaining
                ];
            }
        } else {
            $this->items = [];
        }
    }

    public function save(PurchaseService $purchaseService)
    {
        if ($this->editId) {
            return;
        }

        $this->validate();
        
        $totalReceived = 0;
        foreach ($this->items as $item) {
            $totalReceived += floatval($item['received_qty']);
        }

        if ($totalReceived <= 0) {
            session()->flash('error', 'You must receive at least one item (quantity > 0).');
            return;
        }

        $grnData = [
            'received_date' => $this->received_date,
            'notes' => $this->notes,
        ];

        try {
            $purchaseService->recordGoodsReceipt($this->purchase_order_id, $grnData, $this->items);
            session()->flash('success', 'Goods Receiving Note logged successfully.');
            return redirect()->route('goods-receipts.index');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.purchases.goods-receiving-form')
            ->layout('layouts.admin');
    }
}
