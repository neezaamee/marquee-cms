<?php

namespace App\Livewire\Inventory;

use App\Models\CashBankAccount;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Services\InventoryService;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierLedgerView extends Component
{
    use WithPagination;

    public Supplier $supplier;

    // Manual Payment fields
    public $showPaymentModal = false;
    public $payment_amount = 0.00;
    public $payment_date = '';
    public $cash_bank_account_id = '';
    public $reference_no = '';
    public $description = '';

    public $cashAccounts = [];

    protected $paginationTheme = 'bootstrap';

    protected $rules = [
        'payment_amount' => 'required|numeric|min:1',
        'payment_date' => 'required|date',
        'cash_bank_account_id' => 'required|exists:cash_bank_accounts,id',
        'reference_no' => 'nullable|string|max:50',
        'description' => 'nullable|string|max:255',
    ];

    public function mount(Supplier $supplier)
    {
        $this->supplier = $supplier;
        $this->payment_date = date('Y-m-d');
        
        $marqueeId = auth()->user()->marquee_id;
        $this->cashAccounts = CashBankAccount::where('marquee_id', $marqueeId)->where('status', 'active')->get();
    }

    public function recordPayment(InventoryService $inventoryService)
    {
        $this->validate();
        $marqueeId = auth()->user()->marquee_id;

        \Illuminate\Support\Facades\DB::transaction(function () use ($inventoryService, $marqueeId) {
            // Find account for the ledger update
            $account = CashBankAccount::findOrFail($this->cash_bank_account_id);

            // Record manual vendor payment transaction (reduces supplier balance)
            $inventoryService->recordSupplierTransaction(
                $marqueeId,
                $this->supplier->id,
                $this->payment_date,
                $this->payment_amount, // Debit (Decreases payable balance)
                0.00, // Credit
                'VendorPayment',
                $account->id,
                $this->reference_no ?: 'PAID',
                "Manual Vendor Payment from: {$account->account_name}. " . $this->description
            );

            // Double Entry mapping: In a full integration, you would also post a JV: Accounts Payable Dr / Cash-Bank Cr.
            // Let's create an optional posted JV for the payment!
            // First find accounts payable mapped account
            $settings = $inventoryService->getOrCreateSettings($marqueeId);
            $payableAccId = $settings->accounts_payable_account_id;
            
            // Map the cash/bank account's COA account_id
            $cashCoaId = $account->account_id;

            if ($payableAccId && $cashCoaId) {
                $accountingService = app(\App\Services\AccountingService::class);
                $header = [
                    'marquee_id' => $marqueeId,
                    'branch_id' => auth()->user()->branch_id,
                    'voucher_date' => $this->payment_date,
                    'reference' => $this->reference_no ?: 'PMT-' . rand(1000, 9999),
                    'notes' => "Auto-posted Supplier Payment to: {$this->supplier->name}. " . $this->description,
                    'status' => 'posted',
                ];

                $items = [
                    [
                        'account_id' => $payableAccId,
                        'debit' => $this->payment_amount, // AP Debit reduces liability
                        'credit' => 0.00,
                        'narration' => "Debit Accounts Payable - Payment to {$this->supplier->name}",
                    ],
                    [
                        'account_id' => $cashCoaId,
                        'debit' => 0.00,
                        'credit' => $this->payment_amount, // Cash/Bank Credit reduces assets
                        'narration' => "Credit Asset - Paid via {$account->account_name}",
                    ]
                ];
                $accountingService->createJournalVoucher($header, $items);
            }
        });

        session()->flash('success', 'Manual vendor payment recorded successfully.');
        $this->supplier->refresh();
        $this->resetPaymentForm();
        $this->resetPage();
    }

    public function resetPaymentForm()
    {
        $this->showPaymentModal = false;
        $this->payment_amount = 0.00;
        $this->payment_date = date('Y-m-d');
        $this->cash_bank_account_id = '';
        $this->reference_no = '';
        $this->description = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $ledgers = SupplierLedger::where('supplier_id', $this->supplier->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('livewire.inventory.supplier-ledger-view', compact('ledgers'))
            ->layout('layouts.admin');
    }
}
