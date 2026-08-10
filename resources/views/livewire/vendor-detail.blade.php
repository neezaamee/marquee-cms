<div class="p-3">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('vendors.index') }}" class="btn btn-falcon-default btn-xs"><i class="fas fa-arrow-left me-1"></i> Back</a>
                <span class="badge badge-subtle-primary font-monospace">{{ $vendor->vendor_code }}</span>
                <h4 class="fw-bold mb-0 text-dark">{{ $vendor->name }}</h4>
                @if($vendor->status === 'active')
                     <span class="badge badge-subtle-success">Active</span>
                @elseif($vendor->status === 'suspended')
                     <span class="badge badge-subtle-warning">Suspended</span>
                @else
                     <span class="badge badge-subtle-secondary">Inactive</span>
                @endif
            </div>
            <div class="text-secondary fs-12 mt-1">
                <i class="fas fa-tag me-1 text-primary"></i>{{ $vendor->vendor_type }} | 
                <i class="fas fa-user me-1 text-muted"></i>Contact: {{ $vendor->contact_person ?: '—' }} | 
                <i class="fas fa-phone me-1 text-muted"></i>{{ $vendor->phone }}
            </div>
        </div>
        <div class="text-end">
            <div class="fs-11 text-uppercase text-secondary fw-bold">Current Net Balance Payable</div>
            <div class="fs-4 fw-extrabold {{ $vendor->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                Rs. {{ number_format($vendor->current_balance) }}
            </div>
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
            <button wire:click="setTab('services')" class="nav-link {{ $activeTab === 'services' ? 'active fw-bold text-primary' : 'text-secondary' }}">
                <i class="fas fa-concierge-bell me-1"></i> Services ({{ $vendor->services->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="setTab('agreements')" class="nav-link {{ $activeTab === 'agreements' ? 'active fw-bold text-primary' : 'text-secondary' }}">
                <i class="fas fa-file-contract me-1"></i> Agreements ({{ $vendor->agreements->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="setTab('sales')" class="nav-link {{ $activeTab === 'sales' ? 'active fw-bold text-primary' : 'text-secondary' }}">
                <i class="fas fa-shopping-cart me-1"></i> Sales ({{ $vendor->sales->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="setTab('ledger')" class="nav-link {{ $activeTab === 'ledger' ? 'active fw-bold text-primary' : 'text-secondary' }}">
                <i class="fas fa-book me-1"></i> Financial Ledger
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="setTab('settlements')" class="nav-link {{ $activeTab === 'settlements' ? 'active fw-bold text-primary' : 'text-secondary' }}">
                <i class="fas fa-wallet me-1"></i> Settlements ({{ $vendor->settlements->count() }})
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
                        <h6 class="mb-0 fw-bold"><i class="fas fa-id-card me-2 text-primary"></i>Provider Contact & Tax Profile</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-secondary fw-semibold" style="width: 140px;">Primary Phone:</td><td class="fw-bold font-monospace">{{ $vendor->phone }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Alternate / WhatsApp:</td><td>{{ $vendor->alternate_phone ?: '—' }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Email:</td><td>{{ $vendor->email ?: '—' }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Address / City:</td><td>{{ $vendor->address ? $vendor->address . ', ' . $vendor->city : ($vendor->city ?: '—') }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Tax NTN / STRN:</td><td>{{ $vendor->tax_ntn ?: '—' }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Payment Terms:</td><td><span class="badge badge-subtle-secondary">{{ $vendor->payment_terms }}</span></td></tr>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-university me-2 text-primary"></i>Banking & Payment Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-secondary fw-semibold" style="width: 140px;">Bank Name:</td><td class="fw-bold">{{ $vendor->bank_name ?: '—' }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Account Title:</td><td class="fw-bold">{{ $vendor->account_title ?: '—' }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Account # / IBAN:</td><td class="font-monospace fw-bold text-primary">{{ $vendor->account_number_iban ?: '—' }}</td></tr>
                            <tr><td class="text-secondary fw-semibold">Opening Balance:</td><td>Rs. {{ number_format($vendor->opening_balance) }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Active Agreement & Performance Summary -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-file-contract me-2 text-success"></i>Active Commission Contract</h6>
                    </div>
                    <div class="card-body">
                        @if($activeAgreement)
                            <div class="p-3 border rounded bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge badge-subtle-primary font-monospace">{{ $activeAgreement->agreement_number }}</span>
                                    <span class="badge badge-subtle-success">Active Contract</span>
                                </div>
                                <div class="fs-14 fw-extrabold text-dark mb-1">
                                    Model: {{ strtoupper(str_replace('_', ' ', $activeAgreement->commission_type)) }}
                                </div>
                                <div class="fs-13 fw-bold text-success mb-2">
                                    @if($activeAgreement->commission_type === 'percentage')
                                        Commission Rate: {{ $activeAgreement->commission_percentage }}% on all sales
                                    @elseif($activeAgreement->commission_type === 'fixed_per_event')
                                        Fixed Fee: Rs. {{ number_format($activeAgreement->fixed_commission_amount) }} per event
                                    @elseif($activeAgreement->commission_type === 'fixed_monthly')
                                        Fixed Monthly Retainer: Rs. {{ number_format($activeAgreement->monthly_fixed_amount) }}
                                    @elseif($activeAgreement->commission_type === 'hybrid')
                                        {{ $activeAgreement->commission_percentage }}% + Rs. {{ number_format($activeAgreement->fixed_commission_amount) }} (Min Rs. {{ number_format($activeAgreement->minimum_commission) }})
                                    @endif
                                </div>
                                <div class="text-secondary fs-11">
                                    Effective: {{ $activeAgreement->effective_from->format('d-M-Y') }} → {{ $activeAgreement->effective_to ? $activeAgreement->effective_to->format('d-M-Y') : 'Ongoing' }}
                                </div>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted border rounded">
                                <i class="fas fa-exclamation-triangle text-warning me-1"></i> No active commission agreement configured for this date.
                                <div class="mt-2">
                                    <button wire:click="setTab('agreements')" class="btn btn-falcon-primary btn-xs">Configure Agreement Now</button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i>Partnership Performance Metrics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center g-2">
                            <div class="col-4">
                                <div class="border rounded p-2 bg-light">
                                    <div class="text-secondary fs-11">Total Sales</div>
                                    <div class="fw-extrabold text-dark fs-13">Rs. {{ number_format($vendor->total_sales) }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 bg-light">
                                    <div class="text-secondary fs-11">Commission Income</div>
                                    <div class="fw-extrabold text-success fs-13">Rs. {{ number_format($vendor->total_commission) }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-2 bg-light">
                                    <div class="text-secondary fs-11">Total Settled</div>
                                    <div class="fw-extrabold text-info fs-13">
                                        Rs. {{ number_format($vendor->settlements->where('status', 'fully_settled')->sum('paid_amount')) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'services')
        <livewire:vendor-service-manager :vendor="$vendor" key="vendor-services-{{ $vendor->id }}" />
    @elseif($activeTab === 'agreements')
        <livewire:vendor-agreement-manager :vendor="$vendor" key="vendor-agreements-{{ $vendor->id }}" />
    @elseif($activeTab === 'sales')
        <livewire:vendor-sale-manager :vendor="$vendor" key="vendor-sales-{{ $vendor->id }}" />
    @elseif($activeTab === 'ledger')
        <livewire:vendor-ledger-view :vendor="$vendor" key="vendor-ledger-{{ $vendor->id }}" />
    @elseif($activeTab === 'settlements')
        <livewire:vendor-settlement-manager :vendor="$vendor" key="vendor-settlements-{{ $vendor->id }}" />
    @endif
</div>
