<div>
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-check-circle me-2 fs-6"></i>
            <div class="flex-grow-1">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($showSettlementModal)
        <!-- Falcon Card Form for Process Settlement -->
        <div class="card mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <span class="fas fa-hand-holding-usd me-2 text-warning"></span>
                    Process Settlement Payout
                </h6>
                <button class="btn btn-falcon-default btn-sm" wire:click="$set('showSettlementModal', false)">
                    <span class="fas fa-chevron-left me-1" data-fa-transform="shrink-4"></span> Back
                </button>
            </div>

            <div class="card-body bg-light">
                <form wire:submit.prevent="saveSettlement">
                    <div class="row g-3">
                        @if(!$vendor)
                            <div class="col-md-6">
                                <label class="form-label fw-semi-bold" for="vendor_id">Select Service Provider <span class="text-danger">*</span></label>
                                <select id="vendor_id" wire:model.live="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
                                    <option value="">-- Choose Service Provider --</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}">{{ $v->name }} (Balance: Rs. {{ number_format($v->current_balance) }})</option>
                                    @endforeach
                                </select>
                                @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="settlement_date">Settlement Date <span class="text-danger">*</span></label>
                            <input type="date" id="settlement_date" wire:model="settlement_date" class="form-control @error('settlement_date') is-invalid @enderror" required>
                            @error('settlement_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="paid_amount">Payout Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="paid_amount" wire:model="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" placeholder="0.00" required>
                            @error('paid_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="payment_method">Payment Method <span class="text-danger">*</span></label>
                            <select id="payment_method" wire:model="payment_method" class="form-select">
                                <option value="Cash">Cash in Hand</option>
                                <option value="Bank Transfer">Bank Transfer (IBFT)</option>
                                <option value="Cheque">Bank Cheque</option>
                                <option value="Online / POS">Online / POS</option>
                            </select>
                            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="account_id">Cash/Bank Ledger Account (Accounting Integration)</label>
                            <select id="account_id" wire:model="account_id" class="form-select">
                                <option value="">-- Select Cash/Bank Account --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->account_code }})</option>
                                @endforeach
                            </select>
                            @error('account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semi-bold" for="reference_number">Reference / Cheque Number</label>
                            <input type="text" id="reference_number" wire:model="reference_number" class="form-control" placeholder="e.g. TR-998877 / CHQ-100200">
                            @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semi-bold" for="remarks">Remarks / Payout Description</label>
                            <textarea id="remarks" wire:model="remarks" class="form-control" rows="3" placeholder="e.g. Full settlement payout for August photography services..."></textarea>
                            @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 mt-4 text-end">
                            <button type="button" wire:click="$set('showSettlementModal', false)" class="btn btn-falcon-default btn-sm me-2">Cancel</button>
                            <button type="submit" class="btn btn-warning btn-sm px-4 text-dark fw-bold"><span class="fas fa-check-circle me-1"></span> Authorize & Post Payout</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0"><i class="fas fa-wallet text-warning me-2"></i>Service Provider Settlements & Payouts</h6>
                <div class="text-secondary fs-11">Payout logs, bank disbursements, and provider account clearance records.</div>
            </div>
            <button wire:click="openCreateModal" class="btn btn-warning btn-xs text-dark fw-bold"><i class="fas fa-hand-holding-usd me-1"></i> Process Settlement</button>
        </div>

        <!-- Settlements Table -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-12">
                    <thead class="bg-200">
                        <tr>
                            <th>Settlement #</th>
                            @if(!$vendor) <th>Service Provider</th> @endif
                            <th>Date</th>
                            <th>Net Payable</th>
                            <th>Paid Amount</th>
                            <th>Remaining Balance</th>
                            <th>Payment Method</th>
                            <th>Accounting JV</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $set)
                            <tr>
                                <td class="fw-bold font-monospace text-primary">{{ $set->settlement_number }}</td>
                                @if(!$vendor)
                                    <td class="fw-semibold">{{ $set->vendor->name ?? '—' }}</td>
                                @endif
                                <td>{{ $set->settlement_date->format('d-M-Y') }}</td>
                                <td class="fw-bold text-dark">Rs. {{ number_format($set->net_payable_amount) }}</td>
                                <td class="fw-bold text-success">Rs. {{ number_format($set->paid_amount) }}</td>
                                <td class="fw-bold {{ $set->remaining_balance > 0 ? 'text-danger' : 'text-muted' }}">
                                    Rs. {{ number_format($set->remaining_balance) }}
                                </td>
                                <td>
                                    <span class="badge badge-subtle-primary"><i class="fas fa-university me-1"></i>{{ $set->payment_method }}</span>
                                    @if($set->account)
                                        <div class="text-secondary fs-11 mt-1">{{ $set->account->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($set->journalVoucher)
                                        <span class="badge badge-subtle-info font-monospace">{{ $set->journalVoucher->voucher_no }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($set->status === 'fully_settled')
                                        <span class="badge badge-subtle-success">Fully Settled</span>
                                    @elseif($set->status === 'partially_settled')
                                        <span class="badge badge-subtle-warning">Partially Settled</span>
                                    @else
                                        <span class="badge badge-subtle-secondary">{{ ucfirst($set->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $vendor ? 8 : 9 }}" class="text-center py-4 text-muted">No settlements recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
