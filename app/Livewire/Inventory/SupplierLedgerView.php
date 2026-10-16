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
        
        $marqueeId = $this->supplier->marquee_id ?: (auth()->user()->marquee_id ?: auth()->user()->getActiveMarqueeId());
        CashBankAccount::ensureDefaultAccountsForMarquee($marqueeId);
    }

    public function getCashAccountsProperty()
    {
        $marqueeId = $this->supplier->marquee_id ?: (auth()->user()->marquee_id ?: auth()->user()->getActiveMarqueeId());
        return CashBankAccount::getAccountsWithBalances($marqueeId);
    }

    public function getCashAccounts()
    {
        return $this->cashAccounts;
    }

    public function recordPayment(InventoryService $inventoryService)
    {
        $this->validate();
        $marqueeId = $this->supplier->marquee_id ?: (auth()->user()->marquee_id ?: auth()->user()->getActiveMarqueeId());

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
                "Manual Vendor Payment from: {$account->display_name}. " . $this->description
            );

            // Double Entry mapping: In a full integration, you would also post a JV: Accounts Payable Dr / Cash-Bank Cr.
            $settings = $inventoryService->getOrCreateSettings($marqueeId);
            $payableAccId = $settings->accounts_payable_account_id;
            
            // Fallback to COA Accounts Payable (2001) if not explicitly mapped
            if (!$payableAccId) {
                $payableAccId = \App\Models\Account::withoutGlobalScope('tenant')
                    ->where('marquee_id', $marqueeId)
                    ->where('account_code', '2001')
                    ->value('id');
            }

            // Map the cash/bank account's COA account_id
            $cashCoaId = $account->account_id;

            if ($payableAccId && $cashCoaId) {
                $accountingService = app(\App\Services\AccountingService::class);
                $branchId = auth()->user()->branch_id;
                $header = [
                    'marquee_id' => $marqueeId,
                    'branch_id' => $branchId,
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
                        'narration' => "Credit Asset - Paid via {$account->display_name}",
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

    public function exportExcel()
    {
        $supplier = $this->supplier;
        $ledgers = SupplierLedger::where('supplier_id', $supplier->id)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $filename = "Supplier_Ledger_" . ($supplier->supplier_code ?: $supplier->id) . "_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream(function () use ($supplier, $ledgers) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            fputcsv($handle, ['SUPPLIER LEDGER STATEMENT']);
            fputcsv($handle, ['Supplier Name', $supplier->name]);
            fputcsv($handle, ['Supplier Code', $supplier->supplier_code]);
            fputcsv($handle, ['Contact', $supplier->mobile_number]);
            fputcsv($handle, ['Outstanding Balance', $supplier->current_balance]);
            fputcsv($handle, ['Generated At', date('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, ['Date', 'Voucher No', 'Transaction Type', 'Description', 'Debit (Paid/Ret)', 'Credit (Billed)', 'Balance (Payable)']);

            foreach ($ledgers as $l) {
                fputcsv($handle, [
                    $l->transaction_date->format('Y-m-d'),
                    $l->voucher_no ?: '—',
                    $l->reference_type,
                    $l->description,
                    $l->debit > 0 ? $l->debit : '0.00',
                    $l->credit > 0 ? $l->credit : '0.00',
                    $l->running_balance,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function render()
    {
        $ledgers = SupplierLedger::where('supplier_id', $this->supplier->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $cashAccounts = $this->getCashAccounts();

        return view('livewire.inventory.supplier-ledger-view', compact('ledgers', 'cashAccounts'))
            ->layout('layouts.admin');
    }
}
