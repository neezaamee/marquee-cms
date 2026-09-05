<div>
    <!-- Supplier Ledger Details Header -->
    <div class="card mb-3 border border-200 shadow-sm">
        <div class="card-body py-3 px-4 bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-1 text-primary fw-bold">{{ $supplier->name }}</h4>
                <p class="mb-1 text-600 fs-12">
                    <span class="badge badge-subtle-secondary font-monospace me-2">{{ $supplier->supplier_code }}</span>
                    @if($supplier->contact_person) <span class="fas fa-user-alt me-1"></span>{{ $supplier->contact_person }} | @endif
                    <span class="fas fa-phone-alt me-1"></span>{{ $supplier->mobile_number }} |
                    <span class="fas fa-map-marker-alt me-1"></span>{{ $supplier->city ?: 'No City' }}
                </p>
                @if($supplier->categories->isNotEmpty())
                    <div class="d-flex align-items-center gap-1 flex-wrap mt-1">
                        <span class="text-500 fs-11 me-1"><span class="fas fa-tags me-1"></span>Categories:</span>
                        @foreach($supplier->categories as $cat)
                            <span class="badge badge-subtle-primary rounded-pill fs-11">{{ $cat->name }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="text-end">
                <span class="fs-12 text-600 fw-bold d-block">Outstanding Balance:</span>
                <h3 class="mb-0 font-monospace fw-black text-danger">Rs. {{ number_format($supplier->current_balance, 2) }}</h3>
            </div>
        </div>
    </div>

    <!-- Quick action links -->
    <div class="card mb-3">
        <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="{{ route('suppliers.index') }}" class="btn btn-falcon-default btn-sm">
                <span class="fas fa-arrow-left me-1"></span>Back to Directory
            </a>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting'))
                <button wire:click="$set('showPaymentModal', true)" class="btn btn-falcon-success btn-sm">
                    <span class="fas fa-wallet me-1"></span>Record Vendor Payment
                </button>
            @endif
        </div>
    </div>

    @if($showPaymentModal)
        <!-- Manual Payment Card Form -->
        <div class="card mb-3 border border-success">
            <div class="card-header bg-success text-white py-2">
                <h6 class="mb-0 text-white"><span class="fas fa-money-bill-wave me-2"></span>Record Manual Payment to Supplier</h6>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="recordPayment">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="pay-amount">Payment Amount (Rs.) *</label>
                            <input wire:model="payment_amount" type="number" step="0.01" class="form-control form-control-sm @error('payment_amount') is-invalid @enderror" id="pay-amount" />
                            @error('payment_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="pay-date">Payment Date *</label>
                            <input wire:model="payment_date" type="date" class="form-control form-control-sm @error('payment_date') is-invalid @enderror" id="pay-date" />
                            @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="pay-account">Cash/Bank Account *</label>
                            <select wire:model="cash_bank_account_id" class="form-select form-select-sm @error('cash_bank_account_id') is-invalid @enderror" id="pay-account">
                                <option value="">Select Account</option>
                                @foreach($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->account_name }} (Bal: {{ number_format($acc->current_balance) }})</option>
                                @endforeach
                            </select>
                            @error('cash_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="pay-ref">Reference No / Chq #</label>
                            <input wire:model="reference_no" type="text" class="form-control form-control-sm" id="pay-ref" placeholder="e.g. Cheque-12345" />
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="pay-desc">Payment Narration / Remarks</label>
                            <input wire:model="description" type="text" class="form-control form-control-sm" id="pay-desc" placeholder="Details of payment..." />
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" wire:click="resetPaymentForm" class="btn btn-falcon-default btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm">
                            <span class="fas fa-check-circle me-1"></span>Post Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Ledger Transaction statement grid -->
    <div class="card">
        <div class="card-header bg-light">
            <h6 class="mb-0 fw-bold">Supplier Transaction Ledger Statement</h6>
        </div>
        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 100px;">Date</th>
                            <th style="width: 120px;">Voucher No</th>
                            <th style="width: 150px;">Transaction Type</th>
                            <th>Description</th>
                            <th class="text-end" style="width: 120px;">Debit (Paid/Ret)</th>
                            <th class="text-end" style="width: 120px;">Credit (Billed)</th>
                            <th class="text-end px-3" style="width: 140px;">Balance (Payable)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledgers as $ledger)
                            <tr>
                                <td class="px-3">{{ $ledger->transaction_date->format('Y-m-d') }}</td>
                                <td class="font-monospace fw-bold">{{ $ledger->voucher_no ?: '—' }}</td>
                                <td>
                                    @php
                                        $badges = [
                                            'OpeningBalance' => 'secondary',
                                            'PurchaseInvoice' => 'primary',
                                            'PurchaseReturn' => 'warning',
                                            'VendorPayment' => 'success'
                                        ];
                                        $bc = $badges[$ledger->reference_type] ?? 'dark';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $bc }}">{{ $ledger->reference_type }}</span>
                                </td>
                                <td class="text-muted">{{ $ledger->description }}</td>
                                <td class="text-end font-monospace text-success fw-semi-bold">
                                    {{ $ledger->debit > 0 ? 'Rs. ' . number_format($ledger->debit, 2) : '—' }}
                                </td>
                                <td class="text-end font-monospace text-dark fw-semi-bold">
                                    {{ $ledger->credit > 0 ? 'Rs. ' . number_format($ledger->credit, 2) : '—' }}
                                </td>
                                <td class="text-end px-3 font-monospace fw-bold text-danger">
                                    Rs. {{ number_format($ledger->running_balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No transaction history recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($ledgers->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $ledgers->links() }}
            </div>
        @endif
    </div>
</div>
