<div>
    <div class="card mb-3">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <span class="fas fa-file-invoice-dollar me-2 text-primary"></span>
                {{ $editId ? 'Edit Expense Record: ' . $expense_number : 'Create Expense Voucher' }}
            </h5>
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center mb-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form wire:submit.prevent="saveDraft">
                <div class="row g-3">
                    <!-- General Metadata Section -->
                    <div class="col-md-3">
                        <label class="form-label" for="exp_date">Expense Date</label>
                        <input wire:model="expense_date" type="date" class="form-control form-control-sm @error('expense_date') is-invalid @enderror" id="exp_date">
                        @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="br_id">Branch</label>
                        <select wire:model="branch_id" class="form-select form-select-sm @error('branch_id') is-invalid @enderror" id="br_id">
                            <option value="">Select branch</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="dept">Department</label>
                        <select wire:model="department" class="form-select form-select-sm @error('department') is-invalid @enderror" id="dept">
                            <option value="">Select department</option>
                            @foreach($departments as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="cc_id">Cost Center</label>
                        <input wire:model="cost_center" type="text" class="form-control form-control-sm @error('cost_center') is-invalid @enderror" id="cc_id" placeholder="e.g. CC-KITCHEN">
                        @error('cost_center') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Category and Type -->
                    <div class="col-md-4">
                        <label class="form-label" for="exp_type">Expense Type</label>
                        <select wire:model.live="expense_type_id" class="form-select form-select-sm @error('expense_type_id') is-invalid @enderror" id="exp_type">
                            <option value="">Select operational type</option>
                            @foreach($expenseTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('expense_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if(!$is_multiline)
                        <div class="col-md-4">
                            <label class="form-label" for="exp_cat">GL Category</label>
                            <select wire:model="expense_category_id" class="form-select form-select-sm @error('expense_category_id') is-invalid @enderror" id="exp_cat">
                                <option value="">Select category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('expense_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input wire:model.live="is_multiline" class="form-check-input" type="checkbox" id="multiline_chk">
                            <label class="form-check-label mb-0" for="multiline_chk">Split across multiple lines</label>
                        </div>
                    </div>

                    <!-- Supplier & Relations -->
                    <div class="col-md-4">
                        <label class="form-label" for="supplier">Vendor / Supplier (Optional)</label>
                        <select wire:model="supplier_id" class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" id="supplier">
                            <option value="">Select supplier</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">[{{ $sup->supplier_code }}] {{ $sup->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="employee">Employee / Custodian (Optional)</label>
                        <select wire:model="employee_id" class="form-select form-select-sm @error('employee_id') is-invalid @enderror" id="employee">
                            <option value="">Select employee</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">[{{ $emp->employee_id }}] {{ $emp->name }}</option>
                            @endforeach
                        </select>
                        @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="booking">Related Event Booking (Optional)</label>
                        <select wire:model="booking_id" class="form-select form-select-sm @error('booking_id') is-invalid @enderror" id="booking">
                            <option value="">Select booking reference</option>
                            @foreach($bookings as $bk)
                                <option value="{{ $bk->id }}">{{ $bk->booking_number }} - {{ $bk->customer->name ?? '' }} ({{ $bk->event_date->format('Y-m-d') }})</option>
                            @endforeach
                        </select>
                        @error('booking_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Purchase relations -->
                    <div class="col-md-6">
                        <label class="form-label" for="po">Related Purchase Order (Optional)</label>
                        <select wire:model="purchase_order_id" class="form-select form-select-sm @error('purchase_order_id') is-invalid @enderror" id="po">
                            <option value="">Select PO</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}">{{ $po->po_number }} ({{ number_format($po->net_amount, 2) }} PKR)</option>
                            @endforeach
                        </select>
                        @error('purchase_order_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="invoice">Related Purchase Invoice (Optional)</label>
                        <select wire:model="purchase_invoice_id" class="form-select form-select-sm @error('purchase_invoice_id') is-invalid @enderror" id="invoice">
                            <option value="">Select Purchase Invoice</option>
                            @foreach($purchaseInvoices as $pi)
                                <option value="{{ $pi->id }}">{{ $pi->invoice_number }} ({{ number_format($pi->net_amount, 2) }} PKR)</option>
                            @endforeach
                        </select>
                        @error('purchase_invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- DYNAMIC SUB-FORMS -->
                    @if($this->isUtilityBill())
                        <div class="col-12">
                            <div class="card border border-primary bg-light">
                                <div class="card-header bg-primary text-white py-2">
                                    <h6 class="mb-0 text-white"><span class="fas fa-plug me-2"></span>Utility Bill Information Panel</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label text-900" for="ut_type">Utility Service Type</label>
                                            <select wire:model="utility_type" class="form-select form-select-sm @error('utility_type') is-invalid @enderror" id="ut_type">
                                                <option value="">Select type</option>
                                                <option value="Electricity">Electricity</option>
                                                <option value="Gas">Gas</option>
                                                <option value="Water">Water</option>
                                                <option value="Internet">Internet</option>
                                                <option value="Telephone">Telephone</option>
                                            </select>
                                            @error('utility_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label text-900" for="con_num">Consumer Number</label>
                                            <input wire:model="consumer_number" type="text" class="form-control form-control-sm @error('consumer_number') is-invalid @enderror" id="con_num" placeholder="e.g. 15-23423-234">
                                            @error('consumer_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label text-900" for="acc_num">Account Number (Optional)</label>
                                            <input wire:model="account_number" type="text" class="form-control form-control-sm @error('account_number') is-invalid @enderror" id="acc_num">
                                            @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label text-900" for="bill_period">Billing Period</label>
                                            <input wire:model="billing_period" type="text" class="form-control form-control-sm @error('billing_period') is-invalid @enderror" id="bill_period" placeholder="e.g. August 2026">
                                            @error('billing_period') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label text-900" for="prev_read">Previous Reading (Units)</label>
                                            <input wire:model="previous_reading" type="number" step="0.01" class="form-control form-control-sm @error('previous_reading') is-invalid @enderror" id="prev_read">
                                            @error('previous_reading') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label text-900" for="curr_read">Current Reading (Units)</label>
                                            <input wire:model="current_reading" type="number" step="0.01" class="form-control form-control-sm @error('current_reading') is-invalid @enderror" id="curr_read">
                                            @error('current_reading') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label text-900" for="late_ch">Late Charges (PKR)</label>
                                            <input wire:model.live="late_charges" type="number" step="0.01" class="form-control form-control-sm @error('late_charges') is-invalid @enderror" id="late_ch">
                                            @error('late_charges') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($this->isMaintenance())
                        <div class="col-12">
                            <div class="card border border-info bg-light">
                                <div class="card-header bg-info text-white py-2">
                                    <h6 class="mb-0 text-white"><span class="fas fa-tools me-2"></span>Equipment & Property Maintenance Panel</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label text-900" for="maint_type">Maintenance Category</label>
                                            <input wire:model="maintenance_type" type="text" class="form-control form-control-sm @error('maintenance_type') is-invalid @enderror" id="maint_type" placeholder="e.g. Generator Tuning, AC Gas Fill">
                                            @error('maintenance_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label text-900" for="asset_name">Asset / Item Name</label>
                                            <input wire:model="asset_name" type="text" class="form-control form-control-sm @error('asset_name') is-invalid @enderror" id="asset_name" placeholder="e.g. Cummins 100KVA Gen, Hall AC #4">
                                            @error('asset_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label text-900" for="warranty">Warranty Cover Period (Months)</label>
                                            <input wire:model="warranty_period_months" type="number" class="form-control form-control-sm @error('warranty_period_months') is-invalid @enderror" id="warranty">
                                            @error('warranty_period_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-900" for="sch_date">Scheduled Maintenance Date</label>
                                            <input wire:model="scheduled_date" type="date" class="form-control form-control-sm @error('scheduled_date') is-invalid @enderror" id="sch_date">
                                            @error('scheduled_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-900" for="comp_date">Completion Date</label>
                                            <input wire:model="completion_date" type="date" class="form-control form-control-sm @error('completion_date') is-invalid @enderror" id="comp_date">
                                            @error('completion_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Line Items or Single-line inputs -->
                    @if($is_multiline)
                        <div class="col-12 mt-4">
                            <h6 class="border-bottom pb-2"><span class="fas fa-list me-2"></span>Line Items Split</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered fs-10 align-middle">
                                    <thead class="bg-200">
                                        <tr>
                                            <th style="width: 25%;">Expense Category</th>
                                            <th style="width: 35%;">Description</th>
                                            <th class="text-end" style="width: 12%;">Amount</th>
                                            <th class="text-end" style="width: 12%;">Tax</th>
                                            <th class="text-end" style="width: 12%;">Discount</th>
                                            <th class="text-end" style="width: 12%;">Total</th>
                                            <th class="text-center" style="width: 5%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $index => $item)
                                            <tr>
                                                <td>
                                                    <select wire:model="items.{{ $index }}.expense_category_id" class="form-select form-select-sm">
                                                        <option value="">Select Category</option>
                                                        @foreach($categories as $cat)
                                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input wire:model="items.{{ $index }}.description" type="text" class="form-control form-control-sm">
                                                </td>
                                                <td>
                                                    <input wire:model.live="items.{{ $index }}.amount" type="number" step="0.01" class="form-control form-control-sm text-end">
                                                </td>
                                                <td>
                                                    <input wire:model.live="items.{{ $index }}.tax_amount" type="number" step="0.01" class="form-control form-control-sm text-end">
                                                </td>
                                                <td>
                                                    <input wire:model.live="items.{{ $index }}.discount_amount" type="number" step="0.01" class="form-control form-control-sm text-end">
                                                </td>
                                                <td class="text-end fw-bold font-monospace">
                                                    {{ number_format($item['total_amount'], 2) }} PKR
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" wire:click="removeRow({{ $index }})" class="btn btn-link p-0 text-danger">
                                                        <span class="fas fa-trash-alt"></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" wire:click="addRow" class="btn btn-falcon-default btn-sm mt-2">
                                <span class="fas fa-plus me-1"></span>Add Line Row
                            </button>
                        </div>
                    @else
                        <!-- Single line amount inputs -->
                        <div class="col-md-3">
                            <label class="form-label" for="sa_amt">Subtotal Amount (PKR)</label>
                            <input wire:model.live="amount" type="number" step="0.01" class="form-control form-control-sm @error('amount') is-invalid @enderror" id="sa_amt">
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="sa_tax">Tax Amount (PKR)</label>
                            <input wire:model.live="tax_amount" type="number" step="0.01" class="form-control form-control-sm @error('tax_amount') is-invalid @enderror" id="sa_tax">
                            @error('tax_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="sa_disc">Discount (PKR)</label>
                            <input wire:model.live="discount_amount" type="number" step="0.01" class="form-control form-control-sm @error('discount_amount') is-invalid @enderror" id="sa_disc">
                            @error('discount_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3 d-flex flex-column justify-content-end pb-1 text-end">
                            <span class="text-muted fs-11">Total Base Rate</span>
                            <h4 class="fw-bold font-monospace text-primary mb-0">
                                {{ number_format($total_amount, 2) }} PKR
                            </h4>
                        </div>
                    @endif

                    <!-- Multi-Currency Configuration -->
                    <div class="col-md-4">
                        <label class="form-label" for="currency">Billing Currency</label>
                        <select wire:model.live="currency_id" class="form-select form-select-sm @error('currency_id') is-invalid @enderror" id="currency">
                            @foreach($currencies as $curr)
                                <option value="{{ $curr->id }}">{{ $curr->code }} - {{ $curr->name }}</option>
                            @endforeach
                        </select>
                        @error('currency_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="exch_rate">Exchange Rate (vs Base)</label>
                        <input wire:model.live="exchange_rate" type="number" step="0.000001" class="form-control form-control-sm @error('exchange_rate') is-invalid @enderror" id="exch_rate">
                        @error('exchange_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 d-flex flex-column justify-content-end pb-1 text-end">
                        @if($currency_id)
                            @php
                                $currCode = $currencies->firstWhere('id', $currency_id)->code ?? '';
                            @endphp
                            <span class="text-muted fs-11">Transactional Currency Total</span>
                            <h5 class="fw-bold font-monospace text-secondary mb-0">
                                {{ number_format($total_amount, 2) }} {{ $currCode }}
                            </h5>
                        @endif
                    </div>

                    <!-- Payment Terms & Mappings -->
                    <div class="col-md-4">
                        <label class="form-label" for="pay_method">Payment Method</label>
                        <select wire:model.live="payment_method" class="form-select form-select-sm @error('payment_method') is-invalid @enderror" id="pay_method">
                            <option value="Cash">Cash in Hand</option>
                            <option value="Bank">Bank account</option>
                            <option value="Petty Cash">Petty Cash Box</option>
                            <option value="Accounts Payable">Accounts Payable (Credit Purchase)</option>
                        </select>
                        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if($payment_method === 'Bank')
                        <div class="col-md-4">
                            <label class="form-label" for="cb_acc">Cash / Bank Account</label>
                            <select wire:model="cash_bank_account_id" class="form-select form-select-sm @error('cash_bank_account_id') is-invalid @enderror" id="cb_acc">
                                <option value="">Select bank account</option>
                                @foreach($cashAccounts as $ca)
                                    <option value="{{ $ca->id }}">{{ $ca->account_name }} ({{ $ca->account_number }})</option>
                                @endforeach
                            </select>
                            @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @elseif($payment_method === 'Petty Cash')
                        <div class="col-md-4">
                            <label class="form-label" for="petty_acc">Petty Cash Drawer</label>
                            <select wire:model="petty_cash_account_id" class="form-select form-select-sm @error('petty_cash_account_id') is-invalid @enderror" id="petty_acc">
                                <option value="">Select petty drawer</option>
                                @foreach($pettyDrawers as $pd)
                                    <option value="{{ $pd->id }}">{{ $pd->account_name }} (Bal: {{ number_format($pd->current_balance, 2) }} PKR)</option>
                                @endforeach
                            </select>
                            @error('petty_cash_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    @if($payment_method === 'Accounts Payable')
                        <div class="col-md-4">
                            <label class="form-label" for="due_dt">Due Date</label>
                            <input wire:model="due_date" type="date" class="form-control form-control-sm @error('due_date') is-invalid @enderror" id="due_dt">
                            @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <div class="col-md-4">
                        <label class="form-label" for="ref_no">Ref Number / Bill Invoice #</label>
                        <input wire:model="reference_number" type="text" class="form-control form-control-sm @error('reference_number') is-invalid @enderror" id="ref_no" placeholder="Receipt / transaction reference">
                        @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Notes -->
                    <div class="col-md-6">
                        <label class="form-label" for="desc">Public Description (Show in Voucher)</label>
                        <textarea wire:model="description" class="form-control form-control-sm" id="desc" rows="3" placeholder="Describe the purpose of this expense..."></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="int_notes">Internal Audit Notes</label>
                        <textarea wire:model="internal_notes" class="form-control form-control-sm" id="int_notes" rows="3" placeholder="Private internal comments..."></textarea>
                    </div>

                    <!-- Receipt Attachment Section -->
                    <div class="col-12 mt-4">
                        <h6 class="border-bottom pb-2"><span class="fas fa-paperclip me-2"></span>Bill Receipts & Attachments</h6>
                        
                        @if(count($existingAttachments) > 0)
                            <div class="row g-2 mb-3">
                                @foreach($existingAttachments as $exist)
                                    <div class="col-md-3">
                                        <div class="border rounded p-2 bg-light d-flex justify-content-between align-items-center">
                                            <div class="text-truncate" style="max-width: 80%;">
                                                <span class="fas fa-file me-2 text-primary"></span>
                                                <span class="fs-11 fw-semi-bold">{{ $exist->file_name }}</span>
                                            </div>
                                            <button type="button" wire:click="removeAttachment({{ $exist->id }})" class="btn btn-link p-0 text-danger" title="Remove attachment">
                                                <span class="fas fa-times"></span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mb-3">
                            <input wire:model="uploadedFiles" type="file" class="form-control form-control-sm" multiple id="attach_files">
                            <span class="text-muted fs-11">Supported formats: JPG, PNG, PDF, DOCX. Max file size: 10MB per file.</span>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="col-12 mt-4 text-end">
                        <a href="{{ route('expenses.index') }}" class="btn btn-falcon-default btn-sm me-2">Cancel</a>
                        <button type="submit" class="btn btn-falcon-primary btn-sm me-2">
                            <span class="fas fa-save me-1"></span>Save as Draft
                        </button>
                        <button type="button" wire:click="submit" class="btn btn-primary btn-sm">
                            <span class="fas fa-paper-plane me-1"></span>Submit for Approval
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
