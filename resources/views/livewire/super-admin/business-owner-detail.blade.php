<div class="p-3">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('super-admin.business-owners') }}" class="btn btn-falcon-default btn-xs">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
                <span class="badge badge-subtle-primary font-monospace">ID: #{{ $businessOwner->id }}</span>
                <h4 class="fw-bold mb-0 text-dark">{{ $businessOwner->name }}</h4>
                @if($businessOwner->status === 'active')
                     <span class="badge badge-subtle-success">Active</span>
                @elseif($businessOwner->status === 'suspended')
                     <span class="badge badge-subtle-danger">Suspended</span>
                @else
                     <span class="badge badge-subtle-secondary">Inactive</span>
                @endif
            </div>
            <div class="text-secondary fs-12 mt-1">
                <i class="fas fa-envelope me-1 text-muted"></i>Email: {{ $businessOwner->email }} | 
                <i class="fas fa-phone me-1 text-muted"></i>Phone: {{ $businessOwner->phone ?: '—' }} | 
                <i class="fas fa-user-shield me-1 text-primary"></i>Role: {{ ucfirst(str_replace('_', ' ', $businessOwner->role->name ?? 'owner')) }}
            </div>
        </div>
        <div>
            <a href="{{ route('super-admin.business-owners.edit', $businessOwner->id) }}" class="btn btn-falcon-primary btn-sm">
                <i class="fas fa-edit me-1"></i> Edit Owner
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs border-bottom mb-3 fs-12">
        <li class="nav-item">
            <button wire:click="setTab('overview')" class="nav-link {{ $activeTab === 'overview' ? 'active fw-bold text-primary' : 'text-secondary' }}">
                <i class="fas fa-info-circle me-1"></i> Overview
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="setTab('businesses')" class="nav-link {{ $activeTab === 'businesses' ? 'active fw-bold text-primary' : 'text-secondary' }}">
                <i class="fas fa-building me-1"></i> Businesses / Marquees ({{ $businessOwner->ownedMarquees->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="setTab('invoices')" class="nav-link {{ $activeTab === 'invoices' ? 'active fw-bold text-primary' : 'text-secondary' }}">
                <i class="fas fa-file-invoice-dollar me-1"></i> SaaS Invoices ({{ $invoices->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="setTab('payments')" class="nav-link {{ $activeTab === 'payments' ? 'active fw-bold text-primary' : 'text-secondary' }}">
                <i class="fas fa-receipt me-1"></i> Payments ({{ $payments->count() }})
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    @if($activeTab === 'overview')
        <div class="row g-3 fs-12">
            <!-- Left Info Panel -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-id-card me-2 text-primary"></i>Profile Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-secondary fw-semibold" style="width: 140px;">Full Name:</td><td class="fw-bold">{{ $businessOwner->name }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Username:</td><td class="fw-semibold">{{ $businessOwner->username }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Email Address:</td><td>{{ $businessOwner->email }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Contact Phone:</td><td>{{ $businessOwner->phone ?: '—' }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Account Status:</td><td>
                                @if($businessOwner->status === 'active')
                                    <span class="badge badge-subtle-success">Active</span>
                                @elseif($businessOwner->status === 'suspended')
                                    <span class="badge badge-subtle-danger">Suspended</span>
                                @else
                                    <span class="badge badge-subtle-secondary">Inactive</span>
                                @endif
                            </td></tr>
                            <tr><td class="text-secondary fw-semibold">Created At:</td><td>{{ $businessOwner->created_at ? $businessOwner->created_at->format('F d, Y h:i A') : 'N/A' }}</td></tr>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2 text-primary"></i>Resource Usage Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center g-2">
                            <div class="col-4">
                                <div class="border rounded p-2 bg-light">
                                    <div class="text-secondary fs-11">Businesses</div>
                                    <div class="fw-extrabold text-dark fs-13">{{ $businessOwner->ownedMarquees->count() }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 bg-light">
                                    <div class="text-secondary fs-11">Total Branches</div>
                                    <div class="fw-extrabold text-success fs-13">
                                        {{ $businessOwner->ownedMarquees->sum(fn($m) => $m->branches->count()) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 bg-light">
                                    <div class="text-secondary fs-11">Team Users</div>
                                    <div class="fw-extrabold text-info fs-13">
                                        {{ $businessOwner->ownedMarquees->sum(fn($m) => $m->users->count()) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Subscription Panel -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-file-contract me-2 text-success"></i>Subscription & Plan Details</h6>
                    </div>
                    <div class="card-body">
                        @if($businessOwner->subscriptionPlan)
                            <div class="p-3 border rounded bg-light mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge badge-subtle-primary fs-11">{{ $businessOwner->subscriptionPlan->name }}</span>
                                    @if($businessOwner->status === 'inactive')
                                        <span class="badge badge-subtle-secondary">Inactive</span>
                                    @elseif($businessOwner->status === 'suspended')
                                        <span class="badge badge-subtle-danger">Suspended</span>
                                    @elseif($businessOwner->subscription_trial_ends_at && $businessOwner->subscription_trial_ends_at->isFuture())
                                        <span class="badge badge-subtle-info">Active Trial</span>
                                    @elseif($businessOwner->subscription_ends_at && $businessOwner->subscription_ends_at->isPast())
                                        <span class="badge badge-subtle-danger">Expired Plan</span>
                                    @elseif($businessOwner->subscription_trial_ends_at && $businessOwner->subscription_trial_ends_at->isPast())
                                        <span class="badge badge-subtle-danger">Expired Trial</span>
                                    @else
                                        <span class="badge badge-subtle-success">Active Subscription</span>
                                    @endif
                                </div>
                                <div class="fs-14 fw-extrabold text-dark mb-1">
                                    Plan Price: {{ number_format($businessOwner->subscriptionPlan->price, 2) }} {{ $businessOwner->subscriptionPlan->currency }}
                                </div>
                                <div class="text-secondary fs-11 mb-2">
                                    @if($businessOwner->subscription_trial_ends_at && $businessOwner->subscription_trial_ends_at->isFuture())
                                        Trial Expires At: {{ $businessOwner->subscription_trial_ends_at->format('F d, Y') }}
                                    @else
                                        Expires At: {{ $businessOwner->subscription_ends_at ? $businessOwner->subscription_ends_at->format('F d, Y') : 'Ongoing / Lifetime' }}
                                    @endif
                                </div>
                                <div class="fs-12 text-secondary">
                                    {{ $businessOwner->subscriptionPlan->description ?: 'No description provided.' }}
                                </div>
                            </div>

                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-secondary fw-semibold" style="width: 160px;">Max Branches Limit:</td>
                                    <td class="fw-bold">{{ $businessOwner->subscriptionPlan->max_branches ?? 'Unlimited' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold">Max Users Limit:</td>
                                    <td class="fw-bold">{{ $businessOwner->subscriptionPlan->max_users ?? 'Unlimited' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-secondary fw-semibold">Storage Limit:</td>
                                    <td class="fw-bold">{{ $businessOwner->subscriptionPlan->storage_limit_mb ? $businessOwner->subscriptionPlan->storage_limit_mb . ' MB' : 'Unlimited' }}</td>
                                </tr>
                                @if($businessOwner->subscriptionPlan->features)
                                    <tr>
                                        <td class="text-secondary fw-semibold valign-top">Included Features:</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($businessOwner->subscriptionPlan->features as $feature)
                                                    <span class="badge badge-subtle-secondary">{{ ucfirst(str_replace('_', ' ', $feature)) }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        @else
                            <div class="text-center py-4 text-muted border rounded">
                                <i class="fas fa-exclamation-triangle text-warning me-1"></i> No active subscription plan configured.
                                <div class="mt-2">
                                    <a href="{{ route('super-admin.business-owners.edit', $businessOwner->id) }}" class="btn btn-falcon-primary btn-xs">Configure Plan Now</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'businesses')
        <div class="card border-0 shadow-sm fs-12">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 fw-bold"><i class="fas fa-building me-2 text-primary"></i>Owned Businesses / Marquees</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped fs-10 mb-0">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="align-middle px-3" style="width: 50px;">No.</th>
                                <th class="align-middle">Business Name</th>
                                <th class="align-middle">Email Address</th>
                                <th class="align-middle">Phone Number</th>
                                <th class="align-middle">Location</th>
                                <th class="align-middle text-center">Status</th>
                                <th class="align-middle text-center">Setup Status</th>
                                <th class="align-middle text-center">Branches</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($businessOwner->ownedMarquees as $marquee)
                                <tr>
                                    <td class="align-middle px-3">{{ $loop->iteration }}</td>
                                    <td class="align-middle fw-bold">{{ $marquee->name }}</td>
                                    <td class="align-middle">{{ $marquee->email }}</td>
                                    <td class="align-middle">{{ $marquee->phone }}</td>
                                    <td class="align-middle">
                                        {{ $marquee->address ? $marquee->address . ', ' : '' }}{{ $marquee->city }}
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge badge-subtle-{{ $marquee->status === 'active' ? 'success' : 'secondary' }} rounded-pill">
                                            {{ ucfirst($marquee->status) }}
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge badge-subtle-{{ $marquee->is_setup_completed ? 'success' : 'warning' }} rounded-pill">
                                            {{ $marquee->is_setup_completed ? 'Completed' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge badge-subtle-info rounded-pill">{{ $marquee->branches->count() }} Branches</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No registered businesses found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'invoices')
        <div class="card border-0 shadow-sm fs-12">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>SaaS Subscriptions Invoices</h6>
            </div>
            <div class="card-body p-0">
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
                                    <td class="align-middle fw-semibold">{{ $businessOwner->name }}</td>
                                    <td class="align-middle">{{ $invoice->subscriptionPlan->name }}</td>
                                    <td class="align-middle">{{ $invoice->billingCycle->cycle_name ?? 'N/A' }}</td>
                                    <td class="align-middle fw-bold text-dark">
                                        {{ number_format($invoice->total_amount, 2) }} {{ $invoice->subscriptionPlan->currency }}
                                    </td>
                                    <td class="align-middle">{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}</td>
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
                                        <a class="btn btn-link p-0" href="{{ route('saas-invoices.show', $invoice->id) }}" data-bs-toggle="tooltip" title="View & Print Invoice">
                                            <span class="text-info fas fa-print"></span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No invoices found for this owner.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'payments')
        <div class="card border-0 shadow-sm fs-12">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 fw-bold"><i class="fas fa-receipt me-2 text-primary"></i>SaaS Payment Transactions</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped fs-10 mb-0">
                        <thead class="bg-200 text-900">
                            <tr>
                                <th class="align-middle px-3">Payment Reference</th>
                                <th class="align-middle">Invoice Number</th>
                                <th class="align-middle">Business Owner</th>
                                <th class="align-middle">Amount</th>
                                <th class="align-middle">Payment Method</th>
                                <th class="align-middle">Transaction ID</th>
                                <th class="align-middle">Payment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td class="align-middle px-3 fw-bold">{{ $payment->payment_reference }}</td>
                                    <td class="align-middle">
                                        @if($payment->invoice)
                                            <a href="{{ route('saas-invoices.show', $payment->invoice_id) }}">
                                                {{ $payment->invoice->invoice_number }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="align-middle fw-semibold">{{ $businessOwner->name }}</td>
                                    <td class="align-middle fw-bold text-success">
                                        {{ number_format($payment->amount, 2) }}
                                    </td>
                                    <td class="align-middle">{{ $payment->payment_method }}</td>
                                    <td class="align-middle font-monospace">{{ $payment->transaction_id ?: '—' }}</td>
                                    <td class="align-middle">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No payments found for this owner.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
