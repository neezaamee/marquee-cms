<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">
                <span class="fas fa-file-invoice-dollar me-2 text-primary"></span>
                {{ $editId ? 'Edit Expense Record: ' . $expense_number : 'Record Expense Voucher' }}
            </h5>
            @if($expense_number)
                <span class="badge badge-subtle-primary font-monospace fs-10">{{ $expense_number }}</span>
            @endif
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
                <!-- PRIMARY EXPENSE DETAILS -->
                <div class="row g-3">
                    <!-- Date -->
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold" for="exp_date">Expense Date <span class="text-danger">*</span></label>
                        <input wire:model="expense_date" type="date" class="form-control form-control-sm @error('expense_date') is-invalid @enderror" id="exp_date">
                        @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Branch -->
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold" for="br_id">Branch <span class="text-danger">*</span></label>
                        <select wire:model="branch_id" class="form-select form-select-sm @error('branch_id') is-invalid @enderror" id="br_id">
                            <option value="">Select branch</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Expense Category (26 Standard Categories) -->
                    @if(!$is_multiline)
                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold" for="exp_cat">Expense Category <span class="text-danger">*</span></label>
                            <select wire:model="expense_category_id" class="form-select form-select-sm @error('expense_category_id') is-invalid @enderror" id="exp_cat">
                                <option value="">Select category...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('expense_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- Amount -->
                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold" for="exp_amt">Amount (PKR) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text fw-bold text-primary">Rs.</span>
                                <input wire:model.live="amount" type="number" step="0.01" min="0.01" class="form-control form-control-sm fw-bold font-monospace text-end @error('amount') is-invalid @enderror" id="exp_amt" placeholder="0.00">
                            </div>
                            @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    @else
                        <div class="col-md-6 d-flex align-items-center">
                            <span class="badge bg-soft-info text-info p-2 fs-10">
                                <span class="fas fa-layer-group me-1"></span> Multi-line item voucher enabled. Fill item breakdown below.
                            </span>
                        </div>
                    @endif

                    <!-- Payment Method -->
                    <div class="col-md-3">
                        <label class="form-label fw-semi-bold" for="pay_method">Payment Method <span class="text-danger">*</span></label>
                        <select wire:model.live="payment_method" class="form-select form-select-sm @error('payment_method') is-invalid @enderror" id="pay_method">
                            <option value="Cash">Cash in Hand</option>
                            <option value="Bank">Bank Account</option>
                            <option value="Petty Cash">Petty Cash Box</option>
                            <option value="Accounts Payable">Accounts Payable (Credit)</option>
                        </select>
                        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Dynamic Bank Account -->
                    @if($payment_method === 'Bank')
                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold" for="cb_acc">Bank Account <span class="text-danger">*</span></label>
                            <select wire:model="cash_bank_account_id" class="form-select form-select-sm @error('cash_bank_account_id') is-invalid @enderror" id="cb_acc">
                                <option value="">Select bank account</option>
                                @foreach($cashAccounts as $ca)
                                    <option value="{{ $ca->id }}">{{ $ca->account_name }} ({{ $ca->account_number }})</option>
                                @endforeach
                            </select>
                            @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @elseif($payment_method === 'Petty Cash')
                        <!-- Dynamic Petty Cash Drawer -->
                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold" for="petty_acc">Petty Cash Drawer <span class="text-danger">*</span></label>
                            <select wire:model="petty_cash_account_id" class="form-select form-select-sm @error('petty_cash_account_id') is-invalid @enderror" id="petty_acc">
                                <option value="">Select petty drawer</option>
                                @foreach($pettyDrawers as $pd)
                                    <option value="{{ $pd->id }}">{{ $pd->account_name }} (Bal: {{ number_format($pd->current_balance, 2) }} PKR)</option>
                                @endforeach
                            </select>
                            @error('petty_cash_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @elseif($payment_method === 'Accounts Payable')
                        <!-- Due Date for Credit Payments -->
                        <div class="col-md-3">
                            <label class="form-label fw-semi-bold" for="due_dt">Payment Due Date</label>
                            <input wire:model="due_date" type="date" class="form-control form-control-sm @error('due_date') is-invalid @enderror" id="due_dt">
                            @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    <!-- Paid To / Vendor (Optional) -->
                    <div class="col-md-3">
                        <label class="form-label" for="supplier">Paid To / Vendor <span class="text-muted fs-11">(Optional)</span></label>
                        <select wire:model="supplier_id" class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" id="supplier">
                            <option value="">Select vendor / person</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Reference / Bill # (Optional) -->
                    <div class="col-md-3">
                        <label class="form-label" for="ref_no">Bill / Ref / Cheque # <span class="text-muted fs-11">(Optional)</span></label>
                        <input wire:model="reference_number" type="text" class="form-control form-control-sm @error('reference_number') is-invalid @enderror" id="ref_no" placeholder="e.g. Consumer # / Bill # / Chq #">
                        @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Description / Remarks -->
                    <div class="col-12">
                        <label class="form-label" for="desc">Description / Remarks <span class="text-muted fs-11">(Optional)</span></label>
                        <textarea wire:model="description" class="form-control form-control-sm" id="desc" rows="2" placeholder="Brief note or purpose of this expense voucher..."></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- MULTI-LINE SPLIT TABLE (Shown only when toggled) -->
                    @if($is_multiline)
                        <div class="col-12 mt-3">
                            <div class="border rounded p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 text-primary"><span class="fas fa-list me-2"></span>Line Items Breakdown</h6>
                                    <button type="button" wire:click="addRow" class="btn btn-falcon-default btn-xs">
                                        <span class="fas fa-plus me-1"></span>Add Line
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered fs-10 align-middle mb-0 bg-white">
                                        <thead class="bg-200">
                                            <tr>
                                                <th style="width: 35%;">Category</th>
                                                <th style="width: 40%;">Description</th>
                                                <th class="text-end" style="width: 20%;">Amount (PKR)</th>
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
                                                        <input wire:model="items.{{ $index }}.description" type="text" class="form-control form-control-sm" placeholder="Line remarks">
                                                    </td>
                                                    <td>
                                                        <input wire:model.live="items.{{ $index }}.amount" type="number" step="0.01" class="form-control form-control-sm text-end font-monospace" placeholder="0.00">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" wire:click="removeRow({{ $index }})" class="btn btn-link p-0 text-danger">
                                                            <span class="fas fa-trash-alt"></span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold bg-light">
                                                <td colspan="2" class="text-end">Total Amount:</td>
                                                <td class="text-end font-monospace text-primary fs-9">
                                                    {{ number_format($total_amount, 2) }} PKR
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- OPTIONAL EXTENSIONS ACCORDION -->
                    <div class="col-12 mt-2">
                        <div class="accordion border rounded" id="expenseMoreOptions">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header" id="headingMore">
                                    <button class="accordion-button collapsed py-2 bg-light text-700 fs-10" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMore" aria-expanded="false" aria-controls="collapseMore">
                                        <span class="fas fa-sliders-h me-2 text-primary"></span>
                                        Additional Options (Link Event Booking, Attach Receipt, Split Items)
                                    </button>
                                </h2>
                                <div id="collapseMore" class="accordion-collapse collapse" aria-labelledby="headingMore" data-bs-parent="#expenseMoreOptions">
                                    <div class="accordion-body bg-white p-3">
                                        <div class="row g-3">
                                            <!-- Event Booking Link -->
                                            <div class="col-md-6">
                                                <label class="form-label" for="booking">Link to Event Booking <span class="text-muted fs-11">(Direct Cost Tracking)</span></label>
                                                <select wire:model="booking_id" class="form-select form-select-sm" id="booking">
                                                    <option value="">No linked booking</option>
                                                    @foreach($bookings as $bk)
                                                        <option value="{{ $bk->id }}">{{ $bk->booking_number }} — {{ $bk->customer->name ?? 'Guest' }} ({{ $bk->booking_date ? $bk->booking_date->format('d M Y') : '' }})</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-muted fs-11">Assign this expense directly to an event for gross margin tracking.</span>
                                            </div>

                                            <!-- Multi-line split toggle -->
                                            <div class="col-md-6 d-flex align-items-center">
                                                <div class="form-check mt-3">
                                                    <input wire:model.live="is_multiline" class="form-check-input" type="checkbox" id="multiline_chk">
                                                    <label class="form-check-label fw-semi-bold text-800" for="multiline_chk">
                                                        Split voucher across multiple expense categories
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Receipt & Bill Attachments -->
                                            <div class="col-12 border-top pt-3">
                                                <label class="form-label fw-semi-bold"><span class="fas fa-paperclip me-1 text-primary"></span>Attach Receipt / Bill Image</label>
                                                
                                                @if(count($existingAttachments) > 0)
                                                    <div class="row g-2 mb-2">
                                                        @foreach($existingAttachments as $exist)
                                                            <div class="col-md-4">
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

                                                <input wire:model="uploadedFiles" type="file" class="form-control form-control-sm" multiple id="attach_files">
                                                <span class="text-muted fs-11">Supported formats: JPG, PNG, PDF. Max size: 10MB per file.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER ACTIONS -->
                    <div class="col-12 mt-4 d-flex justify-content-between align-items-center border-top pt-3">
                        <a href="{{ route('expenses.index') }}" class="btn btn-falcon-default btn-sm">
                            <span class="fas fa-arrow-left me-1"></span>Back to Register
                        </a>
                        <div class="d-flex gap-2">
                            <button type="submit" wire:loading.attr="disabled" class="btn btn-falcon-primary btn-sm">
                                <span wire:loading.remove wire:target="saveDraft"><span class="fas fa-save me-1"></span>Save as Draft</span>
                                <span wire:loading wire:target="saveDraft"><span class="spinner-border spinner-border-sm me-1"></span>Saving...</span>
                            </button>
                            <button type="button" wire:click="submit" wire:loading.attr="disabled" class="btn btn-primary btn-sm px-3">
                                <span wire:loading.remove wire:target="submit"><span class="fas fa-check-circle me-1"></span>Record & Post Expense</span>
                                <span wire:loading wire:target="submit"><span class="spinner-border spinner-border-sm me-1"></span>Posting...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
