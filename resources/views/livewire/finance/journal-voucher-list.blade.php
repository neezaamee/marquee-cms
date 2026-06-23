<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-file-invoice me-2 text-primary"></span>Journal Vouchers</h5>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('finance.journal-vouchers.create') }}" class="btn btn-falcon-primary btn-sm text-nowrap">
                    <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> New Voucher
                </a>
            </div>
        </div>

        <div class="card-body bg-light border-top border-bottom py-2">
            <div class="row g-2">
                <div class="col-lg-3 col-md-4 col-12">
                    <div class="input-group input-group-sm">
                        <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search voucher #, ref..." />
                        <span class="input-group-text"><span class="fas fa-search"></span></span>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4 col-6">
                    <select wire:model.live="filterBranch" class="form-select form-select-sm" {{ auth()->user()->branch_id ? 'disabled' : '' }}>
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 col-6">
                    <select wire:model.live="filterFinancialYear" class="form-select form-select-sm">
                        <option value="">All Financial Years</option>
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}">{{ $fy->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 col-6">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="col-lg-1.5 col-md-4 col-6">
                    <input wire:model.live="startDate" type="date" class="form-control form-control-sm" placeholder="From Date" title="From Voucher Date" />
                </div>

                <div class="col-lg-1.5 col-md-4 col-6">
                    <input wire:model.live="endDate" type="date" class="form-control form-control-sm" placeholder="To Date" title="To Voucher Date" />
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

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0 align-middle table-hover">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3">Voucher No</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Reference</th>
                            <th>Notes</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Status</th>
                            <th class="text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $v)
                            <tr>
                                <td class="px-3">
                                    <span class="badge badge-subtle-secondary fs-11 font-monospace">{{ $v->voucher_no }}</span>
                                </td>
                                <td class="font-monospace text-nowrap">{{ $v->voucher_date->format('M d, Y') }}</td>
                                <td>{{ $v->branch->name ?? 'Central / Head Office' }}</td>
                                <td>{{ $v->reference ?? '—' }}</td>
                                <td class="text-muted text-nowrap" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $v->notes ?? '—' }}
                                </td>
                                <td class="text-end font-monospace fw-bold text-primary">
                                    {{ number_format($v->total_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusColors = [
                                            'draft' => 'secondary',
                                            'posted' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $sc = $statusColors[$v->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill">{{ ucfirst($v->status) }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end gap-2">
                                        @if($v->status === 'draft')
                                            <a href="{{ route('finance.journal-vouchers.edit', $v->id) }}" class="btn btn-link p-0 text-primary" title="Edit">
                                                <span class="fas fa-edit"></span>
                                            </a>
                                            <button wire:click="postVoucher({{ $v->id }})" class="btn btn-link p-0 text-success" title="Post to Ledger">
                                                <span class="fas fa-check-circle"></span>
                                            </button>
                                            <button onclick="confirm('Are you sure you want to delete this voucher?') || event.stopImmediatePropagation()" wire:click="deleteVoucher({{ $v->id }})" class="btn btn-link p-0 text-danger" title="Delete">
                                                <span class="fas fa-trash-alt"></span>
                                            </button>
                                        @else
                                            @if($v->status === 'posted')
                                                <button onclick="confirm('Are you sure you want to cancel this posted voucher?') || event.stopImmediatePropagation()" wire:click="cancelVoucher({{ $v->id }})" class="btn btn-link p-0 text-warning" title="Cancel Voucher">
                                                    <span class="fas fa-ban"></span>
                                                </button>
                                            @endif
                                            <span class="text-muted fs-11">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <span class="fas fa-file-invoice fa-2x mb-2 d-block"></span>
                                    No journal vouchers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($vouchers->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $vouchers->links() }}
            </div>
        @endif
    </div>
</div>
