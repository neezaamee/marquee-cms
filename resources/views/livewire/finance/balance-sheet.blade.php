<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-balance-scale me-2 text-primary"></span>Balance Sheet</h5>
        </div>

        <div class="card-body bg-light border-top border-bottom py-3">
            <form wire:submit.prevent="generateReport">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fs-11 fw-bold mb-1" for="bs_fy">Financial Year <span class="text-danger">*</span></label>
                        <select wire:model.live="financial_year_id" class="form-select form-select-sm @error('financial_year_id') is-invalid @enderror" id="bs_fy">
                            <option value="">Select Financial Year</option>
                            @foreach($financialYears as $fy)
                                <option value="{{ $fy->id }}">{{ $fy->name }} ({{ $fy->status === 'active' ? 'Active' : 'Closed' }})</option>
                            @endforeach
                        </select>
                        @error('financial_year_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fs-11 fw-bold mb-1" for="bs_branch">Branch Location</label>
                        <select wire:model="branch_id" class="form-select form-select-sm" id="bs_branch" {{ auth()->user()->branch_id ? 'disabled' : '' }}>
                            <option value="">Central / All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fs-11 fw-bold mb-1" for="bs_date">As of Date <span class="text-danger">*</span></label>
                        <input wire:model="asOfDate" type="date" class="form-control form-control-sm @error('asOfDate') is-invalid @enderror" id="bs_date">
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
                <!-- Balance Status Verification Banner -->
                <div class="m-3">
                    @if($reportData['is_balanced'])
                        <div class="alert alert-success border-2 d-flex align-items-center mb-0 py-2" role="alert">
                            <div class="bg-success me-2 icon-item icon-item-sm" style="width: 24px; height: 24px;"><span class="fas fa-check text-white fs-11"></span></div>
                            <h6 class="mb-0 text-success-800 fs-10">Balance Sheet balances: <strong>Assets = Liabilities + Equity</strong>.</h6>
                        </div>
                    @else
                        <div class="alert alert-danger border-2 d-flex align-items-center mb-0 py-2" role="alert">
                            <div class="bg-danger me-2 icon-item icon-item-sm" style="width: 24px; height: 24px;"><span class="fas fa-times text-white fs-11"></span></div>
                            <h6 class="mb-0 text-danger-800 fs-10">Unbalanced sheet! Difference: <strong>{{ number_format($reportData['difference'], 2) }}</strong>. Please check matching transaction logs.</h6>
                        </div>
                    @endif
                </div>

                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                    <div>
                        <h6 class="mb-1">Statement of Financial Position</h6>
                        <span class="text-muted fs-11">As of Date: <strong>{{ date('M d, Y', strtotime($reportData['as_of_date'])) }}</strong> | Financial Year: <strong>{{ $reportData['financial_year']->name }}</strong></span>
                    </div>
                    @if($branch_id)
                        <span class="badge badge-subtle-primary rounded-pill">{{ \App\Models\Branch::find($branch_id)->name }}</span>
                    @else
                        <span class="badge badge-subtle-secondary rounded-pill">Central (All Branches)</span>
                    @endif
                </div>

                <div class="p-3">
                    <div class="row g-4">
                        <!-- Assets Column -->
                        <div class="col-md-6 border-end">
                            <table class="table table-sm fs-10 mb-0 align-middle">
                                <thead>
                                    <tr class="table-light fw-bold">
                                        <th colspan="3" class="fs-9 text-success text-uppercase px-2 py-2">
                                            <span class="fas fa-plus-circle me-1"></span>Assets
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData['asset_rows'] as $row)
                                        <tr>
                                            <td class="font-monospace text-secondary" style="width: 20%;">{{ $row['account_code'] }}</td>
                                            <td class="fw-semi-bold" style="width: 50%;">{{ $row['account_name'] }} <span class="text-muted fs-11 fw-normal">({{ $row['type_name'] }})</span></td>
                                            <td class="text-end font-monospace text-success px-2 fw-bold" style="width: 30%;">
                                                Rs. {{ number_format($row['balance'], 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">No asset accounts recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold border-top bg-subtle-success fs-9">
                                        <td colspan="2" class="px-2 py-2 text-uppercase">Total Assets:</td>
                                        <td class="text-end font-monospace px-2 py-2">Rs. {{ number_format($reportData['total_assets'], 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Liabilities & Equity Column -->
                        <div class="col-md-6">
                            <table class="table table-sm fs-10 mb-3 align-middle">
                                <thead>
                                    <tr class="table-light fw-bold">
                                        <th colspan="3" class="fs-9 text-warning text-uppercase px-2 py-2">
                                            <span class="fas fa-minus-circle me-1"></span>Liabilities
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData['liability_rows'] as $row)
                                        <tr>
                                            <td class="font-monospace text-secondary" style="width: 20%;">{{ $row['account_code'] }}</td>
                                            <td class="fw-semi-bold" style="width: 50%;">{{ $row['account_name'] }} <span class="text-muted fs-11 fw-normal">({{ $row['type_name'] }})</span></td>
                                            <td class="text-end font-monospace text-warning px-2 fw-bold" style="width: 30%;">
                                                Rs. {{ number_format($row['balance'], 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">No liability accounts recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold border-top bg-subtle-warning fs-9">
                                        <td colspan="2" class="px-2 py-2 text-uppercase">Total Liabilities:</td>
                                        <td class="text-end font-monospace px-2 py-2">Rs. {{ number_format($reportData['total_liabilities'], 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <table class="table table-sm fs-10 mb-0 align-middle">
                                <thead>
                                    <tr class="table-light fw-bold">
                                        <th colspan="3" class="fs-9 text-info text-uppercase px-2 py-2">
                                            <span class="fas fa-chart-pie me-1"></span>Owner Equity
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData['equity_rows'] as $row)
                                        <tr>
                                            <td class="font-monospace text-secondary" style="width: 20%;">{{ $row['account_code'] }}</td>
                                            <td class="fw-semi-bold" style="width: 50%;">{{ $row['account_name'] }} <span class="text-muted fs-11 fw-normal">({{ $row['type_name'] }})</span></td>
                                            <td class="text-end font-monospace text-info px-2 fw-bold" style="width: 30%;">
                                                Rs. {{ number_format($row['balance'], 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">No equity accounts recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold border-top bg-subtle-info fs-9">
                                        <td colspan="2" class="px-2 py-2 text-uppercase">Total Equity:</td>
                                        <td class="text-end font-monospace px-2 py-2">Rs. {{ number_format($reportData['total_equity'], 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <!-- Total Liabilities & Equity Summary -->
                            <div class="mt-3 border rounded p-3 bg-light d-flex justify-content-between align-items-center">
                                <span class="fw-bold fs-10 text-uppercase">Total Liabilities & Equity:</span>
                                <span class="font-monospace fw-bold fs-9 text-primary">Rs. {{ number_format($reportData['total_liabilities_and_equity'], 2) }}</span>
                            </div>
                        </div>
                    </div>
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
