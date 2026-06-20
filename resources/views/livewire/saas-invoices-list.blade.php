<div>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">SaaS Invoices</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm w-auto">
                    <input wire:model.live.debounce.300ms="search" class="form-control" type="search" placeholder="Search invoices..." />
                    <span class="input-group-text"><span class="fas fa-search"></span></span>
                </div>
                
                <select wire:model.live="filterPaymentStatus" class="form-select form-select-sm w-auto">
                    <option value="">All Payment Statuses</option>
                    <option value="Unpaid">Unpaid</option>
                    <option value="Partially Paid">Partially Paid</option>
                    <option value="Paid">Paid</option>
                    <option value="Refunded">Refunded</option>
                </select>

                <select wire:model.live="filterInvoiceStatus" class="form-select form-select-sm w-auto">
                    <option value="">All Invoice Statuses</option>
                    <option value="Draft">Draft</option>
                    <option value="Pending">Pending</option>
                    <option value="Paid">Paid</option>
                    <option value="Overdue">Overdue</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

                <button class="btn btn-falcon-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#generateInvoiceModal">
                    <span class="fas fa-plus me-1" data-fa-transform="shrink-3"></span> Generate Manual Invoice
                </button>
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

            <div class="table-responsive scrollbar">
                <table class="table table-sm table-striped fs-10 mb-0">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th class="align-middle px-3">Invoice Number</th>
                            <th class="align-middle">Marquee</th>
                            <th class="align-middle">Plan</th>
                            <th class="align-middle">Billing Cycle</th>
                            <th class="align-middle">Total Amount</th>
                            <th class="align-middle">Due Date</th>
                            <th class="align-middle text-center">Payment Status</th>
                            <th class="align-middle text-center">Invoice Status</th>
                            <th class="align-middle text-end px-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td class="align-middle px-3 fw-semi-bold">
                                    <a href="{{ route('saas-invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td class="align-middle fw-semi-bold">{{ $invoice->marquee->name }}</td>
                                <td class="align-middle">{{ $invoice->subscriptionPlan->name }}</td>
                                <td class="align-middle">{{ $invoice->billingCycle->cycle_name }}</td>
                                <td class="align-middle fw-bold text-dark">{{ number_format($invoice->total_amount, 2) }} {{ $invoice->subscriptionPlan->currency }}</td>
                                <td class="align-middle">{{ $invoice->due_date->format('M d, Y') }}</td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-subtle-{{ 
                                        $invoice->payment_status === 'Paid' ? 'success' : 
                                        ($invoice->payment_status === 'Partially Paid' ? 'warning' : 'danger')
                                    }} rounded-pill">
                                        {{ $invoice->payment_status }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-subtle-{{ 
                                        $invoice->invoice_status === 'Paid' ? 'success' : 
                                        ($invoice->invoice_status === 'Pending' ? 'primary' : 
                                        ($invoice->invoice_status === 'Overdue' ? 'danger' : 'secondary'))
                                    }} rounded-pill">
                                        {{ $invoice->invoice_status }}
                                    </span>
                                </td>
                                <td class="align-middle text-end px-3">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a class="btn btn-link p-0" href="{{ route('saas-invoices.show', $invoice->id) }}" data-bs-toggle="tooltip" title="View & Print Invoice">
                                            <span class="text-info fas fa-print"></span>
                                        </a>
                                        
                                        @if(in_array($invoice->invoice_status, ['Pending', 'Draft']))
                                            <button class="btn btn-link p-0 dropdown-toggle dropdown-caret-none" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <span class="text-primary fas fa-ellipsis-v"></span>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end py-1">
                                                <button wire:click="updateStatus({{ $invoice->id }}, 'Overdue')" class="dropdown-item py-1 text-danger">Mark Overdue</button>
                                                <button wire:click="updateStatus({{ $invoice->id }}, 'Cancelled')" class="dropdown-item py-1 text-muted">Cancel Invoice</button>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($invoices->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-center bg-light">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    <!-- Generate Manual Invoice Modal -->
    <div wire:ignore.self class="modal fade" id="generateInvoiceModal" tabindex="-1" aria-labelledby="generateInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="generateInvoiceModalLabel">
                        <span class="fas fa-file-invoice-dollar me-2"></span>Generate Manual SaaS Invoice
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="createInvoice">
                    <div class="modal-body text-start">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="new_marquee_id">Select Marquee Tenant <span class="text-danger">*</span></label>
                                <select wire:model="new_marquee_id" class="form-select @error('new_marquee_id') is-invalid @enderror" id="new_marquee_id">
                                    <option value="">-- Choose Marquee --</option>
                                    @foreach($marquees as $marquee)
                                        <option value="{{ $marquee->id }}">{{ $marquee->name }}</option>
                                    @endforeach
                                </select>
                                @error('new_marquee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="new_plan_id">Select Plan <span class="text-danger">*</span></label>
                                <select wire:model="new_plan_id" class="form-select @error('new_plan_id') is-invalid @enderror" id="new_plan_id">
                                    <option value="">-- Choose Plan --</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                    @endforeach
                                </select>
                                @error('new_plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="new_billing_cycle_id">Billing Cycle <span class="text-danger">*</span></label>
                                <select wire:model="new_billing_cycle_id" class="form-select @error('new_billing_cycle_id') is-invalid @enderror" id="new_billing_cycle_id">
                                    <option value="">-- Choose Cycle --</option>
                                    @foreach($billingCycles as $cycle)
                                        <option value="{{ $cycle->id }}">{{ $cycle->cycle_name }}</option>
                                    @endforeach
                                </select>
                                @error('new_billing_cycle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="new_due_date">Due Date <span class="text-danger">*</span></label>
                                <input wire:model="new_due_date" type="date" class="form-control @error('new_due_date') is-invalid @enderror" id="new_due_date" />
                                @error('new_due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="new_notes">Invoice Notes / Description</label>
                                <textarea wire:model="new_notes" class="form-control" id="new_notes" rows="2" placeholder="e.g. Renewal invoice..."></textarea>
                            </div>

                            <!-- Pricing Calculations Preview -->
                            @if($calc_total > 0)
                                <div class="col-12 bg-light p-3 rounded">
                                    <h6 class="mb-2 text-primary">Invoice Preview</h6>
                                    <div class="d-flex justify-content-between fs-11 text-700">
                                        <span>Base Price:</span>
                                        <span>{{ number_format($calc_base_amount, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between fs-11 text-success">
                                        <span>Discount:</span>
                                        <span>-{{ number_format($calc_discount, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between fs-11 text-700 border-bottom pb-1">
                                        <span>Tax (0%):</span>
                                        <span>{{ number_format($calc_tax, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between fs-10 fw-bold text-dark pt-1">
                                        <span>Total Amount:</span>
                                        <span>{{ number_format($calc_total, 2) }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-falcon-default btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <span class="fas fa-file-invoice-dollar me-1"></span> Generate Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('close-modal', () => {
                const modalEl = document.getElementById('generateInvoiceModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                }
            });
        });
    </script>
</div>
