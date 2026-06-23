<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">
                <span class="fas fa-file-invoice me-2 text-primary"></span>
                {{ $editId ? 'Edit' : 'New' }} Journal Voucher
                @if($voucher_no)
                    <small class="text-muted font-monospace">[{{ $voucher_no }}]</small>
                @endif
            </h5>
            <div>
                <a href="{{ route('finance.journal-vouchers.index') }}" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-arrow-left me-1"></span> Back to List
                </a>
            </div>
        </div>

        <form wire:submit.prevent="save">
            <div class="card-body bg-light border-bottom">
                @if(session('validation_error'))
                    <div class="alert alert-danger border-2 d-flex align-items-center mb-3" role="alert">
                        <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                        <p class="mb-0 flex-grow-1 text-danger-800">{{ session('validation_error') }}</p>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="v_date">Voucher Date <span class="text-danger">*</span></label>
                        <input wire:model="voucher_date" type="date" class="form-control form-control-sm @error('voucher_date') is-invalid @enderror" id="v_date">
                        @error('voucher_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="v_branch">Branch Location</label>
                        <select wire:model="branch_id" class="form-select form-select-sm @error('branch_id') is-invalid @enderror" id="v_branch" {{ auth()->user()->branch_id ? 'disabled' : '' }}>
                            <option value="">Central / Head Office</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="v_ref">Reference</label>
                        <input wire:model="reference" type="text" class="form-control form-control-sm @error('reference') is-invalid @enderror" id="v_ref" placeholder="Cheque #, Invoice #, etc.">
                        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="v_status">Status</label>
                        <select wire:model="status" class="form-select form-select-sm @error('status') is-invalid @enderror" id="v_status">
                            <option value="draft">Draft (Save Only)</option>
                            <option value="posted">Posted (Save & Post to Ledger)</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" for="v_notes">Notes / Narration Header</label>
                        <textarea wire:model="notes" class="form-control form-control-sm @error('notes') is-invalid @enderror" id="v_notes" rows="2" placeholder="Overall notes about this journal voucher..."></textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="px-3" style="width: 35%;">Account</th>
                                <th class="text-end" style="width: 15%;">Debit</th>
                                <th class="text-end" style="width: 15%;">Credit</th>
                                <th style="width: 30%;">Line Narration</th>
                                <th class="text-center" style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                <tr>
                                    <td class="px-3">
                                        <select wire:model="items.{{ $index }}.account_id" class="form-select form-select-sm @error('items.'.$index.'.account_id') is-invalid @enderror">
                                            <option value="">Select Account</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}">[{{ $acc->account_code }}] {{ $acc->name }} ({{ $acc->nature }})</option>
                                            @endforeach
                                        </select>
                                        @error('items.'.$index.'.account_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input wire:model.blur="items.{{ $index }}.debit" type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-monospace" placeholder="0.00">
                                        @error('items.'.$index.'.debit') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input wire:model.blur="items.{{ $index }}.credit" type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-monospace" placeholder="0.00">
                                        @error('items.'.$index.'.credit') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input wire:model="items.{{ $index }}.narration" type="text" class="form-control form-control-sm" placeholder="Optional line details...">
                                        @error('items.'.$index.'.narration') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </td>
                                    <td class="text-center">
                                        @if(count($items) > 2)
                                            <button type="button" wire:click="removeRow({{ $index }})" class="btn btn-link p-0 text-danger" title="Remove line">
                                                <span class="fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-200 fw-bold">
                            <tr>
                                <td class="text-end px-3">
                                    <button type="button" wire:click="addRow" class="btn btn-falcon-default btn-xs">
                                        <span class="fas fa-plus me-1"></span>Add Line Item
                                    </button>
                                </td>
                                <td class="text-end font-monospace text-primary fs-9">{{ number_format($totalDebit, 2) }}</td>
                                <td class="text-end font-monospace text-primary fs-9">{{ number_format($totalCredit, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <span class="text-900 fs-11">
                        @if($totalDebit > 0 && abs($totalDebit - $totalCredit) < 0.001)
                            <span class="text-success fw-bold"><span class="fas fa-check-circle me-1"></span>Voucher is Balanced</span>
                        @else
                            @if($totalDebit == 0 && $totalCredit == 0)
                                <span class="text-muted"><span class="fas fa-info-circle me-1"></span>Please enter debit/credit items</span>
                            @else
                                <span class="text-danger fw-bold"><span class="fas fa-times-circle me-1"></span>Unbalanced (Difference: {{ number_format(abs($totalDebit - $totalCredit), 2) }})</span>
                            @endif
                        @endif
                    </span>
                    <div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <span class="fas fa-save me-1"></span> Save Journal Voucher
                        </button>
                        <a href="{{ route('finance.journal-vouchers.index') }}" class="btn btn-falcon-default btn-sm ms-2">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
