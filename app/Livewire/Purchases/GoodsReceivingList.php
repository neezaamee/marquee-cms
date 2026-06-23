<?php

namespace App\Livewire\Purchases;

use App\Models\GoodsReceivingNote;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class GoodsReceivingList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterSupplier = '';

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterSupplier' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterSupplier()
    {
        $this->resetPage();
    }

    public function render()
    {
        $marqueeId = auth()->user()->marquee_id;
        $query = GoodsReceivingNote::where('marquee_id', $marqueeId)->with(['supplier', 'branch', 'purchaseOrder']);

        if (!empty($this->search)) {
            $query->where('grn_number', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->filterSupplier)) {
            $query->where('supplier_id', $this->filterSupplier);
        }

        $goodsReceipts = $query->latest()->paginate(10);
        $suppliers = Supplier::where('marquee_id', $marqueeId)->orderBy('name')->get();

        return view('livewire.purchases.goods-receiving-list', compact('goodsReceipts', 'suppliers'))
            ->layout('layouts.admin');
    }
}
