<div>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-0"><i class="fas fa-wallet text-warning me-2"></i>Vendor Settlements & Payouts</h6>
            <div class="text-secondary fs-11">Payout logs, bank disbursements, and vendor account clearance records.</div>
        </div>
        <button wire:click="openCreateModal" class="btn btn-warning btn-xs text-dark fw-bold"><i class="fas fa-hand-holding-usd me-1"></i> Process Settlement</button>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 fs-12 mb-3">{{ session('success') }}</div>
    @endif

    <!-- Settlements Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-12">
                <thead class="bg-200">
                    <tr>
                        <th>Settlement #</th>
                        @if(!$vendor) <th>Vendor</th> @endif
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
                            <td colspan="{{ $vendor ? 8 : 9 }}" class="text-center py-4 text-muted">No vendor settlements recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Settlement Modal -->
    @if($showSettlementModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-warning text-dark py-2">
                        <h6 class="modal-title fw-bold fs-13"><i class="fas fa-hand-holding-usd me-2"></i>Process Vendor Settlement Payout</h6>
                        <button wire:click="$set('showSettlementModal', false)" type="button" class="btn-close" aria-label="Close"></button>
                    </div>
                    <form wire:submit.prevent="saveSettlement">
                        <div class="modal-body p-3 fs-12">
                            <div class="row g-2">
                                @if(!$vendor)
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-bold">Select Vendor <span class="text-danger">*</span></label>
                                        <select wire:model.live="vendor_id" class="form-select form-select-sm">
                                            <option value="">-- Choose Vendor --</option>
                                            @foreach($vendors as $v)
                                                <option value="{{ $v->id }}">{{ $v->name }} (Balance: Rs. {{ number_format($v->current_balance) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Settlement Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="settlement_date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Payout Amount (Rs.) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" wire:model="paid_amount" class="form-control form-control-sm @error('paid_amount') is-invalid @enderror" placeholder="0.00">
                                    @error('paid_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                                    <select wire:model="payment_method" class="form-select form-select-sm">
                                        <option value="Cash">Cash in Hand</option>
                                        <option value="Bank Transfer">Bank Transfer (IBFT)</option>
                                        <option value="Cheque">Bank Cheque</option>
                                        <option value="Online / POS">Online / POS</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Cash/Bank Ledger Account (Accounting Integration)</label>
                                    <select wire:model="account_id" class="form-select form-select-sm">
                                        <option value="">-- Select Cash/Bank Account --</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->account_code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Reference / Cheque Number</label>
                                    <input type="text" wire:model="reference_number" class="form-control form-control-sm" placeholder="e.g. TR-998877 / CHQ-100200">
                                </div>
                                <div class="col-12 mb-2">
                                    <label class="form-label fw-bold">Remarks / Payout Description</label>
                                    <textarea wire:model="remarks" class="form-control form-control-sm" rows="2" placeholder="e.g. Full settlement payout for August photography services..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button wire:click="$set('showSettlementModal', false)" type="button" class="btn btn-secondary btn-sm px-3">Cancel</button>
                            <button type="submit" class="btn btn-warning btn-sm px-4 text-dark fw-bold"><i class="fas fa-check-circle me-1"></i> Authorize & Post Payout</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
