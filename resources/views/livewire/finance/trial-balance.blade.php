<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-balance-scale me-2 text-primary"></span>Trial Balance Report</h5>
        </div>

        <div class="card-body bg-light border-top border-bottom py-3">
            <form wire:submit.prevent="generateReport">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fs-11 fw-bold mb-1" for="tb_fy">Financial Year <span class="text-danger">*</span></label>
                        <select wire:model.live="financial_year_id" class="form-select form-select-sm @error('financial_year_id') is-invalid @enderror" id="tb_fy">
                            <option value="">Select Financial Year</option>
                            @foreach($financialYears as $fy)
                                <option value="{{ $fy->id }}">{{ $fy->name }} ({{ $fy->status === 'active' ? 'Active' : 'Closed' }})</option>
                            @endforeach
                        </select>
                        @error('financial_year_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fs-11 fw-bold mb-1" for="tb_branch">Branch Location</label>
                        <select wire:model="branch_id" class="form-select form-select-sm" id="tb_branch" {{ auth()->user()->branch_id ? 'disabled' : '' }}>
                            <option value="">Central / All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fs-11 fw-bold mb-1" for="tb_date">As of Date <span class="text-danger">*</span></label>
                        <input wire:model="asOfDate" type="date" class="form-control form-control-sm @error('asOfDate') is-invalid @enderror" id="tb_date">
                        @error('asOfDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <span class="fas fa-sync me-1"></span>Generate Report
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

            @if($reportData)
                <!-- Balance Status Banner -->
                <div class="m-3">
                    @if($reportData['is_balanced'])
                        <div class="alert alert-success border-2 d-flex align-items-center mb-0 py-2" role="alert">
                            <div class="bg-success me-2 icon-item icon-item-sm" style="width: 24px; height: 24px;"><span class="fas fa-check text-white fs-11"></span></div>
                            <h6 class="mb-0 text-success-800 fs-10">Ledger is balanced. Total Debits equal Total Credits.</h6>
                        </div>
                    @else
                        <div class="alert alert-danger border-2 d-flex align-items-center mb-0 py-2" role="alert">
                            <div class="bg-danger me-2 icon-item icon-item-sm" style="width: 24px; height: 24px;"><span class="fas fa-times text-white fs-11"></span></div>
                            <h6 class="mb-0 text-danger-800 fs-10">Ledger is UNBALANCED! Difference: <strong>{{ number_format(abs($reportData['total_debit'] - $reportData['total_credit']), 2) }}</strong>. Please review journal entries.</h6>
                        </div>
                    @endif
                </div>

                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                    <div>
                        <h6 class="mb-1">Trial Balance Sheet</h6>
                        <span class="text-muted fs-11">As of: <strong>{{ date('M d, Y', strtotime($reportData['as_of_date'])) }}</strong> | Financial Year: <strong>{{ $reportData['financial_year']->name }}</strong></span>
                    </div>
                    @if($branch_id)
                        <span class="badge badge-subtle-primary rounded-pill">{{ Branch::find($branch_id)->name }}</span>
                    @else
                        <span class="badge badge-subtle-secondary rounded-pill">Central (All Branches)</span>
                    @endif
                </div>

                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="px-3" style="width: 15%;">Account Code</th>
                                <th style="width: 35%;">Account Name</th>
                                <th style="width: 15%;">Account Type</th>
                                <th style="width: 15%;">Nature</th>
                                <th class="text-end" style="width: 10%;">Debit</th>
                                <th class="text-end px-3" style="width: 10%;">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['rows'] as $row)
                                <tr>
                                    <td class="px-3 font-monospace fw-bold text-secondary">{{ $row['account_code'] }}</td>
                                    <td class="fw-semi-bold">{{ $row['account_name'] }}</td>
                                    <td>{{ $row['type_name'] }}</td>
                                    <td>
                                        @php
                                            $natureColors = [
                                                'Asset' => 'success',
                                                'Liability' => 'warning',
                                                'Equity' => 'info',
                                                'Income' => 'primary',
                                                'Expense' => 'danger'
                                            ];
                                            $nc = $natureColors[$row['nature']] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-subtle-{{ $nc }} rounded-pill">{{ $row['nature'] }}</span>
                                    </td>
                                    <td class="text-end font-monospace text-success">
                                        {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '—' }}
                                    </td>
                                    <td class="text-end px-3 font-monospace text-danger">
                                        {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No active ledger records found for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-200 fw-bold">
                            <tr>
                                <td colspan="4" class="text-end px-3">Total Net Balances:</td>
                                <td class="text-end font-monospace text-success fs-9">+{{ number_format($reportData['total_debit'], 2) }}</td>
                                <td class="text-end font-monospace text-danger px-3 fs-9">-{{ number_format($reportData['total_credit'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <span class="fas fa-balance-scale fa-2x mb-2 d-block"></span>
                    Please select filters and click Generate Report.
                </div>
            @endif
        </div>
    </div>
</div>
