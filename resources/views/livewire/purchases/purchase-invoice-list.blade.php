<div>
    <div class="card border border-200 shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 px-4">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0 text-primary fw-bold">
                    <span class="fas fa-file-invoice-dollar me-2 text-primary"></span>Purchase Invoices
                </h5>
                <span class="badge badge-subtle-primary rounded-pill font-monospace fs-11 ms-1">
                    {{ $purchaseInvoices->total() }} Total
                </span>
                @if(!empty($filterDateFrom) || !empty($filterDateTo))
                    <span class="badge badge-subtle-info rounded-pill font-monospace fs-11 ms-1" title="Filtered Date Range">
                        <i class="fas fa-calendar-alt me-1"></i>
                        {{ $filterDateFrom ?: 'Start' }} &rarr; {{ $filterDateTo ?: 'Today' }}
                    </span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Page size selector -->
                <select wire:model.live="perPage" class="form-select form-select-sm" style="max-width: 105px;" title="Items per page">
                    <option value="10">10 / page</option>
                    <option value="15">15 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>

                <!-- Date Range Filters -->
                <div class="d-flex align-items-center gap-1 bg-white border rounded px-2 py-1">
                    <span class="text-500 fs-11 fw-bold text-uppercase me-1"><span class="fas fa-calendar-alt text-primary me-1"></span>From:</span>
                    <input type="date" wire:model.live="filterDateFrom" class="form-control form-control-sm border-0 p-0 shadow-none" style="width: 125px;" title="Period From Date" />
                </div>

                <div class="d-flex align-items-center gap-1 bg-white border rounded px-2 py-1">
                    <span class="text-500 fs-11 fw-bold text-uppercase me-1"><span class="fas fa-calendar-check text-primary me-1"></span>To:</span>
                    <input type="date" wire:model.live="filterDateTo" class="form-control form-control-sm border-0 p-0 shadow-none" style="width: 125px;" title="Period To Date" />
                </div>

                @if(!empty($filterDateFrom) || !empty($filterDateTo))
                    <button wire:click="clearDateFilter" type="button" class="btn btn-falcon-default btn-sm text-danger" title="Clear Date Filter">
                        <span class="fas fa-times me-1"></span>Reset Dates
                    </button>
                @endif

                <!-- Supplier Filter -->
                <select wire:model.live="filterSupplier" class="form-select form-select-sm" style="max-width: 180px;">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supp)
                        <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                    @endforeach
                </select>

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="max-width: 140px;">
                    <option value="">All Statuses</option>
                    <option value="Draft">Draft</option>
                    <option value="Posted">Posted</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 220px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search invoice, ref #..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>

                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory'))
                    <a class="btn btn-falcon-primary btn-sm text-nowrap" href="{{ route('purchase-invoices.create') }}">
                        <span class="fas fa-plus me-1"></span>New Bill
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('success') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger border-2 d-flex align-items-center m-3" role="alert">
                    <div class="bg-danger me-3 icon-item"><span class="fas fa-times-circle text-white fs-8"></span></div>
                    <p class="mb-0 flex-1">{{ session('error') }}</p>
                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive scrollbar">
                <table class="table table-hover fs-9 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="ps-3 py-2" style="width: 170px;">Invoice Number</th>
                            <th class="py-2" style="min-width: 180px;">Supplier</th>
                            <th class="py-2" style="width: 120px;">Purchase Date</th>
                            <th class="py-2" style="width: 140px;">Supplier Ref #</th>
                            <th class="py-2 text-end" style="width: 140px;">Net Amount</th>
                            <th class="py-2" style="width: 140px;">Posted JV</th>
                            <th class="py-2 text-center" style="width: 110px;">Status</th>
                            <th class="py-2 text-end pe-3" style="width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseInvoices as $inv)
                            <tr class="hover-actions-trigger">
                                <td class="ps-3 py-2.5 font-monospace">
                                    <span class="badge badge-subtle-primary fs-11 px-2 py-1 fw-bold">
                                        <i class="fas fa-file-invoice me-1"></i>{{ $inv->invoice_number }}
                                    </span>
                                </td>
                                <td class="py-2.5">
                                    <div class="fw-bold text-dark">{{ $inv->supplier->name ?? '—' }}</div>
                                    @if($inv->branch)
                                        <div class="fs-11 text-muted"><i class="fas fa-building me-1"></i>{{ $inv->branch->name }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5 text-nowrap text-secondary">
                                    <i class="far fa-calendar-alt me-1 text-muted"></i>{{ $inv->purchase_date->format('Y-m-d') }}
                                </td>
                                <td class="py-2.5 font-monospace">
                                    @if($inv->reference_number)
                                        <span class="badge badge-subtle-secondary fs-11">{{ $inv->reference_number }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-end font-monospace fw-bold fs-9 text-dark">
                                    Rs. {{ number_format($inv->net_amount, 2) }}
                                </td>
                                <td class="py-2.5 font-monospace">
                                    @if($inv->journalVoucher)
                                        <span class="badge badge-subtle-success fs-11">
                                            <span class="fas fa-book me-1"></span>{{ $inv->journalVoucher->voucher_no }}
                                        </span>
                                    @else
                                        <span class="badge bg-200 text-600 fs-11">Not Posted</span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-center">
                                    @php
                                        $statuses = [
                                            'Draft' => 'secondary',
                                            'Posted' => 'success',
                                            'Cancelled' => 'danger'
                                        ];
                                        $sc = $statuses[$inv->status] ?? 'dark';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill px-2 py-1 fs-11">{{ $inv->status }}</span>
                                </td>
                                <td class="py-2.5 text-end pe-3">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        <!-- Post to Ledger (Draft Only) -->
                                        @if($inv->status === 'Draft' && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_accounting') || auth()->user()->hasPermission('manage_inventory')))
                                            <button type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#postLedgerModal"
                                                    wire:click="confirmPost({{ $inv->id }})"
                                                    class="btn btn-link p-0 text-success"
                                                    title="Post to Ledger">
                                                <span class="fas fa-file-export fs-10"></span>
                                            </button>
                                        @elseif($inv->status === 'Posted')
                                            <span class="text-success" title="Posted to Ledger (JV: {{ $inv->journalVoucher->voucher_no ?? 'Posted' }})">
                                                <span class="fas fa-check-circle fs-11"></span>
                                            </span>
                                        @endif

                                        <!-- View / Edit -->
                                        <a href="{{ route('purchase-invoices.edit', $inv->id) }}" class="btn btn-link p-0 text-primary" title="{{ $inv->status === 'Draft' ? 'Edit Invoice' : 'View Details' }}">
                                            <span class="fas fa-{{ $inv->status === 'Draft' ? 'edit' : 'eye' }} fs-10"></span>
                                        </a>

                                        <!-- Print -->
                                        <a href="{{ route('purchase-invoices.print', $inv->id) }}" target="_blank" class="btn btn-link p-0 text-secondary" title="Print Invoice">
                                            <span class="fas fa-print fs-10"></span>
                                        </a>

                                        <!-- Download PDF -->
                                        <a href="{{ route('purchase-invoices.pdf', $inv->id) }}" target="_blank" class="btn btn-link p-0 text-danger" title="Download PDF">
                                            <span class="fas fa-file-pdf fs-10"></span>
                                        </a>

                                        <!-- Share Dropdown -->
                                        <div class="dropdown font-sans-serif d-inline-block">
                                            <button class="btn btn-link p-0 text-info dropdown-toggle dropdown-caret-none" type="button" id="shareInv{{ $inv->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Share Invoice">
                                                <span class="fas fa-share-alt fs-10"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end py-2" aria-labelledby="shareInv{{ $inv->id }}">
                                                @php
                                                    $invShareText = urlencode("Purchase Invoice #{$inv->invoice_number}\nVendor: " . ($inv->supplier->name ?? '') . "\nDate: " . $inv->purchase_date->format('Y-m-d') . "\nAmount: Rs. " . number_format($inv->net_amount, 2));
                                                    $supPhone = preg_replace('/[^0-9]/', '', $inv->supplier->mobile_number ?? '');
                                                    $waInvUrl = $supPhone ? "https://wa.me/{$supPhone}?text={$invShareText}" : "https://api.whatsapp.com/send?text={$invShareText}";
                                                @endphp
                                                <a class="dropdown-item d-flex align-items-center" href="{{ $waInvUrl }}" target="_blank">
                                                    <span class="fab fa-whatsapp me-2 text-success"></span>Share via WhatsApp
                                                </a>
                                                <button class="dropdown-item d-flex align-items-center" type="button" onclick="navigator.clipboard.writeText('{{ route('purchase-invoices.print', $inv->id) }}'); alert('Invoice link copied to clipboard!');">
                                                    <span class="fas fa-link me-2 text-primary"></span>Copy Link
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Return Items (for Posted Invoices) -->
                                        @if($inv->status === 'Posted')
                                            <a href="{{ route('purchase-returns.create') }}?invoice_id={{ $inv->id }}" class="btn btn-link p-0 text-warning" title="Return Items from this Invoice">
                                                <span class="fas fa-undo-alt fs-10"></span>
                                            </a>
                                        @endif

                                        <!-- Delete (Drafts only) -->
                                        @if($inv->status === 'Draft' && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory')))
                                            <button class="btn btn-link p-0 text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $inv->id }})" title="Delete">
                                                <span class="fas fa-trash-alt fs-10"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="py-3">
                                        <i class="fas fa-file-invoice-dollar fa-3x text-300 mb-2"></i>
                                        <div class="fw-bold fs-9">No purchase invoices found</div>
                                        <div class="fs-11 text-muted">Try adjusting your filters or search keywords</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($purchaseInvoices->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-between bg-light py-2 px-3">
                <div class="fs-11 text-muted">
                    Showing <strong>{{ $purchaseInvoices->firstItem() ?? 0 }}</strong> to <strong>{{ $purchaseInvoices->lastItem() ?? 0 }}</strong> of <strong>{{ $purchaseInvoices->total() }}</strong> invoices
                </div>
                <div>
                    {{ $purchaseInvoices->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Post to Ledger Confirmation Modal (Falcon Styled) -->
    <div wire:ignore.self class="modal fade" id="postLedgerModal" tabindex="-1" aria-labelledby="postLedgerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2 px-3">
                    <h5 class="modal-title text-white fs-9" id="postLedgerModalLabel">
                        <span class="fas fa-file-export me-2"></span>Confirm Post to Ledger
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" wire:click="cancelPostConfirmation"></button>
                </div>
                <div class="modal-body p-3">
                    @if($confirmingInvoice)
                        <div class="p-3 bg-light rounded border border-200 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-600 fs-11">Purchase Invoice:</span>
                                <span class="badge badge-subtle-primary font-monospace fs-11 fw-bold">
                                    {{ $confirmingInvoice->invoice_number }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-600 fs-11">Supplier / Vendor:</span>
                                <span class="fw-bold text-dark fs-11">
                                    {{ $confirmingInvoice->supplier->name ?? '—' }}
                                </span>
                            </div>
                            @if($confirmingInvoice->reference_number)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-600 fs-11">Supplier Ref #:</span>
                                    <span class="font-monospace text-secondary fs-11">
                                        {{ $confirmingInvoice->reference_number }}
                                    </span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="text-800 fw-bold fs-11">Net Amount:</span>
                                <span class="font-monospace fw-bold text-success fs-9">
                                    Rs. {{ number_format($confirmingInvoice->net_amount, 2) }}
                                </span>
                            </div>
                        </div>
                    @endif
                    <p class="mb-2 text-900 fs-11">
                        Are you sure you want to post this purchase invoice to the general ledger?
                    </p>
                    <div class="alert alert-subtle-success d-flex align-items-center p-2 mb-0 fs-11" role="alert">
                        <span class="fas fa-info-circle me-2 fs-10 text-success"></span>
                        <div>
                            This will generate a double-entry <strong>Journal Voucher (JV)</strong>, update physical warehouse stock costs, and record the payable credit to the supplier's ledger.
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal" wire:click="cancelPostConfirmation">
                        Cancel
                    </button>
                    <button wire:click="postToLedger" type="button" class="btn btn-success btn-sm" data-bs-dismiss="modal" wire:loading.attr="disabled">
                        <span class="fas fa-check-circle me-1"></span>Yes, Post to Ledger
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteConfirmModalLabel">
                        <span class="fas fa-exclamation-triangle me-2"></span>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="mb-0 text-900">Are you sure you want to remove this draft purchase invoice? This action will permanently remove this draft invoice.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="deleteRecord" type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                        <span class="fas fa-trash-alt me-1"></span>Delete Bill
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
