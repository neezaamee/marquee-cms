<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Record SaaS Subscription Payment</h5>
            <a href="{{ route('saas-payments.index') }}" class="btn btn-falcon-default btn-sm">
                <span class="fas fa-arrow-left me-1"></span> Cancel
            </a>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="invoice_id">Select Unpaid/Pending Invoice <span class="text-danger">*</span></label>
                        <select wire:model.live="invoice_id" class="form-select @error('invoice_id') is-invalid @enderror" id="invoice_id" @if(request()->query('invoice_id')) disabled @endif>
                            <option value="">-- Choose Invoice --</option>
                            @foreach($invoices as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->invoice_number }} ({{ $inv->user->name }} - {{ $inv->subscriptionPlan->name }})</option>
                            @endforeach
                        </select>
                        @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="discount">Discount Allowed</label>
                        <div class="input-group">
                            <input wire:model.live="discount" class="form-control @error('discount') is-invalid @enderror" id="discount" type="number" step="0.01" min="0" />
                            <span class="input-group-text">{{ $selectedInvoice ? $selectedInvoice->subscriptionPlan->currency : 'PKR' }}</span>
                        </div>
                        @error('discount') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="amount">Payment Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input wire:model="amount" class="form-control @error('amount') is-invalid @enderror" id="amount" type="number" step="0.01" min="0" />
                            <span class="input-group-text">{{ $selectedInvoice ? $selectedInvoice->subscriptionPlan->currency : 'PKR' }}</span>
                        </div>
                        @if($selectedInvoice)
                            <small class="text-info fw-semi-bold d-block mt-1">Remaining Balance: {{ number_format($remainingBalance, 2) }} {{ $selectedInvoice->subscriptionPlan->currency }}</small>
                        @endif
                        @error('amount') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <!-- Selected Invoice Details Card -->
                    @if($selectedInvoice)
                        <div class="col-12 bg-light p-3 rounded">
                            <h6 class="mb-2 text-primary">Invoice Details</h6>
                            <div class="row g-2 fs-11 text-700">
                                <div class="col-md-4"><strong>Business Owner:</strong> {{ $selectedInvoice->user->name }}</div>
                                <div class="col-md-4"><strong>Plan:</strong> {{ $selectedInvoice->subscriptionPlan->name }}</div>
                                <div class="col-md-4"><strong>Billing Cycle:</strong> {{ $selectedInvoice->billingCycle->cycle_name }}</div>
                                <div class="col-md-4"><strong>Due Date:</strong> {{ $selectedInvoice->due_date->format('M d, Y') }}</div>
                                <div class="col-md-4"><strong>Invoice Total:</strong> {{ number_format($selectedInvoice->total_amount, 2) }} {{ $selectedInvoice->subscriptionPlan->currency }}</div>
                                <div class="col-md-4"><strong>Status:</strong> <span class="badge badge-subtle-danger rounded-pill">{{ $selectedInvoice->payment_status }}</span></div>
                            </div>
                        </div>
                    @endif

                    <div class="col-md-6">
                        <label class="form-label" for="payment_method">Payment Method <span class="text-danger">*</span></label>
                        <select wire:model="payment_method" class="form-select @error('payment_method') is-invalid @enderror" id="payment_method">
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Easypaisa">Easypaisa</option>
                            <option value="JazzCash">JazzCash</option>
                            <option value="Credit Card">Credit Card</option>
                        </select>
                        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="transaction_id">Transaction ID / Reference Number</label>
                        <input wire:model="transaction_id" class="form-control @error('transaction_id') is-invalid @enderror" id="transaction_id" type="text" placeholder="e.g. TXN-1982738" />
                        @error('transaction_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="payment_date">Payment Date <span class="text-danger">*</span></label>
                        <input wire:model="payment_date" class="form-control @error('payment_date') is-invalid @enderror" id="payment_date" type="date" />
                        @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea wire:model="notes" class="form-control" id="notes" rows="2" placeholder="Record additional transaction info..."></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button class="btn btn-primary btn-sm px-4 me-2" type="submit">
                            <span class="fas fa-save me-1"></span> Record Payment
                        </button>
                        <a href="{{ route('saas-payments.index') }}" class="btn btn-falcon-default btn-sm px-3">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
