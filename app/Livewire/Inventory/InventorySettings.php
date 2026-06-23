<?php

namespace App\Livewire\Inventory;

use App\Models\Account;
use App\Models\InventorySetting;
use App\Services\InventoryService;
use Livewire\Component;

class InventorySettings extends Component
{
    public $inventory_asset_account_id = '';
    public $accounts_payable_account_id = '';

    public $accounts = [];

    protected $rules = [
        'inventory_asset_account_id' => 'required|exists:accounts,id',
        'accounts_payable_account_id' => 'required|exists:accounts,id',
    ];

    public function mount(InventoryService $inventoryService)
    {
        $marqueeId = auth()->user()->marquee_id;

        $settings = $inventoryService->getOrCreateSettings($marqueeId);
        $this->inventory_asset_account_id = $settings->inventory_asset_account_id ?? '';
        $this->accounts_payable_account_id = $settings->accounts_payable_account_id ?? '';

        // Fetch leaf level accounts (assets & liabilities)
        $this->accounts = Account::where('marquee_id', $marqueeId)
            ->whereDoesntHave('children')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();
    }

    public function save(InventoryService $inventoryService)
    {
        $this->validate();
        $marqueeId = auth()->user()->marquee_id;

        $settings = $inventoryService->getOrCreateSettings($marqueeId);
        $settings->update([
            'inventory_asset_account_id' => $this->inventory_asset_account_id,
            'accounts_payable_account_id' => $this->accounts_payable_account_id,
        ]);

        session()->flash('success', 'Account mappings configured successfully.');
    }

    public function render()
    {
        return view('livewire.inventory.inventory-settings')
            ->layout('layouts.admin');
    }
}
