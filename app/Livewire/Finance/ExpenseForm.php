<?php

namespace App\Livewire\Finance;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\CashBankAccount;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\ExpenseType;
use App\Models\PettyCashAccount;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ExpenseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ExpenseForm extends Component
{
    use WithFileUploads;

    public $editId = null;

    // Header properties
    public $expense_number;
    public $expense_date;
    public $branch_id;
    public $department;
    public $cost_center;
    public $expense_category_id;
    public $expense_type_id;
    public $supplier_id;
    public $employee_id;
    public $booking_id;
    public $purchase_order_id;
    public $purchase_invoice_id;
    public $currency_id;
    public $exchange_rate = 1.000000;
    public $description;
    public $internal_notes;
    public $payment_method = 'Cash';
    public $cash_bank_account_id;
    public $petty_cash_account_id;
    public $due_date;
    public $reference_number;
    public $is_multiline = false;

    // Totals
    public $amount = 0.00;
    public $tax_amount = 0.00;
    public $discount_amount = 0.00;
    public $total_amount = 0.00;

    // Multi-line items
    public $items = [];

    // Utility Bill properties
    public $utility_type;
    public $consumer_number;
    public $account_number;
    public $billing_period;
    public $previous_reading;
    public $current_reading;
    public $late_charges = 0.00;

    // Maintenance properties
    public $maintenance_type;
    public $asset_name;
    public $scheduled_date;
    public $completion_date;
    public $warranty_period_months = 0;

    // Upload files
    public $uploadedFiles = [];
    public $existingAttachments = [];

    protected function rules()
    {
        $rules = [
            'expense_date' => 'required|date',
            'branch_id' => 'required|exists:branches,id',
            'expense_type_id' => 'nullable|exists:expense_types,id',
            'department' => 'nullable|string|max:100',
            'cost_center' => 'nullable|string|max:100',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'employee_id' => 'nullable|exists:employees,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'currency_id' => 'nullable|exists:currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'description' => 'nullable|string|max:1000',
            'internal_notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:Cash,Bank,Accounts Payable,Petty Cash',
            'cash_bank_account_id' => 'required_if:payment_method,Bank|nullable|exists:cash_bank_accounts,id',
            'petty_cash_account_id' => 'required_if:payment_method,Petty Cash|nullable|exists:petty_cash_accounts,id',
            'due_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'uploadedFiles.*' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx,xls,xlsx|max:10240', // 10MB Limit
        ];

        if ($this->is_multiline) {
            $rules['items'] = 'required|array|min:1';
            $rules['items.*.expense_category_id'] = 'required|exists:expense_categories,id';
            $rules['items.*.amount'] = 'required|numeric|min:0.01';
            $rules['items.*.tax_amount'] = 'nullable|numeric|min:0';
            $rules['items.*.discount_amount'] = 'nullable|numeric|min:0';
            $rules['items.*.description'] = 'nullable|string|max:255';
        } else {
            $rules['expense_category_id'] = 'required|exists:expense_categories,id';
            $rules['amount'] = 'required|numeric|min:0.01';
            $rules['tax_amount'] = 'nullable|numeric|min:0';
            $rules['discount_amount'] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    public function mount($id = null)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('create_expenses') || $user->hasPermission('manage_accounting') || $user->hasPermission('view_expenses')), 403, 'Unauthorized access to expenses.');

        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        // Load Default Currency
        $baseCurrency = Currency::where('marquee_id', $marqueeId)->where('is_base', true)->first()
            ?? Currency::where('marquee_id', $marqueeId)->first();
        $this->currency_id = $baseCurrency ? $baseCurrency->id : '';

        if ($id) {
            $expense = Expense::withoutGlobalScope('tenant')->with(['items', 'utilityBill', 'maintenanceRecord', 'attachments'])->findOrFail($id);
            if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($expense->marquee_id)) {
                abort(403, 'Unauthorized access to this expense.');
            }
            
            if ($expense->status !== Expense::STATUS_DRAFT && $expense->status !== Expense::STATUS_REJECTED && !request()->has('duplicate')) {
                return redirect()->route('expenses.show', $expense->id);
            }

            $this->expense_date = $expense->expense_date->format('Y-m-d');
            $this->branch_id = $expense->branch_id;
            $this->department = $expense->department;
            $this->cost_center = $expense->cost_center;
            $this->expense_category_id = $expense->expense_category_id;
            $this->expense_type_id = $expense->expense_type_id;
            $this->supplier_id = $expense->supplier_id;
            $this->employee_id = $expense->employee_id;
            $this->booking_id = $expense->booking_id;
            $this->purchase_order_id = $expense->purchase_order_id;
            $this->purchase_invoice_id = $expense->purchase_invoice_id;
            $this->currency_id = $expense->currency_id;
            $this->exchange_rate = (float)$expense->exchange_rate;
            $this->description = $expense->description;
            $this->internal_notes = $expense->internal_notes;
            $this->payment_method = $expense->payment_method;
            $this->cash_bank_account_id = $expense->cash_bank_account_id;
            $this->petty_cash_account_id = $expense->petty_cash_account_id;
            $this->due_date = $expense->due_date ? $expense->due_date->format('Y-m-d') : '';
            $this->reference_number = $expense->reference_number;

            $this->amount = (float)$expense->amount;
            $this->tax_amount = (float)$expense->tax_amount;
            $this->discount_amount = (float)$expense->discount_amount;
            $this->total_amount = (float)$expense->total_amount;

            if ($expense->items()->exists()) {
                $this->is_multiline = true;
                foreach ($expense->items as $item) {
                    $this->items[] = [
                        'expense_category_id' => $item->expense_category_id,
                        'description' => $item->description,
                        'amount' => (float)$item->amount,
                        'tax_amount' => (float)$item->tax_amount,
                        'discount_amount' => (float)$item->discount_amount,
                        'total_amount' => (float)$item->total_amount,
                    ];
                }
            } else {
                $this->is_multiline = false;
            }

            if ($expense->utilityBill) {
                $this->utility_type = $expense->utilityBill->utility_type;
                $this->consumer_number = $expense->utilityBill->consumer_number;
                $this->account_number = $expense->utilityBill->account_number;
                $this->billing_period = $expense->utilityBill->billing_period;
                $this->previous_reading = (float)$expense->utilityBill->previous_reading;
                $this->current_reading = (float)$expense->utilityBill->current_reading;
                $this->late_charges = (float)$expense->utilityBill->late_charges;
            }

            if ($expense->maintenanceRecord) {
                $this->maintenance_type = $expense->maintenanceRecord->maintenance_type;
                $this->asset_name = $expense->maintenanceRecord->asset_name;
                $this->scheduled_date = $expense->maintenanceRecord->scheduled_date->format('Y-m-d');
                $this->completion_date = $expense->maintenanceRecord->completion_date ? $expense->maintenanceRecord->completion_date->format('Y-m-d') : '';
                $this->warranty_period_months = $expense->maintenanceRecord->warranty_period_months;
            }

            if (request()->has('duplicate')) {
                // If duplicating, clear out ID references and generate new number
                $this->editId = null;
                $this->expense_number = null;
                $this->existingAttachments = [];
            } else {
                $this->editId = $expense->id;
                $this->expense_number = $expense->expense_number;
                $this->existingAttachments = $expense->attachments;
            }
        } else {
            $this->expense_date = date('Y-m-d');
            $this->branch_id = $user->branch_id ?: Branch::where('marquee_id', $marqueeId)->value('id');
            $this->payment_method = 'Cash';
            $this->items = [
                ['expense_category_id' => '', 'description' => '', 'amount' => '', 'tax_amount' => 0.00, 'discount_amount' => 0.00, 'total_amount' => 0.00]
            ];
        }
    }

    public function updatedAmount()
    {
        $this->recalculateTotals();
    }

    public function updatedTaxAmount()
    {
        $this->recalculateTotals();
    }

    public function updatedDiscountAmount()
    {
        $this->recalculateTotals();
    }

    public function updatedIsMultiline()
    {
        $this->recalculateTotals();
    }

    public function updatedItems()
    {
        $this->recalculateTotals();
    }

    public function updatedCurrencyId()
    {
        $marqueeId = auth()->user()->marquee_id;
        $currency = Currency::where('marquee_id', $marqueeId)->find($this->currency_id);
        if ($currency) {
            $this->exchange_rate = (float)$currency->exchange_rate;
        }
        $this->recalculateTotals();
    }

    public function addRow()
    {
        $this->items[] = [
            'expense_category_id' => '',
            'description' => '',
            'amount' => '',
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 0.00,
        ];
    }

    public function removeRow($index)
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        if ($this->is_multiline) {
            $subtotal = 0.0;
            $tax = 0.0;
            $discount = 0.0;

            foreach ($this->items as $index => $item) {
                $itemAmt = (isset($item['amount']) && is_numeric($item['amount'])) ? (float)$item['amount'] : 0.0;
                $itemTax = (isset($item['tax_amount']) && is_numeric($item['tax_amount'])) ? (float)$item['tax_amount'] : 0.0;
                $itemDisc = (isset($item['discount_amount']) && is_numeric($item['discount_amount'])) ? (float)$item['discount_amount'] : 0.0;

                $total = $itemAmt + $itemTax - $itemDisc;
                $this->items[$index]['total_amount'] = $total;

                $subtotal += $itemAmt;
                $tax += $itemTax;
                $discount += $itemDisc;
            }

            $this->amount = $subtotal;
            $this->tax_amount = $tax;
            $this->discount_amount = $discount;
        }

        $amt = (isset($this->amount) && is_numeric($this->amount)) ? (float)$this->amount : 0.0;
        $tax = (isset($this->tax_amount) && is_numeric($this->tax_amount)) ? (float)$this->tax_amount : 0.0;
        $disc = (isset($this->discount_amount) && is_numeric($this->discount_amount)) ? (float)$this->discount_amount : 0.0;
        $late = ($this->isUtilityBill() && isset($this->late_charges) && is_numeric($this->late_charges)) ? (float)$this->late_charges : 0.0;

        $this->total_amount = $amt + $tax - $disc + $late;
    }

    public function isUtilityBill(): bool
    {
        if (!$this->expense_type_id) {
            return false;
        }
        $code = ExpenseType::where('id', $this->expense_type_id)->value('code');
        return in_array($code, ['utility_bills', 'electricity', 'gas', 'water', 'internet', 'telephone']);
    }

    public function isMaintenance(): bool
    {
        if (!$this->expense_type_id) {
            return false;
        }
        $code = ExpenseType::where('id', $this->expense_type_id)->value('code');
        return in_array($code, ['maintenance', 'repairs', 'asset_maintenance']);
    }

    public function generateNextExpenseNumber(int $marqueeId, ?int $branchId = null): string
    {
        $datePrefix = date('Ymd');
        
        $latest = Expense::withTrashed()
            ->where('marquee_id', $marqueeId)
            ->where('expense_number', 'like', "EXP-{$datePrefix}-%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(expense_number, "-", -1) AS UNSIGNED) DESC')
            ->value('expense_number');

        $nextSequence = 1;
        if ($latest) {
            $parts = explode('-', $latest);
            $lastSeq = end($parts);
            if (is_numeric($lastSeq)) {
                $nextSequence = (int)$lastSeq + 1;
            }
        } else {
            $overallLatest = Expense::withTrashed()
                ->where('marquee_id', $marqueeId)
                ->orderByRaw('CAST(SUBSTRING_INDEX(expense_number, "-", -1) AS UNSIGNED) DESC')
                ->value('expense_number');
            if ($overallLatest) {
                $parts = explode('-', $overallLatest);
                $lastSeq = end($parts);
                if (is_numeric($lastSeq)) {
                    $nextSequence = (int)$lastSeq + 1;
                }
            }
        }

        return "EXP-{$datePrefix}-" . str_pad((string)$nextSequence, 5, '0', STR_PAD_LEFT);
    }

    public function saveDraft()
    {
        return $this->saveExpense(Expense::STATUS_DRAFT);
    }

    public function submit()
    {
        return $this->saveExpense(Expense::STATUS_SUBMITTED);
    }

    protected function saveExpense(string $statusToSet)
    {
        $this->recalculateTotals();
        $this->validate();

        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasPermission('create_expenses') || $user->hasPermission('manage_accounting') || $user->hasPermission('edit_expenses')), 403, 'Unauthorized.');

        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        // Ensure base currency
        $resolvedCurrencyId = $this->currency_id 
            ?: (Currency::where('marquee_id', $marqueeId)->where('is_base', true)->value('id') 
                ?? Currency::where('marquee_id', $marqueeId)->value('id'));
        
        // Auto resolve operational type if not set
        $resolvedTypeId = $this->expense_type_id 
            ?: ExpenseType::where('marquee_id', $marqueeId)->value('id');

        // Calculate base totals
        $rate = is_numeric($this->exchange_rate) && (float)$this->exchange_rate > 0 ? (float)$this->exchange_rate : 1.0;
        $totalBase = $this->total_amount * $rate;

        // Guaranteed unique sequence number on create
        $expenseNum = $this->editId 
            ? Expense::where('id', $this->editId)->value('expense_number')
            : $this->generateNextExpenseNumber($marqueeId, $this->branch_id);

        $data = [
            'marquee_id' => $marqueeId,
            'branch_id' => $this->branch_id ?: null,
            'expense_number' => $expenseNum,
            'expense_date' => $this->expense_date,
            'department' => $this->department ?: null,
            'cost_center' => $this->cost_center ?: null,
            'expense_category_id' => !$this->is_multiline ? $this->expense_category_id : null,
            'expense_type_id' => $resolvedTypeId,
            'supplier_id' => $this->supplier_id ?: null,
            'employee_id' => $this->employee_id ?: null,
            'booking_id' => $this->booking_id ?: null,
            'purchase_order_id' => $this->purchase_order_id ?: null,
            'purchase_invoice_id' => $this->purchase_invoice_id ?: null,
            'currency_id' => $resolvedCurrencyId,
            'exchange_rate' => $rate,
            'description' => $this->description,
            'internal_notes' => $this->internal_notes,
            'amount' => (float)($this->amount ?: 0),
            'tax_amount' => (float)($this->tax_amount ?: 0),
            'discount_amount' => (float)($this->discount_amount ?: 0),
            'total_amount' => (float)($this->total_amount ?: $this->amount ?: 0),
            'total_amount_base' => $totalBase ?: (float)($this->total_amount ?: $this->amount ?: 0),
            'payment_method' => $this->payment_method,
            'cash_bank_account_id' => $this->payment_method === 'Bank' ? $this->cash_bank_account_id : null,
            'petty_cash_account_id' => $this->payment_method === 'Petty Cash' ? $this->petty_cash_account_id : null,
            'payment_status' => $this->payment_method === 'Accounts Payable' ? 'Unpaid' : 'Paid',
            'status' => Expense::STATUS_DRAFT, // Always initialize as draft before submission workflow
            'due_date' => $this->due_date ?: null,
            'reference_number' => $this->reference_number ?: null,
        ];

        try {
            $createdExpense = null;

            DB::transaction(function () use ($data, $statusToSet, $user, &$createdExpense) {
                if ($this->editId) {
                    $expense = Expense::withoutGlobalScope('tenant')->findOrFail($this->editId);
                    if (!$user->isSuperAdmin() && !$user->hasAccessToMarquee($expense->marquee_id)) {
                        abort(403, 'Unauthorized access to this expense.');
                    }
                    $expense->update($data);
                    $expense->items()->delete();
                } else {
                    $expense = Expense::create($data);
                }

                // Create multi-line items if active
                if ($this->is_multiline) {
                    foreach ($this->items as $item) {
                        ExpenseItem::create([
                            'expense_id' => $expense->id,
                            'expense_category_id' => $item['expense_category_id'],
                            'description' => $item['description'] ?: null,
                            'amount' => (float)($item['amount'] ?: 0),
                            'tax_amount' => (float)($item['tax_amount'] ?: 0),
                            'discount_amount' => (float)($item['discount_amount'] ?: 0),
                            'total_amount' => (float)($item['total_amount'] ?: 0),
                        ]);
                    }
                }

                // Create/Update Utility Detail only if specific details provided
                if ($this->isUtilityBill() && !empty($this->consumer_number)) {
                    $expense->utilityBill()->updateOrCreate(
                        ['expense_id' => $expense->id],
                        [
                            'utility_type' => $this->utility_type ?: 'Electricity',
                            'consumer_number' => $this->consumer_number,
                            'account_number' => $this->account_number ?: null,
                            'billing_period' => $this->billing_period ?: date('F Y'),
                            'previous_reading' => $this->previous_reading ?: null,
                            'current_reading' => $this->current_reading ?: null,
                            'late_charges' => $this->late_charges ?: 0.00,
                        ]
                    );
                } else {
                    $expense->utilityBill()?->delete();
                }

                // Create/Update Maintenance Detail only if specific asset details provided
                if ($this->isMaintenance() && !empty($this->asset_name)) {
                    $expense->maintenanceRecord()->updateOrCreate(
                        ['expense_id' => $expense->id],
                        [
                            'maintenance_type' => $this->maintenance_type ?: 'General Repair',
                            'asset_name' => $this->asset_name,
                            'scheduled_date' => $this->scheduled_date ?: date('Y-m-d'),
                            'completion_date' => $this->completion_date ?: null,
                            'warranty_period_months' => $this->warranty_period_months ?: 0,
                        ]
                    );
                } else {
                    $expense->maintenanceRecord()?->delete();
                }

                // Upload Attachments
                foreach ($this->uploadedFiles as $file) {
                    $path = $file->store('expense_receipts', 'public');
                    ExpenseAttachment::create([
                        'expense_id' => $expense->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }

                // If user clicked "Record & Post Expense", submit through workflow
                if ($statusToSet === Expense::STATUS_SUBMITTED) {
                    app(ExpenseService::class)->submitExpense($expense->id);
                }

                $createdExpense = $expense;
            });

            // Reset state
            $this->expense_number = null;

            $msg = ($statusToSet === Expense::STATUS_SUBMITTED) 
                ? "Expense {$createdExpense->expense_number} recorded and processed successfully." 
                : "Expense {$createdExpense->expense_number} saved as draft.";

            session()->flash('success', $msg);
            return redirect()->route('expenses.index');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("ExpenseForm save error: " . $e->getMessage());
            session()->flash('error', $e->getMessage());
        }
    }

    public function removeAttachment($id)
    {
        $attachment = ExpenseAttachment::findOrFail($id);
        // Delete physical file
        if (\Storage::disk('public')->exists($attachment->file_path)) {
            \Storage::disk('public')->delete($attachment->file_path);
        }
        $attachment->delete();
        $this->existingAttachments = $this->existingAttachments->where('id', '!=', $id);
    }

    public function render()
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        $branches = Branch::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $categories = ExpenseCategory::where('marquee_id', $marqueeId)->where('is_active', true)->get();
        $expenseTypes = ExpenseType::where('marquee_id', $marqueeId)->where('is_active', true)->get();
        $suppliers = Supplier::where('marquee_id', $marqueeId)->get();
        $employees = Employee::where('marquee_id', $marqueeId)->where('status', 'active')->get();
        $bookings = Booking::with('customer')
            ->when($marqueeId, fn($q) => $q->where('marquee_id', $marqueeId))
            ->orderBy('booking_date', 'desc')
            ->get();
        $purchaseOrders = PurchaseOrder::where('marquee_id', $marqueeId)->get();
        $purchaseInvoices = PurchaseInvoice::where('marquee_id', $marqueeId)->get();
        $currencies = Currency::where('marquee_id', $marqueeId)->where('is_active', true)->get();

        $cashAccounts = CashBankAccount::where('marquee_id', $marqueeId)->get();
        $pettyDrawers = PettyCashAccount::where('marquee_id', $marqueeId)->where('is_active', true)->get();

        $departments = [
            'Administration',
            'Kitchen / Catering',
            'Event Decoration',
            'Housekeeping & Janitorial',
            'Security',
            'Logistics / Transport',
            'Marketing & Sales',
            'Maintenance',
        ];

        return view('livewire.finance.expense-form', [
            'branches' => $branches,
            'categories' => $categories,
            'expenseTypes' => $expenseTypes,
            'suppliers' => $suppliers,
            'employees' => $employees,
            'bookings' => $bookings,
            'purchaseOrders' => $purchaseOrders,
            'purchaseInvoices' => $purchaseInvoices,
            'currencies' => $currencies,
            'cashAccounts' => $cashAccounts,
            'pettyDrawers' => $pettyDrawers,
            'departments' => $departments,
        ]);
    }
}
