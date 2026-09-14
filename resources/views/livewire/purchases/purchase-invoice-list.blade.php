<div>
    <div class="card border border-200 shadow-none">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><span class="fas fa-file-invoice-dollar me-2 text-primary"></span>Purchase Invoices</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Supplier Filter -->
                <select wire:model.live="filterSupplier" class="form-select form-select-sm" style="max-width: 150px;">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supp)
                        <option value="{{ $supp->id }}">{{ $supp->name }}</option>
                    @endforeach
                </select>

                <!-- Status Filter -->
                <select wire:model.live="filterStatus" class="form-select form-select-sm" style="max-width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="Draft">Draft</option>
                    <option value="Posted">Posted</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

                <!-- Search -->
                <div class="input-group input-group-sm" style="max-width: 180px;">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Invoice Number..." />
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
                <table class="table table-sm table-striped fs-10 mb-0 align-middle">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="px-3" style="width: 150px;">Invoice Number</th>
                            <th>Supplier</th>
                            <th>Purchase Date</th>
                            <th>Reference No</th>
                            <th class="text-end" style="width: 120px;">Net Amount</th>
                            <th>Posted JV</th>
                            <th class="text-center" style="width: 120px;">Status</th>
                            <th class="text-end px-3" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseInvoices as $inv)
                            <tr>
                                <td class="px-3 font-monospace fw-bold"><span class="badge badge-subtle-secondary fs-11">{{ $inv->invoice_number }}</span></td>
                                <td class="fw-semi-bold">{{ $inv->supplier->name ?? '—' }}</td>
                                <td>{{ $inv->purchase_date->format('Y-m-d') }}</td>
                                <td>{{ $inv->reference_number ?: '—' }}</td>
                                <td class="text-end font-monospace fw-bold">Rs. {{ number_format($inv->net_amount, 2) }}</td>
                                <td class="font-monospace">
                                    @if($inv->journalVoucher)
                                        <span class="badge bg-light text-primary"><span class="fas fa-book me-1"></span>{{ $inv->journalVoucher->voucher_no }}</span>
                                    @else
                                        <span class="text-muted">Not Posted</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $statuses = [
                                            'Draft' => 'secondary',
                                            'Posted' => 'success',
                                            'Cancelled' => 'danger'
                                        ];
                                        $sc = $statuses[$inv->status] ?? 'dark';
                                    @endphp
                                    <span class="badge badge-subtle-{{ $sc }} rounded-pill">{{ $inv->status }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        <!-- View / Edit -->
                                        <a href="{{ route('purchase-invoices.edit', $inv->id) }}" class="btn btn-link p-0 text-primary" title="{{ $inv->status === 'Draft' ? 'Edit Invoice' : 'View Details' }}">
                                            <span class="fas fa-{{ $inv->status === 'Draft' ? 'edit' : 'eye' }}"></span>
                                        </a>

                                        <!-- Print -->
                                        <a href="{{ route('purchase-invoices.print', $inv->id) }}" target="_blank" class="btn btn-link p-0 text-secondary" title="Print Invoice">
                                            <span class="fas fa-print"></span>
                                        </a>

                                        <!-- Download PDF -->
                                        <a href="{{ route('purchase-invoices.pdf', $inv->id) }}" target="_blank" class="btn btn-link p-0 text-danger" title="Download PDF">
                                            <span class="fas fa-file-pdf"></span>
                                        </a>

                                        <!-- Share Dropdown -->
                                        <div class="dropdown font-sans-serif d-inline-block">
                                            <button class="btn btn-link p-0 text-info dropdown-toggle dropdown-caret-none" type="button" id="shareInv{{ $inv->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Share Invoice">
                                                <span class="fas fa-share-alt"></span>
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
                                                <span class="fas fa-undo-alt"></span>
                                            </a>
                                        @endif

                                        <!-- Delete (Drafts only) -->
                                        @if($inv->status === 'Draft' && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_inventory')))
                                            <button class="btn btn-link p-0 text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" wire:click="confirmDeletion({{ $inv->id }})" title="Delete">
                                                <span class="fas fa-trash-alt"></span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No purchase invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($purchaseInvoices->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $purchaseInvoices->links() }}
            </div>
        @endif
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
