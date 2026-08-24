<div class="container py-4">
    <!-- Header Page Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="mb-1 text-primary fw-bold">Billing & Subscriptions</h2>
            <p class="text-secondary fs-12 mb-0">Manage your SaaS subscription plan, review invoices, and process secure payments online.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="badge bg-light text-dark border p-2 fs-12">
                <span class="fas fa-building me-1"></span>Company: <strong>{{ $marquee->name ?? 'N/A' }}</strong>
            </div>
        </div>
    </div>

    <!-- Feedback Message System -->
    @if(session()->has('error'))
        <div class="alert alert-danger border-2 d-flex align-items-center" role="alert">
            <span class="fas fa-exclamation-triangle me-2 fs-8"></span>
            <div>{{ session('error') }}</div>
        </div>
    @endif
    @if(session()->has('success'))
        <div class="alert alert-success border-2 d-flex align-items-center" role="alert">
            <span class="fas fa-check-circle me-2 fs-8"></span>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if($marquee)
    <div class="row g-4">
        <!-- Plan Overview Card (Glassmorphism layout) -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 bg-primary text-white mb-4" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;">
                <div class="card-body p-4">
                    @if(auth()->user()->status === 'inactive')
                        <span class="badge bg-white text-secondary text-uppercase fw-bold fs-11 px-2 py-1 mb-3">Inactive</span>
                    @elseif(auth()->user()->status === 'suspended')
                        <span class="badge bg-white text-danger text-uppercase fw-bold fs-11 px-2 py-1 mb-3">Suspended</span>
                    @elseif(auth()->user()->subscription_trial_ends_at && auth()->user()->subscription_trial_ends_at->isFuture())
                        <span class="badge bg-white text-info text-uppercase fw-bold fs-11 px-2 py-1 mb-3">Active Trial</span>
                    @elseif(auth()->user()->subscription_ends_at && auth()->user()->subscription_ends_at->isPast())
                        <span class="badge bg-white text-warning text-uppercase fw-bold fs-11 px-2 py-1 mb-3">Expired</span>
                    @elseif(auth()->user()->subscription_trial_ends_at && auth()->user()->subscription_trial_ends_at->isPast())
                        <span class="badge bg-white text-danger text-uppercase fw-bold fs-11 px-2 py-1 mb-3">Expired Trial</span>
                    @else
                        <span class="badge bg-white text-primary text-uppercase fw-bold fs-11 px-2 py-1 mb-3">Active Subscription</span>
                    @endif
                    <h3 class="fw-bold mb-1">{{ auth()->user()->subscriptionPlan->name ?? 'Trial Plan' }}</h3>
                    <p class="fs-12 text-white-50 mb-3">{{ auth()->user()->subscriptionPlan->description ?? 'Initial trial configuration setup.' }}</p>
                    
                    <button wire:click="openChangePlanModal" class="btn btn-light btn-xs text-primary fw-bold px-3 mb-4">
                        <span class="fas fa-edit me-1"></span>Change / Upgrade Plan
                    </button>
                    
                    <hr class="border-white-50" />

                    <div class="d-flex justify-content-between align-items-center mb-2 fs-12">
                        <span class="text-white-50">Monthly Rate:</span>
                        <span class="fw-bold font-monospace">
                            {{ number_format(auth()->user()->subscriptionPlan->monthly_price ?? 0, 2) }} {{ auth()->user()->subscriptionPlan->currency ?? 'PKR' }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 fs-12">
                        @if(auth()->user()->subscription_trial_ends_at && auth()->user()->subscription_trial_ends_at->isFuture())
                            <span class="text-white-50">Trial Ends:</span>
                            <span class="fw-bold">
                                {{ auth()->user()->subscription_trial_ends_at->format('M d, Y') }}
                            </span>
                        @else
                            <span class="text-white-50">Subscription Ends:</span>
                            <span class="fw-bold">
                                @if(auth()->user()->subscription_ends_at)
                                    {{ auth()->user()->subscription_ends_at->format('M d, Y') }}
                                @else
                                    Never / Unlimited
                                @endif
                            </span>
                        @endif
                    </div>

                    @if(auth()->user()->subscription_ends_at && auth()->user()->subscription_ends_at->isPast())
                        <div class="mt-3 p-2 bg-danger text-white rounded text-center fw-bold fs-11">
                            <span class="fas fa-exclamation-circle me-1"></span>Your subscription has expired!
                        </div>
                    @elseif(auth()->user()->subscription_trial_ends_at && auth()->user()->subscription_trial_ends_at->isPast() && (!auth()->user()->subscription_ends_at || auth()->user()->subscription_ends_at->isPast()))
                        <div class="mt-3 p-2 bg-danger text-white rounded text-center fw-bold fs-11">
                            <span class="fas fa-exclamation-circle me-1"></span>Your trial period has expired!
                        </div>
                    @endif
                </div>
            </div>

            <!-- Resource Limits Card -->
            <div class="card shadow-sm border">
                <div class="card-header bg-light py-3">
                    <h6 class="mb-0 text-dark fw-bold"><span class="fas fa-sliders-h me-2 text-primary"></span>Resource Allocation limits</h6>
                </div>
                <div class="card-body py-2">
                    <table class="table table-sm table-borderless fs-12 mb-0">
                        <tr class="border-bottom">
                            <td class="text-secondary py-2">Max Branches:</td>
                            <td class="text-dark fw-bold text-end py-2">
                                {{ $marquee ? $marquee->branches->count() : 0 }} / {{ auth()->user()->subscriptionPlan->max_branches ?? 'Unlimited' }}
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-secondary py-2">Max User Accounts:</td>
                            <td class="text-dark fw-bold text-end py-2">
                                {{ $marquee ? $marquee->users->count() : 0 }} / {{ auth()->user()->subscriptionPlan->max_users ?? 'Unlimited' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary py-2">Max Disk Storage:</td>
                            <td class="text-dark fw-bold text-end py-2">
                                {{ auth()->user()->subscriptionPlan->storage_limit_mb ?? '512' }} MB
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Invoice Details List -->
        <div class="col-lg-8">
            <div class="card shadow-sm border">
                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-dark fw-bold"><span class="fas fa-file-invoice-dollar me-2 text-primary"></span>Billing Invoices & Payments History</h6>
                    <span class="badge badge-subtle-primary fs-11">Total: {{ count($invoices) }} Invoices</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle fs-12 mb-0">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-3" style="width: 140px;">Invoice No</th>
                                    <th>Billing Term</th>
                                    <th class="text-center" style="width: 130px;">Amount</th>
                                    <th class="text-center" style="width: 120px;">Payment Status</th>
                                    <th class="text-center" style="width: 120px;">Due Date</th>
                                    <th class="text-end pe-3" style="width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    @php
                                        $currency = $invoice->subscriptionPlan->currency ?? 'PKR';
                                        $paidAmt = $invoice->payments->sum('amount');
                                        $due = max(0, $invoice->total_amount - $paidAmt);
                                    @endphp
                                    <tr>
                                        <td class="ps-3 font-monospace fw-bold">
                                            <a href="{{ route('saas-invoices.show', $invoice->id) }}" class="text-primary text-decoration-none">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $invoice->subscriptionPlan->name }}</div>
                                            <div class="text-muted fs-11">{{ $invoice->billingCycle->cycle_name ?? 'Monthly' }} ({{ $invoice->billingCycle->duration_in_months ?? 1 }} months)</div>
                                        </td>
                                        <td class="text-center font-monospace fw-bold text-dark">
                                            {{ number_format($invoice->total_amount, 2) }} {{ $currency }}
                                        </td>
                                        <td class="text-center">
                                            @if($invoice->payment_status === 'Paid')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Paid</span>
                                            @elseif($invoice->payment_status === 'Partially Paid')
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Partial</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Unpaid</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="text-secondary">{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '—' }}</span>
                                        </td>
                                        <td class="text-end pe-3">
                                            @if($invoice->payment_status !== 'Paid' && $invoice->invoice_status !== 'Cancelled')
                                                <button 
                                                    wire:click="checkout({{ $invoice->id }})" 
                                                    wire:loading.attr="disabled"
                                                    class="btn btn-primary btn-xs px-3 shadow-none text-900 border-0"
                                                    style="background: linear-gradient(135deg, #605dec 0%, #4643c1 100%) !important; color: white !important;"
                                                >
                                                    <span class="fab fa-stripe me-1"></span> Pay Online
                                                </button>
                                            @else
                                                <a href="{{ route('saas-invoices.show', $invoice->id) }}" class="btn btn-light border btn-xs px-3 text-secondary">
                                                    <span class="fas fa-eye me-1"></span> View
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <span class="fas fa-folder-open fa-2x d-block mb-2 text-secondary"></span>
                                            No SaaS subscription invoices recorded for your account.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="card shadow-sm border border-2 border-info-subtle p-4 mt-3">
            <div class="card-body text-center">
                <span class="fas fa-info-circle fa-4x text-info mb-3"></span>
                <h4 class="fw-bold text-dark">No Associated Marquee Tenant Account</h4>
                <p class="text-secondary fs-12 mb-0">
                    Your administrator user account is not linked to any active Marquee company tenant. Super Admin logins are exempt from standard billing limits and plan constraints.
                </p>
            </div>
        </div>
    @endif

    <!-- Change Plan Modal -->
    @if($showChangePlanModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title fw-bold text-white"><span class="fas fa-sync-alt me-2"></span>Upgrade or Change Plan</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="$set('showChangePlanModal', false)"></button>
                    </div>
                    <form wire:submit.prevent="changePlan">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semi-bold text-700" for="selectedPlanId">Select Subscription Plan</label>
                                <select wire:model.live="selectedPlanId" class="form-select" id="selectedPlanId" required>
                                    @foreach($plans as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semi-bold text-700" for="selectedCycleId">Select Billing Term</label>
                                <select wire:model.live="selectedCycleId" class="form-select @error('selectedCycleId') is-invalid @enderror" id="selectedCycleId" required>
                                    <option value="">-- Choose Billing Cycle --</option>
                                    @foreach($cycles as $c)
                                        <option value="{{ $c->id }}">{{ $c->cycle_name }} ({{ $c->duration_in_months }} months @if($c->discount_percentage > 0) -{{ $c->discount_percentage }}% off @endif)</option>
                                    @endforeach
                                </select>
                                @error('selectedCycleId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if($selectedPlanId && $selectedCycleId)
                                <div class="p-3 border rounded bg-light">
                                    <h6 class="mb-3 text-primary fw-bold fs-12"><span class="fas fa-calculator me-2"></span>Proration Cost Calculation</h6>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-2 fs-11">
                                        <span class="text-secondary">Unused Subscription Credit:</span>
                                        <span class="fw-bold font-monospace text-success">- PKR {{ number_format($unusedCredit, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2 fs-11">
                                        <span class="text-secondary">Existing Credit Balance:</span>
                                        <span class="fw-bold font-monospace text-success">- PKR {{ number_format(auth()->user()->credit_balance, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2 fs-11">
                                        <span class="text-secondary">New Plan Price:</span>
                                        <span class="fw-bold font-monospace text-dark">PKR {{ number_format($newPlanCharge, 2) }}</span>
                                    </div>
                                    
                                    <hr class="my-2" />

                                    <div class="d-flex justify-content-between align-items-center mb-2 fs-12 fw-bold text-dark">
                                        <span>Net Amount Due:</span>
                                        <span class="font-monospace text-primary">PKR {{ number_format($netPayable, 2) }}</span>
                                    </div>

                                    @if($newCreditBalance > 0)
                                        <div class="d-flex justify-content-between align-items-center mt-2 fs-11 text-success fw-semi-bold">
                                            <span>Remaining Credit Balance:</span>
                                            <span class="font-monospace">PKR {{ number_format($newCreditBalance, 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer bg-light py-3">
                            <button type="button" class="btn btn-secondary btn-sm px-4" wire:click="$set('showChangePlanModal', false)">Close</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4" @if(!$selectedPlanId || !$selectedCycleId) disabled @endif>Confirm Plan Change</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
