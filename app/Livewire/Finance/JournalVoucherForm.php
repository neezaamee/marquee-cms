<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalVoucher;
use App\Services\AccountingService;
use Livewire\Component;

class JournalVoucherForm extends Component
{
    public $editId = null;
    
    // Header fields
    public $voucher_no = '';
    public $voucher_date = '';
    public $branch_id = '';
    public $reference = '';
    public $notes = '';
    public $status = 'draft';

    // Detail lines
    public $items = [];

    // Master data
    public $branches = [];
    public $accounts = [];

    protected $rules = [
        'voucher_date' => 'required|date',
        'branch_id' => 'nullable|exists:branches,id',
        'reference' => 'nullable|string|max:100',
        'notes' => 'nullable|string',
        'status' => 'required|in:draft,posted',
        'items' => 'required|array|min:2',
        'items.*.account_id' => 'required|exists:accounts,id',
        'items.*.debit' => 'nullable|numeric|min:0',
        'items.*.credit' => 'nullable|numeric|min:0',
        'items.*.narration' => 'nullable|string|max:255',
    ];

    protected $messages = [
        'items.*.account_id.required' => 'Account is required.',
        'items.*.debit.numeric' => 'Debit must be a number.',
        'items.*.credit.numeric' => 'Credit must be a number.',
    ];

    public function mount($id = null)
    {
        $marqueeId = auth()->user()->marquee_id;
        $user = auth()->user();

        $this->branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();

        if ($id) {
            $voucher = JournalVoucher::with('items')->findOrFail($id);
            
            if ($voucher->status === 'posted') {
                session()->flash('error', 'Cannot edit a posted journal voucher.');
                return redirect()->route('finance.journal-vouchers.index');
            }

            $this->editId = $voucher->id;
            $this->voucher_no = $voucher->voucher_no;
            $this->voucher_date = $voucher->voucher_date->format('Y-m-d');
            $this->branch_id = $voucher->branch_id ?? '';
            $this->reference = $voucher->reference;
            $this->notes = $voucher->notes;
            $this->status = $voucher->status;

            $this->items = [];
            foreach ($voucher->items as $item) {
                $this->items[] = [
                    'account_id' => $item->account_id,
                    'debit' => $item->debit > 0 ? (float)$item->debit : '',
                    'credit' => $item->credit > 0 ? (float)$item->credit : '',
                    'narration' => $item->narration,
                ];
            }
        } else {
            $this->voucher_date = date('Y-m-d');
            if ($user->branch_id) {
                $this->branch_id = $user->branch_id;
            }
            $this->items = [
                ['account_id' => '', 'debit' => '', 'credit' => '', 'narration' => ''],
                ['account_id' => '', 'debit' => '', 'credit' => '', 'narration' => ''],
            ];
        }

        // Get leaf accounts
        $this->accounts = Account::where('marquee_id', $marqueeId)
            ->whereDoesntHave('children')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();
    }

    public function addRow()
    {
        $this->items[] = ['account_id' => '', 'debit' => '', 'credit' => '', 'narration' => ''];
    }

    public function removeRow($index)
    {
        if (count($this->items) > 2) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        } else {
            session()->flash('row_error', 'A journal voucher must contain at least two rows.');
        }
    }

    public function save(AccountingService $accountingService)
    {
        $this->validate();

        $marqueeId = auth()->user()->marquee_id;

        // Clean items array structure
        $cleanItems = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($this->items as $item) {
            $debit = (float)($item['debit'] ?: 0);
            $credit = (float)($item['credit'] ?: 0);

            if ($debit > 0 && $credit > 0) {
                session()->flash('validation_error', 'A single row cannot have both debit and credit values.');
                return;
            }

            if ($debit == 0 && $credit == 0) {
                session()->flash('validation_error', 'Each row must have either a debit or credit value.');
                return;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $cleanItems[] = [
                'account_id' => $item['account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'narration' => $item['narration'] ?: null,
            ];
        }

        if (abs($totalDebit - $totalCredit) > 0.001) {
            session()->flash('validation_error', "Unbalanced entries: Total Debit ({$totalDebit}) must equal Total Credit ({$totalCredit}).");
            return;
        }

        $header = [
            'marquee_id' => $marqueeId,
            'branch_id' => $this->branch_id ?: null,
            'voucher_date' => $this->voucher_date,
            'reference' => $this->reference ?: null,
            'notes' => $this->notes ?: null,
            'status' => $this->status,
        ];

        try {
            if ($this->editId) {
                $accountingService->updateJournalVoucher($this->editId, $header, $cleanItems);
                session()->flash('success', 'Journal Voucher updated successfully.');
            } else {
                $accountingService->createJournalVoucher($header, $cleanItems);
                session()->flash('success', 'Journal Voucher created successfully.');
            }

            return redirect()->route('finance.journal-vouchers.index');
        } catch (\Exception $e) {
            session()->flash('validation_error', $e->getMessage());
        }
    }

    public function render()
    {
        // Compute running totals for UI
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($this->items as $item) {
            $totalDebit += (float)($item['debit'] ?: 0);
            $totalCredit += (float)($item['credit'] ?: 0);
        }

        return view('livewire.finance.journal-voucher-form', [
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ]);
    }
}
