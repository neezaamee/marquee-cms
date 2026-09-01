<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-book me-2 text-primary"></span>General Ledger Inquiry</h5>
        </div>

        <div class="card-body bg-light border-top border-bottom py-3">
            <form wire:submit.prevent="generateReport">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fs-11 fw-bold mb-1" for="gl_account">Account <span class="text-danger">*</span></label>
                        <select wire:model="account_id" class="form-select form-select-sm @error('account_id') is-invalid @enderror" id="gl_account">
                            <option value="">Select Account</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">[{{ $acc->account_code }}] {{ $acc->name }} ({{ $acc->nature }})</option>
                            @endforeach
                        </select>
                        @error('account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fs-11 fw-bold mb-1" for="gl_fy">Financial Year <span class="text-danger">*</span></label>
                        <select wire:model.live="financial_year_id" class="form-select form-select-sm @error('financial_year_id') is-invalid @enderror" id="gl_fy">
                            <option value="">Select FY</option>
                            @foreach($financialYears as $fy)
                                <option value="{{ $fy->id }}">{{ $fy->name }}</option>
                            @endforeach
                        </select>
                        @error('financial_year_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if(!$isSaas)
                    <div class="col-md-2">
                        <label class="form-label fs-11 fw-bold mb-1" for="gl_branch">Branch</label>
                        <select wire:model="branch_id" class="form-select form-select-sm" id="gl_branch" {{ auth()->user()->branch_id ? 'disabled' : '' }}>
                            <option value="">Central / All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-md-2">
                        <label class="form-label fs-11 fw-bold mb-1" for="gl_start">Start Date</label>
                        <input wire:model="startDate" type="date" class="form-control form-control-sm @error('startDate') is-invalid @enderror" id="gl_start">
                        @error('startDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fs-11 fw-bold mb-1" for="gl_end">End Date</label>
                        <input wire:model="endDate" type="date" class="form-control form-control-sm @error('endDate') is-invalid @enderror" id="gl_end">
                        @error('endDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-grow-1 text-danger-800">{{ session('error') }}</p>
                </div>
            @endif

            @if($ledgerData)
                <!-- Ledger Header Info -->
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                    <div>
                        <h6 class="mb-1">[{{ $ledgerData['account']->account_code }}] {{ $ledgerData['account']->name }}</h6>
                        <span class="text-muted fs-11">Nature: <strong>{{ $ledgerData['account']->nature }}</strong> | Period: <strong>{{ date('M d, Y', strtotime($startDate)) }}</strong> to <strong>{{ date('M d, Y', strtotime($endDate)) }}</strong></span>
                    </div>
                    @if($isSaas)
                        <span class="badge badge-subtle-primary rounded-pill">SaaS Platform</span>
                    @elseif($branch_id)
                        <span class="badge badge-subtle-primary rounded-pill">{{ \App\Models\Branch::find($branch_id)->name }}</span>
                    @else
                        <span class="badge badge-subtle-secondary rounded-pill">Central (All Branches)</span>
                    @endif
                </div>

                <!-- Ledger Quick Stats Cards -->
                <div class="row g-2 p-3 bg-light border-bottom">
                    <div class="col-md-3">
                        <div class="card shadow-none border border-200">
                            <div class="card-body p-3">
                                <p class="text-muted fs-11 mb-1 fw-bold">Opening Balance</p>
                                <h5 class="mb-0 font-monospace">{{ number_format($ledgerData['opening_balance'], 2) }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-none border border-200">
                            <div class="card-body p-3">
                                <p class="text-muted fs-11 mb-1 fw-bold text-success">Total Debits</p>
                                <h5 class="mb-0 font-monospace text-success">+{{ number_format($ledgerData['total_debit'], 2) }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-none border border-200">
                            <div class="card-body p-3">
                                <p class="text-muted fs-11 mb-1 fw-bold text-danger">Total Credits</p>
                                <h5 class="mb-0 font-monospace text-danger">-{{ number_format($ledgerData['total_credit'], 2) }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-none border border-200">
                            <div class="card-body p-3">
                                <p class="text-muted fs-11 mb-1 fw-bold text-primary">Closing Balance</p>
                                <h5 class="mb-0 font-monospace text-primary">{{ number_format($ledgerData['closing_balance'], 2) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ledger Table -->
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="px-3" style="width: 10%;">Date</th>
                                <th style="width: 12%;">Voucher No</th>
                                <th style="width: 12%;">Reference</th>
                                <th style="width: 36%;">Narration</th>
                                <th class="text-end" style="width: 10%;">Debit</th>
                                <th class="text-end" style="width: 10%;">Credit</th>
                                <th class="text-end px-3" style="width: 10%;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Opening Balance Row -->
                            <tr class="fw-bold bg-light">
                                <td class="px-3"></td>
                                <td colspan="3">Opening Balance Brought Forward</td>
                                <td class="text-end font-monospace">
                                    {{ $ledgerData['opening_balance_debit'] > 0 ? number_format($ledgerData['opening_balance_debit'], 2) : '—' }}
                                </td>
                                <td class="text-end font-monospace">
                                    {{ $ledgerData['opening_balance_credit'] > 0 ? number_format($ledgerData['opening_balance_credit'], 2) : '—' }}
                                </td>
                                <td class="text-end px-3 font-monospace">
                                    {{ number_format($ledgerData['opening_balance'], 2) }}
                                </td>
                            </tr>

                            <!-- Posted Entries -->
                            @forelse($ledgerData['entries'] as $entry)
                                <tr>
                                    <td class="px-3 font-monospace text-nowrap">{{ date('Y-m-d', strtotime($entry['voucher_date'])) }}</td>
                                    <td>
                                        <span class="badge badge-subtle-secondary font-monospace">{{ $entry['voucher_no'] }}</span>
                                    </td>
                                    <td>{{ $entry['reference'] ?? '—' }}</td>
                                    <td>{{ $entry['narration'] ?? '—' }}</td>
                                    <td class="text-end font-monospace text-success">
                                        {{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end font-monospace text-danger">
                                        {{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end px-3 font-monospace fw-semi-bold">
                                        {{ number_format($entry['running_balance'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No transaction entries found for this date range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-200 fw-bold">
                            <tr>
                                <td colspan="4" class="text-end px-3">Closing Balances / Totals:</td>
                                <td class="text-end font-monospace text-success fs-9">+{{ number_format($ledgerData['total_debit'], 2) }}</td>
                                <td class="text-end font-monospace text-danger fs-9">-{{ number_format($ledgerData['total_credit'], 2) }}</td>
                                <td class="text-end font-monospace text-primary px-3 fs-9">{{ number_format($ledgerData['closing_balance'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <span class="fas fa-book fa-2x mb-2 d-block"></span>
                    Please select filters and click Search to load the ledger records.
                </div>
            @endif
        </div>
    </div>
</div>
