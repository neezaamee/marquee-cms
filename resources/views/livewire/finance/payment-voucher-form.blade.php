<div class="row g-3">
    <div class="col-lg-10 mx-auto">
        <!-- Title Card -->
        <div class="card mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <h5 class="mb-0 text-primary">
                    <span class="fas fa-file-invoice-dollar me-2"></span>
                    {{ $editId ? 'Edit Payment Voucher: ' . $voucher_no : 'Create Payment Voucher' }}
                </h5>
                <a href="{{ route('finance.payment-vouchers.index') }}" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-arrow-left me-1"></span>Back to List
                </a>
            </div>

            <div class="card-body">
                @if(session()->has('error'))
                    <div class="alert alert-danger alert-dismissible fade show fs-12 py-2" role="alert">
                        <span class="fas fa-times-circle me-1"></span>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form wire:submit.prevent="save">
                    <!-- Voucher Type & Core Info -->
                    <div class="p-3 bg-light rounded border mb-4">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <label class="form-label fs-11 fw-bold text-uppercase text-secondary">Voucher Type</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" wire:click="$set('voucher_type', 'CPV')" class="btn btn-sm {{ $voucher_type === 'CPV' ? 'btn-success fw-bold' : 'btn-outline-secondary' }}">
                                        <i class="fas fa-money-bill-wave me-1"></i>Cash Payment (CPV)
                                    </button>
                                    <button type="button" wire:click="$set('voucher_type', 'BPV')" class="btn btn-sm {{ $voucher_type === 'BPV' ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                                        <i class="fas fa-university me-1"></i>Bank Payment (BPV)
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fs-11 fw-bold text-uppercase text-secondary">Voucher No</label>
                                <input type="text" wire:model="voucher_no" class="form-control form-control-sm font-mono fw-bold bg-white" readonly>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fs-11 fw-bold text-uppercase text-secondary">Voucher Date</label>
                                <input type="date" wire:model="voucher_date" class="form-control form-control-sm @error('voucher_date') is-invalid @enderror" required>
                                @error('voucher_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if(count($branches) > 1)
                            <div class="col-md-2">
                                <label class="form-label fs-11 fw-bold text-uppercase text-secondary">Branch</label>
                                <select wire:model.live="branch_id" class="form-select form-select-sm">
                                    <option value="">Head Office</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Payee Section -->
                    <div class="card mb-4 border shadow-none">
                        <div class="card-header bg-200 py-2">
                            <h6 class="mb-0 text-800 fs-12">
                                <span class="fas fa-user-tag me-2 text-primary"></span>Payee & Beneficiary Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Payee Category Selector -->
                            <div class="mb-3">
                                <label class="form-label fs-11 fw-bold">Payee Category</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" wire:click="setPayeeType('supplier')" class="btn btn-sm {{ $payee_type === 'supplier' ? 'btn-falcon-primary fw-bold' : 'btn-falcon-default' }}">
                                        <i class="fas fa-truck me-1"></i>Supplier / Vendor (Procurement)
                                    </button>
                                    <button type="button" wire:click="setPayeeType('vendor')" class="btn btn-sm {{ $payee_type === 'vendor' ? 'btn-falcon-primary fw-bold' : 'btn-falcon-default' }}">
                                        <i class="fas fa-handshake me-1"></i>Third-Party Vendor (Service Provider)
                                    </button>
                                    <button type="button" wire:click="setPayeeType('expense')" class="btn btn-sm {{ $payee_type === 'expense' ? 'btn-falcon-primary fw-bold' : 'btn-falcon-default' }}">
                                        <i class="fas fa-receipt me-1"></i>Operating Expense
                                    </button>
                                    <button type="button" wire:click="setPayeeType('general')" class="btn btn-sm {{ $payee_type === 'general' ? 'btn-falcon-primary fw-bold' : 'btn-falcon-default' }}">
                                        <i class="fas fa-user me-1"></i>General / Other
                                    </button>
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- Dynamic Selection Dropdown -->
                                @if($payee_type === 'supplier')
                                    <div class="col-md-6">
                                        <label class="form-label fs-11 fw-bold">Select Supplier</label>
                                        <select wire:model.live="payee_id" class="form-select form-select-sm">
                                            <option value="">-- Choose Supplier --</option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}">
                                                    {{ $sup->name }} (Balance: Rs. {{ number_format($sup->current_balance, 0) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @elseif($payee_type === 'vendor')
                                    <div class="col-md-6">
                                        <label class="form-label fs-11 fw-bold">Select Third-Party Vendor / Service Provider</label>
                                        <select wire:model.live="payee_id" class="form-select form-select-sm">
                                            <option value="">-- Choose Vendor or Service Type --</option>
                                            @if(count($vendors) > 0)
                                                <optgroup label="Registered Vendors">
                                                    @foreach($vendors as $ven)
                                                        <option value="ven_{{ $ven->id }}">
                                                            {{ $ven->name }} ({{ $ven->vendor_type ?? 'Vendor' }}) - Due: Rs. {{ number_format($ven->current_balance ?? 0, 0) }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                            <optgroup label="Standard Event Service Categories">
                                                <option value="srv_decor">Stage & Theme Decoration</option>
                                                <option value="srv_floral">Floral Setup & Fresh Flowers</option>
                                                <option value="srv_sound">Sound & Audio System / DJ</option>
                                                <option value="srv_lighting">Lighting & Truss Setup</option>
                                                <option value="srv_photo">Photography & Video Coverage</option>
                                                <option value="srv_catering">Catering & Master Chef Staff</option>
                                                <option value="srv_crockery">Crockery & Buffet Setup</option>
                                                <option value="srv_security">Security Guard Services</option>
                                                <option value="srv_valet">Valet Parking Services</option>
                                                <option value="srv_generator">Generator & Power Rental</option>
                                                <option value="srv_janitorial">Janitorial & Cleaning Crew</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                @elseif($payee_type === 'expense')
                                    <div class="col-md-6">
                                        <label class="form-label fs-11 fw-bold">Select Operating Expense Category</label>
                                        <select wire:model.live="payee_id" class="form-select form-select-sm">
                                            <option value="">-- Choose Operating Expense Category --</option>
                                            <optgroup label="Operating Expense Categories / Accounts">
                                                @foreach($expenseCategories as $cat)
                                                    @php
                                                        $code = $cat->defaultAccount?->account_code ?? $cat->category_code;
                                                    @endphp
                                                    <option value="cat_{{ $cat->id }}">
                                                        {{ $code ? '[' . $code . '] ' : '' }}{{ $cat->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                            @if(count($expenses) > 0)
                                                <optgroup label="Unposted Expense Vouchers (Optional)">
                                                    @foreach($expenses as $exp)
                                                        <option value="exp_{{ $exp->id }}">
                                                            #{{ $exp->expense_number }} - {{ $exp->description }} (Rs. {{ number_format($exp->total_amount, 2) }})
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        </select>
                                    </div>
                                @endif

                                <div class="col-md-6">
                                    <label class="form-label fs-11 fw-bold">Payee / Receiver Name</label>
                                    <input type="text" wire:model="payee_name" class="form-control form-control-sm @error('payee_name') is-invalid @enderror" placeholder="Name of person / firm receiving payment" required>
                                    @error('payee_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold">Payee CNIC (for physical acknowledgment)</label>
                                    <input type="text" wire:model="payee_cnic" class="form-control form-control-sm" placeholder="e.g. 35201-1234567-1">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold">Contact / Phone Number</label>
                                    <input type="text" wire:model="payee_phone" class="form-control form-control-sm" placeholder="0300-1234567">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold">External Bill / Invoice / Challan Ref #</label>
                                    <input type="text" wire:model="reference_no" class="form-control form-control-sm font-mono" placeholder="e.g. INV-9872 / DC-441">
                                </div>

                                @if($payeeOutstandingBalance !== null)
                                    <div class="col-12">
                                        <div class="alert alert-subtle-info py-2 mb-0 fs-11">
                                            <i class="fas fa-info-circle me-1"></i>Current Outstanding Ledger Balance for <strong>{{ $payee_name }}</strong>:
                                            <strong class="font-mono fs-12 ms-1">Rs. {{ number_format($payeeOutstandingBalance, 2) }}</strong>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Financial & Account Details -->
                    <div class="card mb-4 border shadow-none">
                        <div class="card-header bg-200 py-2">
                            <h6 class="mb-0 text-800 fs-12">
                                <span class="fas fa-coins me-2 text-primary"></span>Payment & Accounting Specifications
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Disbursing Source Account -->
                                <div class="col-md-6">
                                    <label class="form-label fs-11 fw-bold">
                                        {{ $voucher_type === 'CPV' ? 'Disbursing Cash Drawer / Petty Cash' : 'Disbursing Bank Account' }}
                                    </label>
                                    <select wire:model="cash_bank_account_id" class="form-select form-select-sm @error('cash_bank_account_id') is-invalid @enderror" required>
                                        <option value="">-- Choose Account --</option>
                                        @foreach($cashBankAccounts as $cba)
                                            <option value="{{ $cba->id }}">
                                                {{ $cba->account->name }} @if($cba->bank_name) ({{ $cba->bank_name }} - {{ $cba->account_number }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <!-- Debit Account -->
                                <div class="col-md-6">
                                    <label class="form-label fs-11 fw-bold">Debit Account Head (General Ledger)</label>
                                    <select wire:model="debit_account_id" class="form-select form-select-sm @error('debit_account_id') is-invalid @enderror" required>
                                        <option value="">-- Choose GL Account --</option>
                                        @php
                                            $groupedAccounts = $debitAccounts->groupBy(function($a) {
                                                return $a->accountType ? $a->accountType->name : $a->nature;
                                            });
                                        @endphp
                                        @foreach($groupedAccounts as $typeGroup => $accs)
                                            <optgroup label="{{ $typeGroup }}">
                                                @foreach($accs as $acc)
                                                    <option value="{{ $acc->id }}">
                                                        [{{ $acc->account_code }}] {{ $acc->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    @error('debit_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="fs-10 text-muted mt-1">This account will be debited upon voucher posting (e.g. Accounts Payable or Expense).</div>
                                </div>

                                <!-- Amount (PKR) -->
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold">Amount to Pay (PKR)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text fw-bold">Rs.</span>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="amount" class="form-control form-control-sm font-mono fw-bold fs-13 @error('amount') is-invalid @enderror" placeholder="0.00" required>
                                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <!-- Payment Instrument Mode -->
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-bold">Payment Method</label>
                                    <select wire:model="payment_method" class="form-select form-select-sm">
                                        <option value="Cash">Cash</option>
                                        <option value="Cheque">Cheque / Demand Draft</option>
                                        <option value="Bank Transfer">Bank Transfer / IBFT</option>
                                        <option value="Online">Online / Digital Payment</option>
                                    </select>
                                </div>

                                <!-- Cheque / Instrument Details -->
                                @if($voucher_type === 'BPV' || $payment_method === 'Cheque')
                                    <div class="col-md-2">
                                        <label class="form-label fs-11 fw-bold">Cheque Number</label>
                                        <input type="text" wire:model="cheque_no" class="form-control form-control-sm font-mono" placeholder="CHQ-001245">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fs-11 fw-bold">Cheque Date</label>
                                        <input type="date" wire:model="cheque_date" class="form-control form-control-sm">
                                    </div>
                                @endif

                                <!-- Amount in Words -->
                                <div class="col-12">
                                    <label class="form-label fs-11 fw-bold">Amount in Words</label>
                                    <input type="text" wire:model="amount_in_words" class="form-control form-control-sm font-italic bg-light text-dark fw-bold" placeholder="Auto-generated in words...">
                                </div>

                                <!-- Description / Particulars -->
                                <div class="col-12">
                                    <label class="form-label fs-11 fw-bold">Particulars / Payment Description</label>
                                    <textarea wire:model="description" class="form-control form-control-sm" rows="3" placeholder="Provide details of what this payment is for, items delivered, milestones achieved, or authorization notes..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow Stage Option -->
                    <div class="p-3 bg-light rounded border mb-4">
                        <label class="form-label fs-11 fw-bold text-uppercase text-secondary d-block">Initial Workflow Status</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" wire:model="status" id="statusApproved" value="approved">
                            <label class="form-check-label fs-12 fw-bold text-success" for="statusApproved">
                                <i class="fas fa-check-circle me-1"></i>Approved (Ready to Print Voucher Slip)
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" wire:model="status" id="statusDraft" value="draft">
                            <label class="form-check-label fs-12 text-secondary" for="statusDraft">
                                <i class="fas fa-pencil-alt me-1"></i>Draft (Unapproved)
                            </label>
                        </div>
                        <div class="fs-10 text-muted mt-2">
                            <i class="fas fa-shield-alt me-1"></i>Regardless of status, <strong>no money leaves the account and no GL entries are posted</strong> until the voucher is physically acknowledged and you confirm "Disburse & Post".
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('finance.payment-vouchers.index') }}" class="btn btn-falcon-default btn-sm">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <span class="fas fa-save me-1"></span>{{ $editId ? 'Update Voucher' : 'Save & Generate Voucher Slip' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
