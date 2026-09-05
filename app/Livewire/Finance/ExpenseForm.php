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
            'department' => 'nullable|string|max:100',
            'cost_center' => 'nullable|string|max:100',
            'expense_type_id' => 'required|exists:expense_types,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'employee_id' => 'nullable|exists:employees,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'currency_id' => 'required|exists:currencies,id',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'description' => 'nullable|string',
            'internal_notes' => 'nullable|string',
            'payment_method' => 'required|in:Cash,Bank,Accounts Payable,Petty Cash',
            'cash_bank_account_id' => 'required_if:payment_method,Bank|nullable|exists:cash_bank_accounts,id',
            'petty_cash_account_id' => 'required_if:payment_method,Petty Cash|nullable|exists:petty_cash_accounts,id',
            'due_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'uploadedFiles.*' => 'nullable|file|max:10240', // 10MB Limit
        ];

        if ($this->is_multiline) {
            $rules['items'] = 'required|array|min:1';
            $rules['items.*.expense_category_id'] = 'required|exists:expense_categories,id';
            $rules['items.*.amount'] = 'required|numeric|min:0.01';
            $rules['items.*.tax_amount'] = 'required|numeric|min:0';
            $rules['items.*.discount_amount'] = 'required|numeric|min:0';
            $rules['items.*.description'] = 'nullable|string|max:255';
        } else {
            $rules['expense_category_id'] = 'required|exists:expense_categories,id';
            $rules['amount'] = 'required|numeric|min:0.01';
            $rules['tax_amount'] = 'required|numeric|min:0';
            $rules['discount_amount'] = 'required|numeric|min:0';
        }

        // Conditional Utility rules
        if ($this->isUtilityBill()) {
            $rules['utility_type'] = 'required|in:Electricity,Gas,Water,Internet,Telephone';
            $rules['consumer_number'] = 'required|string|max:100';
            $rules['billing_period'] = 'required|string|max:50';
            $rules['previous_reading'] = 'nullable|numeric|min:0';
            $rules['current_reading'] = 'nullable|numeric|min:0';
            $rules['late_charges'] = 'required|numeric|min:0';
        }

        // Conditional Maintenance rules
        if ($this->isMaintenance()) {
            $rules['maintenance_type'] = 'required|string|max:100';
            $rules['asset_name'] = 'required|string|max:100';
            $rules['scheduled_date'] = 'required|date';
            $rules['completion_date'] = 'nullable|date';
            $rules['warranty_period_months'] = 'required|integer|min:0';
        }

        return $rules;
    }

    public function mount($id = null)
    {
        $user = auth()->user();
        $marqueeId = $user ? ($user->getActiveMarqueeId() ?: $user->marquee_id) : null;

        // Load Default Currency
        $baseCurrency = Currency::where('marquee_id', $marqueeId)->where('is_base', true)->first()
            ?? Currency::where('marquee_id', $marqueeId)->first();
        $this->currency_id = $baseCurrency ? $baseCurrency->id : '';

        if ($id) {
            $expense = Expense::with(['items', 'utilityBill', 'maintenanceRecord', 'attachments'])->findOrFail($id);
            
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
            if ($user->branch_id) {
                $this->branch_id = $user->branch_id;
            }
            $this->items = [
                ['expense_category_id' => '', 'description' => '', 'amount' => '', 'tax_amount' => 0.00, 'discount_amount' => 0.00, 'total_amount' => 0.00]
            ];
        }
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
            $subtotal = 0;
            $tax = 0;
            $discount = 0;

            foreach ($this->items as $index => $item) {
                $itemAmt = (float)($item['amount'] ?: 0);
                $itemTax = (float)($item['tax_amount'] ?: 0);
                $itemDisc = (float)($item['discount_amount'] ?: 0);

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

        // Add late charges to utility bill subtotal if applicable
        $late = $this->isUtilityBill() ? (float)($this->late_charges ?: 0) : 0;

        $this->total_amount = $this->amount + $this->tax_amount - $this->discount_amount + $late;
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

    public function saveDraft()
    {
        $this->saveExpense(Expense::STATUS_DRAFT);
    }

    public function submit()
    {
        $this->saveExpense(Expense::STATUS_SUBMITTED);
    }

    protected function saveExpense(string $statusToSet)
    {
        $this->recalculateTotals();
        $this->validate();

        $marqueeId = auth()->user()->marquee_id;

        // Auto Number generation
        if (!$this->expense_number) {
            $count = Expense::withTrashed()->where('marquee_id', $marqueeId)->count();
            $this->expense_number = 'EXP-' . date('Ymd') . '-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        }

        // Calculate base totals
        $rate = (float)($this->exchange_rate ?: 1.00);
        $totalBase = $this->total_amount * $rate;

        $data = [
            'marquee_id' => $marqueeId,
            'branch_id' => $this->branch_id ?: null,
            'expense_number' => $this->expense_number,
            'expense_date' => $this->expense_date,
            'department' => $this->department ?: null,
            'cost_center' => $this->cost_center ?: null,
            'expense_category_id' => !$this->is_multiline ? $this->expense_category_id : null,
            'expense_type_id' => $this->expense_type_id,
            'supplier_id' => $this->supplier_id ?: null,
            'employee_id' => $this->employee_id ?: null,
            'booking_id' => $this->booking_id ?: null,
            'purchase_order_id' => $this->purchase_order_id ?: null,
            'purchase_invoice_id' => $this->purchase_invoice_id ?: null,
            'currency_id' => $this->currency_id,
            'exchange_rate' => $rate,
            'description' => $this->description,
            'internal_notes' => $this->internal_notes,
            'amount' => $this->amount,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'total_amount_base' => $totalBase,
            'payment_method' => $this->payment_method,
            'cash_bank_account_id' => $this->payment_method === 'Bank' ? $this->cash_bank_account_id : null,
            'petty_cash_account_id' => $this->payment_method === 'Petty Cash' ? $this->petty_cash_account_id : null,
            'payment_status' => $this->payment_method === 'Accounts Payable' ? 'Unpaid' : 'Paid',
            'status' => $statusToSet,
            'due_date' => $this->due_date ?: null,
            'reference_number' => $this->reference_number ?: null,
        ];

        try {
            DB::transaction(function () use ($data, $statusToSet) {
                if ($this->editId) {
                    $expense = Expense::findOrFail($this->editId);
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
                            'amount' => $item['amount'],
                            'tax_amount' => $item['tax_amount'],
                            'discount_amount' => $item['discount_amount'],
                            'total_amount' => $item['total_amount'],
                        ]);
                    }
                }

                // Create/Update Utility Detail
                if ($this->isUtilityBill()) {
                    $expense->utilityBill()->updateOrCreate(
                        ['expense_id' => $expense->id],
                        [
                            'utility_type' => $this->utility_type,
                            'consumer_number' => $this->consumer_number,
                            'account_number' => $this->account_number ?: null,
                            'billing_period' => $this->billing_period,
                            'previous_reading' => $this->previous_reading ?: null,
                            'current_reading' => $this->current_reading ?: null,
                            'late_charges' => $this->late_charges ?: 0.00,
                        ]
                    );
                } else {
                    $expense->utilityBill()->delete();
                }

                // Create/Update Maintenance Detail
                if ($this->isMaintenance()) {
                    $expense->maintenanceRecord()->updateOrCreate(
                        ['expense_id' => $expense->id],
                        [
                            'maintenance_type' => $this->maintenance_type,
                            'asset_name' => $this->asset_name,
                            'scheduled_date' => $this->scheduled_date,
                            'completion_date' => $this->completion_date ?: null,
                            'warranty_period_months' => $this->warranty_period_months ?: 0,
                        ]
                    );
                } else {
                    $expense->maintenanceRecord()->delete();
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

                // Auto-run submission workflow triggers if Status is Submitted
                if ($statusToSet === Expense::STATUS_SUBMITTED) {
                    app(ExpenseService::class)->submitExpense($expense->id);
                }
            });

            session()->flash('success', 'Expense recorded successfully.');
            return redirect()->route('expenses.index');
        } catch (\Exception $e) {
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
