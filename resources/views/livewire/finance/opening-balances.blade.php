<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-balance-scale me-2 text-primary"></span>Opening Balances</h5>
            <div>
                <button wire:click="save" class="btn btn-primary btn-sm text-nowrap" type="button">
                    <span class="fas fa-save me-1"></span> Save Balances
                </button>
            </div>
        </div>

        <div class="card-body bg-light border-top border-bottom py-2">
            <div class="row g-2">
                <div class="col-md-4 col-12">
                    <label class="form-label fs-11 fw-bold mb-1" for="fy_select">Financial Year</label>
                    <select wire:model.live="financial_year_id" class="form-select form-select-sm" id="fy_select">
                        <option value="">Select Financial Year</option>
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}">{{ $fy->name }} ({{ $fy->status === 'active' ? 'Active' : 'Closed' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-12">
                    <label class="form-label fs-11 fw-bold mb-1" for="branch_select">Branch Location</label>
                    <select wire:model.live="branch_id" class="form-select form-select-sm" id="branch_select" {{ auth()->user()->branch_id ? 'disabled' : '' }}>
                        <option value="">Central / Head Office (All Branches)</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-success-800">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(empty($financial_year_id))
                <div class="text-center py-5 text-muted">
                    <span class="fas fa-info-circle fa-2x mb-2 d-block"></span>
                    Please select a Financial Year to view or enter opening balances.
                </div>
            @else
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="px-3" style="width: 15%;">Account Code</th>
                                <th style="width: 35%;">Account Name</th>
                                <th style="width: 15%;">Account Type</th>
                                <th style="width: 10%;">Nature</th>
                                <th class="text-end" style="width: 12%;">Opening Debit</th>
                                <th class="text-end" style="width: 12%;">Opening Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                                <tr>
                                    <td class="px-3 font-monospace fw-bold text-secondary">{{ $account->account_code }}</td>
                                    <td class="fw-semi-bold">{{ $account->name }}</td>
                                    <td>{{ $account->accountType->name ?? '—' }}</td>
                                    <td>
                                        @php
                                            $natureColors = [
                                                'Asset' => 'success',
                                                'Liability' => 'warning',
                                                'Equity' => 'info',
                                                'Income' => 'primary',
                                                'Expense' => 'danger'
                                            ];
                                            $nc = $natureColors[$account->nature] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-subtle-{{ $nc }} rounded-pill">{{ $account->nature }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="input-group input-group-sm justify-content-end">
                                            <input wire:model.blur="balances.{{ $account->id }}.debit" type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-monospace py-0 style-input" style="max-width: 150px;" placeholder="0.00">
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="input-group input-group-sm justify-content-end">
                                            <input wire:model.blur="balances.{{ $account->id }}.credit" type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-monospace py-0 style-input" style="max-width: 150px;" placeholder="0.00">
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <span class="fas fa-sitemap fa-2x mb-2 d-block"></span>
                                        No active leaf accounts found in Chart of Accounts.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-200 fw-bold">
                            <tr>
                                <td colspan="4" class="text-end px-3">Totals:</td>
                                <td class="text-end font-monospace text-primary px-3 fs-9">
                                    {{ number_format($totalDebit, 2) }}
                                </td>
                                <td class="text-end font-monospace text-primary px-3 fs-9">
                                    {{ number_format($totalCredit, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <span class="text-900 fs-11">
                        @if(abs($totalDebit - $totalCredit) < 0.01)
                            <span class="text-success fw-bold"><span class="fas fa-check-circle me-1"></span>Trial Balance is Balanced</span>
                        @else
                            <span class="text-danger fw-bold"><span class="fas fa-times-circle me-1"></span>Unbalanced (Difference: {{ number_format(abs($totalDebit - $totalCredit), 2) }})</span>
                        @endif
                    </span>
                    <button wire:click="save" class="btn btn-primary btn-sm" type="button">
                        <span class="fas fa-save me-1"></span> Save Balances
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
