<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-file-invoice-dollar me-2 text-primary"></span>Profit & Loss Statement</h5>
        </div>

        <div class="card-body bg-light border-top border-bottom py-3">
            <form wire:submit.prevent="generateReport">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fs-11 fw-bold mb-1" for="pl_fy">Financial Year <span class="text-danger">*</span></label>
                        <select wire:model.live="financial_year_id" class="form-select form-select-sm @error('financial_year_id') is-invalid @enderror" id="pl_fy">
                            <option value="">Select Financial Year</option>
                            @foreach($financialYears as $fy)
                                <option value="{{ $fy->id }}">{{ $fy->name }} ({{ $fy->status === 'active' ? 'Active' : 'Closed' }})</option>
                            @endforeach
                        </select>
                        @error('financial_year_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fs-11 fw-bold mb-1" for="pl_branch">Branch Location</label>
                        <select wire:model="branch_id" class="form-select form-select-sm" id="pl_branch" {{ auth()->user()->branch_id ? 'disabled' : '' }}>
                            <option value="">Central / All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fs-11 fw-bold mb-1" for="pl_start">Start Date <span class="text-danger">*</span></label>
                        <input wire:model="startDate" type="date" class="form-control form-control-sm @error('startDate') is-invalid @enderror" id="pl_start">
                        @error('startDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fs-11 fw-bold mb-1" for="pl_end">End Date <span class="text-danger">*</span></label>
                        <input wire:model="endDate" type="date" class="form-control form-control-sm @error('endDate') is-invalid @enderror" id="pl_end">
                        @error('endDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
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
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                    <div>
                        <h6 class="mb-1">Income & Expenditure Statement</h6>
                        <span class="text-muted fs-11">Period: <strong>{{ date('M d, Y', strtotime($reportData['start_date'])) }}</strong> to <strong>{{ date('M d, Y', strtotime($reportData['end_date'])) }}</strong> | Financial Year: <strong>{{ $reportData['financial_year']->name }}</strong></span>
                    </div>
                    @if($branch_id)
                        <span class="badge badge-subtle-primary rounded-pill">{{ \App\Models\Branch::find($branch_id)->name }}</span>
                    @else
                        <span class="badge badge-subtle-secondary rounded-pill">Central (All Branches)</span>
                    @endif
                </div>

                <div class="p-3">
                    <table class="table table-sm fs-10 mb-0 table-hover align-middle">
                        <tbody>
                            <!-- Income Section Header -->
                            <tr class="table-light fw-bold">
                                <td colspan="3" class="fs-9 text-primary text-uppercase px-3 py-2">
                                    <span class="fas fa-arrow-alt-circle-down me-2"></span>Revenue / Income
                                </td>
                            </tr>
                            @forelse($reportData['income_rows'] as $row)
                                <tr>
                                    <td class="px-4 font-monospace text-secondary" style="width: 15%;">{{ $row['account_code'] }}</td>
                                    <td class="fw-semi-bold" style="width: 60%;">{{ $row['account_name'] }} <span class="text-muted fs-11 fw-normal">({{ $row['type_name'] }})</span></td>
                                    <td class="text-end font-monospace text-success px-3 fw-bold" style="width: 25%;">
                                        Rs. {{ number_format($row['balance'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">No revenue/income transactions recorded for this period.</td>
                                </tr>
                            @endforelse
                            <tr class="fw-bold border-top border-bottom">
                                <td colspan="2" class="text-end px-3 py-2 fs-10 text-uppercase">Total Income:</td>
                                <td class="text-end font-monospace text-success px-3 py-2 fs-9">Rs. {{ number_format($reportData['total_income'], 2) }}</td>
                            </tr>

                            <!-- Spacer Row -->
                            <tr><td colspan="3" class="py-2 border-0"></td></tr>

                            <!-- Expenses Section Header -->
                            <tr class="table-light fw-bold">
                                <td colspan="3" class="fs-9 text-danger text-uppercase px-3 py-2">
                                    <span class="fas fa-arrow-alt-circle-up me-2"></span>Expenditure / Expenses
                                </td>
                            </tr>
                            @forelse($reportData['expense_rows'] as $row)
                                <tr>
                                    <td class="px-4 font-monospace text-secondary" style="width: 15%;">{{ $row['account_code'] }}</td>
                                    <td class="fw-semi-bold" style="width: 60%;">{{ $row['account_name'] }} <span class="text-muted fs-11 fw-normal">({{ $row['type_name'] }})</span></td>
                                    <td class="text-end font-monospace text-danger px-3 fw-bold" style="width: 25%;">
                                        Rs. {{ number_format($row['balance'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">No operating expenditures recorded for this period.</td>
                                </tr>
                            @endforelse
                            <tr class="fw-bold border-top border-bottom">
                                <td colspan="2" class="text-end px-3 py-2 fs-10 text-uppercase">Total Expenditure:</td>
                                <td class="text-end font-monospace text-danger px-3 py-2 fs-9">Rs. {{ number_format($reportData['total_expense'], 2) }}</td>
                            </tr>

                            <!-- Net Profit/Loss Summary Row -->
                            <tr><td colspan="3" class="py-3 border-0"></td></tr>
                            <tr class="border-top-2 border-bottom-2 align-middle">
                                <td colspan="2" class="text-end px-3 py-3 fs-9 text-uppercase fw-bold">
                                    Net {{ $reportData['net_profit_loss'] >= 0 ? 'Profit' : 'Loss' }}:
                                </td>
                                <td class="text-end font-monospace px-3 py-3 fs-8 fw-bold {{ $reportData['net_profit_loss'] >= 0 ? 'text-success bg-subtle-success' : 'text-danger bg-subtle-danger' }}">
                                    Rs. {{ number_format($reportData['net_profit_loss'], 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <span class="fas fa-file-invoice-dollar fa-2x mb-2 d-block"></span>
                    Please select filters and click Generate Report.
                </div>
            @endif
        </div>
    </div>
</div>
