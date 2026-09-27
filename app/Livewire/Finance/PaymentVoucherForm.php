<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\Branch;
use App\Models\CashBankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialYear;
use App\Models\PaymentVoucher;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\AccountingService;
use App\Services\PaymentVoucherService;
use Livewire\Component;

class PaymentVoucherForm extends Component
{
    public $editId = null;

    // Voucher Core
    public $voucher_type = 'CPV'; // CPV, BPV
    public $voucher_no = '';
    public $voucher_date = '';
    public $branch_id = '';
    public $status = 'approved';

    // Payee
    public $payee_type = 'supplier'; // supplier, vendor, expense, general
    public $payee_id = '';
    public $payee_name = '';
    public $payee_cnic = '';
    public $payee_phone = '';

    // Banking & GL Accounts
    public $cash_bank_account_id = '';
    public $debit_account_id = '';

    // Amounts & Instruments
    public $amount = '';
    public $amount_in_words = '';
    public $payment_method = 'Cash';
    public $cheque_no = '';
    public $cheque_date = '';
    public $reference_no = '';
    public $description = '';

    // Form Master Data
    public $branches = [];
    public $cashBankAccounts = [];
    public $debitAccounts = [];
    public $suppliers = [];
    public $vendors = [];
    public $expenseCategories = [];
    public $expenses = [];

    // Helper Info
    public $payeeOutstandingBalance = null;

    protected function rules()
    {
        return [
            'voucher_type' => 'required|in:CPV,BPV',
            'voucher_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'payee_type' => 'required|in:supplier,vendor,expense,general',
            'payee_name' => 'required|string|max:150',
            'payee_cnic' => 'nullable|string|max:50',
            'payee_phone' => 'nullable|string|max:50',
            'cash_bank_account_id' => 'required|exists:cash_bank_accounts,id',
            'debit_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'amount_in_words' => 'nullable|string|max:500',
            'payment_method' => 'required|string|max:30',
            'cheque_no' => 'nullable|string|max:50',
            'cheque_date' => 'nullable|date',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:draft,approved',
        ];
    }

    public function getMarqueeId(): ?int
    {
        $user = auth()->user();
        return $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;
    }

    public function mount($id = null)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting')), 403, 'Unauthorized access to payment vouchers.');

        $service = app(PaymentVoucherService::class);
        $marqueeId = $this->getMarqueeId();

        if ($marqueeId) {
            app(\App\Services\AccountingService::class)->syncExpenseAccountsWithCategories($marqueeId);
        }

        $this->branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $this->suppliers = Supplier::where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        $this->vendors = Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->where('status', 'active')->orderBy('name')->get();
        $this->expenseCategories = ExpenseCategory::with(['defaultAccount.accountType'])
            ->where('marquee_id', $marqueeId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        if ($this->expenseCategories->isEmpty()) {
            $this->expenseCategories = ExpenseCategory::with(['defaultAccount.accountType'])
                ->where('marquee_id', $marqueeId)
                ->orderBy('name')
                ->get();
        }
        $this->expenses = Expense::where('marquee_id', $marqueeId)->whereIn('status', ['Approved', 'Submitted', 'Draft'])->orderBy('expense_date', 'desc')->limit(30)->get();

        // Leaf GL accounts eager-loaded with accountType
        $this->debitAccounts = Account::with('accountType')
            ->where('marquee_id', $marqueeId)
            ->whereDoesntHave('children')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        if ($id) {
            $voucher = PaymentVoucher::where('marquee_id', $marqueeId)->findOrFail($id);
            if ($voucher->status === PaymentVoucher::STATUS_POSTED) {
                session()->flash('error', 'Cannot edit a posted payment voucher.');
                return redirect()->route('finance.payment-vouchers.index');
            }

            $this->editId = $voucher->id;
            $this->voucher_type = $voucher->voucher_type;
            $this->voucher_no = $voucher->voucher_no;
            $this->voucher_date = $voucher->voucher_date->format('Y-m-d');
            $this->branch_id = $voucher->branch_id ?? '';
            $this->status = $voucher->status;
            $this->payee_type = $voucher->payee_type;
            $this->payee_id = $voucher->payee_id ?? '';
            $this->payee_name = $voucher->payee_name;
            $this->payee_cnic = $voucher->payee_cnic;
            $this->payee_phone = $voucher->payee_phone;
            $this->cash_bank_account_id = $voucher->cash_bank_account_id;
            $this->debit_account_id = $voucher->debit_account_id;
            $this->amount = $voucher->amount;
            $this->amount_in_words = $voucher->amount_in_words;
            $this->payment_method = $voucher->payment_method;
            $this->cheque_no = $voucher->cheque_no;
            $this->cheque_date = $voucher->cheque_date ? $voucher->cheque_date->format('Y-m-d') : '';
            $this->reference_no = $voucher->reference_no;
            $this->description = $voucher->description;
        } else {
            $requestedType = request()->query('type');
            if (in_array(strtoupper($requestedType ?? ''), ['CPV', 'BPV'])) {
                $this->voucher_type = strtoupper($requestedType);
                $this->payment_method = $this->voucher_type === 'CPV' ? 'Cash' : 'Cheque';
            }
            $this->voucher_date = date('Y-m-d');
            if ($user && $user->branch_id) {
                $this->branch_id = $user->branch_id;
            }
            $this->refreshVoucherNo();
        }

        $this->loadCashBankAccounts();
    }

    public function updatedVoucherType($val)
    {
        $this->payment_method = $val === 'CPV' ? 'Cash' : 'Cheque';
        $this->loadCashBankAccounts();
        if (!$this->editId) {
            $this->refreshVoucherNo();
        }
    }

    public function updatedBranchId($val)
    {
        if (!$this->editId) {
            $this->refreshVoucherNo();
        }
    }

    public function refreshVoucherNo()
    {
        $service = app(PaymentVoucherService::class);
        $marqueeId = $this->getMarqueeId();
        $branchId = $this->branch_id ? (int)$this->branch_id : null;
        $this->voucher_no = $service->generateVoucherNo($this->voucher_type, $marqueeId, null, $branchId);
    }

    public function loadCashBankAccounts()
    {
        $marqueeId = $this->getMarqueeId();
        $query = CashBankAccount::with('account')->where('marquee_id', $marqueeId)->where('status', 'active');

        if ($this->voucher_type === 'CPV') {
            $query->where('type', 'cash');
        } else {
            $query->where('type', 'bank');
        }

        $this->cashBankAccounts = $query->get();
        if ($this->cashBankAccounts->isNotEmpty() && !$this->cash_bank_account_id) {
            $this->cash_bank_account_id = $this->cashBankAccounts->first()->id;
        }
    }

    public function setPayeeType($type)
    {
        $this->payee_type = $type;
        $this->updatedPayeeType($type);
    }

    public function updatedPayeeType($val)
    {
        $this->payee_id = '';
        $this->payee_name = '';
        $this->payee_cnic = '';
        $this->payee_phone = '';
        $this->payeeOutstandingBalance = null;

        $marqueeId = $this->getMarqueeId();

        // Automatically set default debit account based on payee type
        if ($val === 'supplier') {
            // Find Accounts Payable account 2001
            $apAccount = Account::where('marquee_id', $marqueeId)->where('account_code', '2001')->first();
            if ($apAccount) {
                $this->debit_account_id = $apAccount->id;
            }
        } elseif ($val === 'vendor') {
            // Find Out Sources or Vendor liability account
            $vendorAcc = Account::where('marquee_id', $marqueeId)
                ->where(function ($q) {
                    $q->where('name', 'like', '%Out Source%')
                      ->orWhere('name', 'like', '%Vendor%')
                      ->orWhere('account_code', '2001')
                      ->orWhere('name', 'like', '%Commission%');
                })->first();
            if ($vendorAcc) {
                $this->debit_account_id = $vendorAcc->id;
            }
        }
    }

    public function updatedPayeeId($val)
    {
        if (!$val) {
            $this->payeeOutstandingBalance = null;
            return;
        }

        $marqueeId = $this->getMarqueeId();

        if ($this->payee_type === 'supplier') {
            $sup = Supplier::where('marquee_id', $marqueeId)->find($val);
            if ($sup) {
                $this->payee_name = $sup->name;
                $this->payee_phone = $sup->mobile_number;
                $this->payeeOutstandingBalance = $sup->current_balance ?? 0;
                if (!$this->amount && $sup->current_balance > 0) {
                    $this->amount = $sup->current_balance;
                    $this->updatedAmount($this->amount);
                }
            }
        } elseif ($this->payee_type === 'vendor') {
            if (str_starts_with($val, 'srv_')) {
                // Standard service provider category
                $serviceMap = [
                    'srv_decor' => 'Stage & Theme Decoration',
                    'srv_floral' => 'Floral Setup & Bouquets',
                    'srv_sound' => 'Sound & Audio System / DJ',
                    'srv_lighting' => 'Lighting & Truss Setup',
                    'srv_photo' => 'Photography & Video Coverage',
                    'srv_catering' => 'Catering & Master Chef Staff',
                    'srv_crockery' => 'Crockery & Buffet Setup',
                    'srv_security' => 'Security Guard Services',
                    'srv_valet' => 'Valet Parking Services',
                    'srv_generator' => 'Generator & Power Rental',
                    'srv_janitorial' => 'Janitorial & Cleaning Crew',
                ];
                $serviceName = $serviceMap[$val] ?? 'Event Service Provider';
                $this->payee_name = $serviceName;
                $this->description = "Payment for {$serviceName}";
                $outSources = Account::where('marquee_id', $marqueeId)
                    ->where(function ($q) {
                        $q->where('name', 'like', '%Out Source%')
                          ->orWhere('name', 'like', '%Vendor%');
                    })->first();
                if ($outSources) {
                    $this->debit_account_id = $outSources->id;
                }
            } else {
                $vendId = str_replace('ven_', '', $val);
                $vend = Vendor::withoutGlobalScope('tenant')->where('marquee_id', $marqueeId)->find($vendId);
                if ($vend) {
                    $this->payee_name = $vend->name;
                    $this->payee_phone = $vend->phone;
                    $this->payeeOutstandingBalance = $vend->current_balance ?? 0;
                    if (!$this->amount && $vend->current_balance > 0) {
                        $this->amount = $vend->current_balance;
                        $this->updatedAmount($this->amount);
                    }
                }
            }
        } elseif ($this->payee_type === 'expense') {
            if (str_starts_with($val, 'exp_')) {
                // Unsettled expense voucher
                $expId = (int) str_replace('exp_', '', $val);
                $exp = Expense::with(['category', 'supplier', 'employee'])->where('marquee_id', $marqueeId)->find($expId);
                if ($exp) {
                    $this->payee_name = $exp->supplier ? $exp->supplier->name : ($exp->employee ? $exp->employee->name : $exp->expense_number);
                    $this->amount = $exp->total_amount;
                    $this->description = "Settlement for Expense Voucher: {$exp->expense_number} - {$exp->description}";
                    if ($exp->category && $exp->category->default_account_id) {
                        $this->debit_account_id = $exp->category->default_account_id;
                    }
                    $this->updatedAmount($this->amount);
                }
            } elseif (str_starts_with($val, 'acc_')) {
                // Direct GL Account
                $accId = (int) str_replace('acc_', '', $val);
                $acc = Account::where('marquee_id', $marqueeId)->find($accId);
                if ($acc) {
                    $this->payee_name = $acc->name;
                    $this->description = "Payment for {$acc->name}";
                    $this->debit_account_id = $acc->id;
                }
            } else {
                // Expense Category
                $catId = (int) str_replace('cat_', '', $val);
                $cat = ExpenseCategory::with('defaultAccount')->where('marquee_id', $marqueeId)->find($catId);
                if ($cat) {
                    $this->payee_name = $cat->name;
                    $this->description = "Payment for {$cat->name}";
                    if ($cat->default_account_id) {
                        $this->debit_account_id = $cat->default_account_id;
                    }
                }
            }
        }
    }

    public function updatedAmount($val)
    {
        $service = app(PaymentVoucherService::class);
        if (is_numeric($val) && (float)$val > 0) {
            $this->amount_in_words = $service->numberToWords((float)$val);
        } else {
            $this->amount_in_words = '';
        }
    }

    public function save()
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('manage_accounting')), 403, 'Unauthorized.');

        $service = app(PaymentVoucherService::class);
        $this->validate();

        $marqueeId = $this->getMarqueeId();

        // Validate cash_bank_account_id belongs to marquee
        $cbValid = CashBankAccount::where('marquee_id', $marqueeId)->where('id', (int) $this->cash_bank_account_id)->exists();
        if (!$cbValid) {
            $this->addError('cash_bank_account_id', 'Selected Cash/Bank account does not belong to your organization.');
            return;
        }

        // Validate debit_account_id belongs to marquee
        $debitValid = Account::where('marquee_id', $marqueeId)->where('id', (int) $this->debit_account_id)->exists();
        if (!$debitValid) {
            $this->addError('debit_account_id', 'Selected debit account does not belong to your organization.');
            return;
        }

        // Safely extract numeric payee_id if prefixed
        $cleanPayeeId = null;
        if (is_numeric($this->payee_id)) {
            $cleanPayeeId = (int) $this->payee_id;
        } elseif (is_string($this->payee_id) && preg_match('/^(?:ven_|exp_|cat_|acc_)?(\d+)$/', $this->payee_id, $matches)) {
            $cleanPayeeId = (int) $matches[1];
        }

        $payload = [
            'marquee_id' => $marqueeId,
            'branch_id' => $this->branch_id ?: null,
            'voucher_type' => $this->voucher_type,
            'voucher_no' => $this->voucher_no,
            'voucher_date' => $this->voucher_date,
            'payee_type' => $this->payee_type,
            'payee_id' => $cleanPayeeId,
            'payee_name' => $this->payee_name,
            'payee_cnic' => $this->payee_cnic,
            'payee_phone' => $this->payee_phone,
            'cash_bank_account_id' => $this->cash_bank_account_id,
            'debit_account_id' => $this->debit_account_id,
            'amount' => $this->amount,
            'amount_in_words' => $this->amount_in_words,
            'payment_method' => $this->payment_method,
            'cheque_no' => $this->cheque_no,
            'cheque_date' => $this->cheque_date ?: null,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'status' => $this->status,
        ];

        try {
            if ($this->editId) {
                $voucher = PaymentVoucher::where('marquee_id', $marqueeId)->findOrFail($this->editId);
                $service->updatePaymentVoucher($voucher, $payload, auth()->id());

                \App\Models\ActivityLog::create([
                    'user_id' => $user->id,
                    'marquee_id' => $marqueeId,
                    'action' => 'payment_voucher_updated',
                    'description' => "Updated Payment Voucher #{$voucher->voucher_no}",
                    'model_type' => PaymentVoucher::class,
                    'model_id' => $voucher->id,
                ]);

                session()->flash('success', "Payment Voucher {$voucher->voucher_no} updated successfully.");
            } else {
                $voucher = $service->createPaymentVoucher($payload, auth()->id());

                \App\Models\ActivityLog::create([
                    'user_id' => $user->id,
                    'marquee_id' => $marqueeId,
                    'action' => 'payment_voucher_created',
                    'description' => "Created Payment Voucher #{$voucher->voucher_no}",
                    'model_type' => PaymentVoucher::class,
                    'model_id' => $voucher->id,
                ]);

                session()->flash('success', "Payment Voucher {$voucher->voucher_no} created successfully.");
            }

            return redirect()->route('finance.payment-vouchers.show', $voucher->id);
        } catch (\Throwable $e) {
            session()->flash('error', 'Failed to save voucher: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.finance.payment-voucher-form', [
            'editId' => $this->editId,
            'voucher_type' => $this->voucher_type,
            'voucher_no' => $this->voucher_no,
            'voucher_date' => $this->voucher_date,
            'branch_id' => $this->branch_id,
            'status' => $this->status,
            'payee_type' => $this->payee_type,
            'payee_id' => $this->payee_id,
            'payee_name' => $this->payee_name,
            'payee_cnic' => $this->payee_cnic,
            'payee_phone' => $this->payee_phone,
            'cash_bank_account_id' => $this->cash_bank_account_id,
            'debit_account_id' => $this->debit_account_id,
            'amount' => $this->amount,
            'amount_in_words' => $this->amount_in_words,
            'payment_method' => $this->payment_method,
            'cheque_no' => $this->cheque_no,
            'cheque_date' => $this->cheque_date,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'branches' => $this->branches,
            'cashBankAccounts' => $this->cashBankAccounts,
            'debitAccounts' => $this->debitAccounts,
            'suppliers' => $this->suppliers,
            'vendors' => $this->vendors,
            'expenseCategories' => $this->expenseCategories,
            'expenses' => $this->expenses,
            'payeeOutstandingBalance' => $this->payeeOutstandingBalance,
        ]);
    }
}
