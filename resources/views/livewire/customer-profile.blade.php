<div>
    <!-- Profile Header -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row justify-content-between align-items-center">
                <div class="col-md-auto d-flex align-items-center gap-3">
                    @if($customer->profile_photo)
                        <img src="{{ asset('storage/' . $customer->profile_photo) }}" alt="{{ $customer->full_name }}" class="rounded-circle border border-2 border-primary" width="80" height="80" style="object-fit:cover;">
                    @else
                        <div class="avatar avatar-4xl" style="width:80px;height:80px;background:var(--falcon-200);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                            <span class="fas fa-user text-500 fs-3"></span>
                        </div>
                    @endif
                    <div>
                        <h4 class="mb-1 text-900">{{ $customer->full_name }}</h4>
                        <p class="mb-0 fs-10 text-600">
                            <span class="badge badge-subtle-secondary font-monospace fs-11 me-2">{{ $customer->customer_code }}</span>
                            <span class="badge badge-subtle-{{ $customer->customer_type === 'Individual' ? 'info' : 'warning' }} me-2">{{ $customer->customer_type }}</span>
                            @if($customer->company_name)
                                <span class="text-700 fw-semi-bold me-2"><span class="fas fa-building me-1"></span>{{ $customer->company_name }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="col-md-auto mt-3 mt-md-0 d-flex gap-2">
                    @php
                        $statusColors = ['Active' => 'success', 'Inactive' => 'secondary', 'Blocked' => 'danger'];
                        $sc = $statusColors[$customer->status] ?? 'secondary';
                    @endphp
                    <span class="badge badge-subtle-{{ $sc }} fs-10 px-3 py-2 rounded-pill align-self-center">{{ $customer->status }}</span>
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('edit_bookings'))
                        <a class="btn btn-falcon-primary btn-sm align-self-center" href="{{ route('customers.edit', $customer->id) }}">
                            <span class="fas fa-edit me-1"></span>Edit Profile
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Statistics Counter Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-4 col-xxl-2">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-subtle-primary text-primary rounded-circle icon-item me-3"><span class="fas fa-calendar-alt"></span></div>
                    <div>
                        <h5 class="mb-1 text-900">{{ $customer->total_bookings }}</h5>
                        <p class="fs-11 mb-0 text-600 fw-semi-bold">Total Bookings</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xxl-2">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-subtle-warning text-warning rounded-circle icon-item me-3"><span class="fas fa-clock"></span></div>
                    <div>
                        <h5 class="mb-1 text-900">{{ $customer->upcoming_events }}</h5>
                        <p class="fs-11 mb-0 text-600 fw-semi-bold">Upcoming Events</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xxl-2">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-subtle-success text-success rounded-circle icon-item me-3"><span class="fas fa-check-circle"></span></div>
                    <div>
                        <h5 class="mb-1 text-900">{{ $customer->completed_events }}</h5>
                        <p class="fs-11 mb-0 text-600 fw-semi-bold">Completed Events</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-subtle-danger text-danger rounded-circle icon-item me-3"><span class="fas fa-times-circle"></span></div>
                    <div>
                        <h5 class="mb-1 text-900">{{ $customer->cancelled_events }}</h5>
                        <p class="fs-11 mb-0 text-600 fw-semi-bold">Cancelled Events</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xxl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-subtle-info text-info rounded-circle icon-item me-3"><span class="fas fa-money-bill-wave"></span></div>
                    <div>
                        <h5 class="mb-1 text-900">PKR {{ number_format($customer->total_revenue_generated, 2) }}</h5>
                        <p class="fs-11 mb-0 text-600 fw-semi-bold">Revenue Generated</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light p-0">
                    <ul class="nav nav-tabs border-0" role="tablist">
                        <li class="nav-item">
                            <button wire:click="setTab('overview')" class="nav-link px-3 py-2.5 border-0 rounded-0 {{ $activeTab === 'overview' ? 'active text-primary fw-semi-bold border-bottom-2' : 'text-500' }}" type="button">
                                <span class="fas fa-info-circle me-2"></span>Overview
                            </button>
                        </li>
                        <li class="nav-item">
                            <button wire:click="setTab('financials')" class="nav-link px-3 py-2.5 border-0 rounded-0 {{ $activeTab === 'financials' ? 'active text-primary fw-semi-bold border-bottom-2' : 'text-500' }}" type="button">
                                <span class="fas fa-wallet me-2"></span>Outstanding Balance
                            </button>
                        </li>
                        <li class="nav-item">
                            <button wire:click="setTab('documents')" class="nav-link px-3 py-2.5 border-0 rounded-0 {{ $activeTab === 'documents' ? 'active text-primary fw-semi-bold border-bottom-2' : 'text-500' }}" type="button">
                                <span class="fas fa-file-alt me-2"></span>Documents
                            </button>
                        </li>
                        <li class="nav-item">
                            <button wire:click="setTab('crm')" class="nav-link px-3 py-2.5 border-0 rounded-0 {{ $activeTab === 'crm' ? 'active text-primary fw-semi-bold border-bottom-2' : 'text-500' }}" type="button">
                                <span class="fas fa-comments me-2"></span>CRM Log Timeline
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body tab-content">
                    
                    <!-- Overview Tab -->
                    @if($activeTab === 'overview')
                        <div class="tab-pane fade show active">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h5 class="mb-3 text-800"><span class="fas fa-id-card me-2 text-primary"></span>Personal Profile</h5>
                                    <table class="table table-sm table-borderless fs-10 mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semi-bold text-600" style="width:150px">First Name:</td>
                                                <td class="text-800">{{ $customer->first_name }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">Last Name:</td>
                                                <td class="text-800">{{ $customer->last_name }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">Gender:</td>
                                                <td class="text-800">{{ $customer->gender ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">Date of Birth:</td>
                                                <td class="text-800">{{ $customer->date_of_birth ? date('d-M-Y', strtotime($customer->date_of_birth)) : '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">CNIC / Nat. ID:</td>
                                                <td class="text-800 font-monospace">{{ $customer->cnic_national_id ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">NTN Number:</td>
                                                <td class="text-800">{{ $customer->ntn_number ?? '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h5 class="mb-3 text-800"><span class="fas fa-envelope-open-text me-2 text-primary"></span>Contact Details</h5>
                                    <table class="table table-sm table-borderless fs-10 mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semi-bold text-600" style="width:150px">Email Address:</td>
                                                <td class="text-800">{{ $customer->email ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">Phone Number:</td>
                                                <td class="text-800">{{ $customer->phone_number }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">Alternate Phone:</td>
                                                <td class="text-800">{{ $customer->alternate_phone ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">Address:</td>
                                                <td class="text-800">{{ $customer->address ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">City / Province:</td>
                                                <td class="text-800">{{ trim("{$customer->city}, {$customer->province}", " ,") ?: '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">Postal Code:</td>
                                                <td class="text-800">{{ $customer->postal_code ?? '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-md-6 border-top pt-4">
                                    <h5 class="mb-3 text-800"><span class="fas fa-bullhorn me-2 text-primary"></span>Referral Information</h5>
                                    <table class="table table-sm table-borderless fs-10 mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-semi-bold text-600" style="width:150px">Referral Source:</td>
                                                <td class="text-800"><span class="badge badge-subtle-secondary">{{ $customer->referred_by_type ?? 'Walk-In' }}</span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">Referrer Name:</td>
                                                <td class="text-800">{{ $customer->referred_by_name ?: '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semi-bold text-600">Referrer Contact:</td>
                                                <td class="text-800">{{ $customer->referred_by_contact ?: '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-md-6 border-top pt-4">
                                    <h5 class="mb-3 text-800"><span class="fas fa-sticky-note me-2 text-primary"></span>Internal Notes</h5>
                                    <div class="bg-light p-3 rounded text-800 fs-10" style="min-height: 80px;">
                                        {!! nl2br(e($customer->notes ?: 'No notes recorded for this customer.')) !!}
                                    </div>
                                    <div class="fs-11 text-500 mt-2">
                                        Registered by: {{ $customer->creator->name ?? 'System' }} on {{ $customer->created_at->format('d-M-Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Financials Tab -->
                    @if($activeTab === 'financials')
                        <div class="tab-pane fade show active">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="bg-subtle-info text-info p-3 rounded border border-info-subtle">
                                        <h4 class="mb-1 text-info">PKR {{ number_format($customer->total_invoiced_amount, 2) }}</h4>
                                        <p class="mb-0 fs-11 fw-semi-bold">Total Invoiced Amount</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-subtle-success text-success p-3 rounded border border-success-subtle">
                                        <h4 class="mb-1 text-success">PKR {{ number_format($customer->total_paid_amount, 2) }}</h4>
                                        <p class="mb-0 fs-11 fw-semi-bold">Total Paid Amount</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-subtle-danger text-danger p-3 rounded border border-danger-subtle">
                                        <h4 class="mb-1 text-danger">PKR {{ number_format($customer->outstanding_balance, 2) }}</h4>
                                        <p class="mb-0 fs-11 fw-semi-bold">Outstanding Balance</p>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mb-3 text-800"><span class="fas fa-file-invoice-dollar me-2 text-primary"></span>Invoices & Billing History</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped fs-10 align-middle">
                                    <thead class="bg-200">
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Booking Date</th>
                                            <th>Hall / Event</th>
                                            <th>Invoiced Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <span class="fas fa-file-invoice fa-2x mb-2 d-block text-400"></span>
                                                Billing integration will appear here once the booking and finance engines are active.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Documents Tab -->
                    @if($activeTab === 'documents')
                        <div class="tab-pane fade show active">
                            @if(session('success_doc'))
                                <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
                                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                                    <p class="mb-0 flex-1">{{ session('success_doc') }}</p>
                                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="row g-4">
                                <!-- Upload Form -->
                                <div class="col-md-4">
                                    <h5 class="mb-3 text-800"><span class="fas fa-upload me-2 text-primary"></span>Upload Document</h5>
                                    <form wire:submit.prevent="uploadDocument" class="bg-light p-3 rounded border">
                                        <div class="mb-3">
                                            <label class="form-label" for="document_name">Document Name *</label>
                                            <input wire:model="document_name" type="text" class="form-control form-control-sm @error('document_name') is-invalid @enderror" id="document_name" required placeholder="e.g. CNIC Front Side">
                                            @error('document_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="document_type">Document Type *</label>
                                            <select wire:model="document_type" class="form-select form-select-sm @error('document_type') is-invalid @enderror" id="document_type" required>
                                                @foreach($documentTypes as $type)
                                                    <option value="{{ $type }}">{{ $type }}</option>
                                                @endforeach
                                            </select>
                                            @error('document_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="document_file">Choose File *</label>
                                            <input wire:model="document_file" type="file" class="form-control form-control-sm @error('document_file') is-invalid @enderror" id="document_file" required>
                                            @error('document_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            <div wire:loading wire:target="document_file" class="text-primary fs-11 mt-1">
                                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>Uploading file...
                                            </div>
                                        </div>

                                        <button class="btn btn-primary btn-sm w-100" type="submit">
                                            <span wire:loading wire:target="uploadDocument" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                            <span class="fas fa-file-upload me-1"></span>Upload
                                        </button>
                                    </form>
                                </div>

                                <!-- Documents List -->
                                <div class="col-md-8">
                                    <h5 class="mb-3 text-800"><span class="fas fa-folder-open me-2 text-primary"></span>Customer Document Archives</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped fs-10 align-middle">
                                            <thead class="bg-200">
                                                <tr>
                                                    <th>Document Name</th>
                                                    <th>Type</th>
                                                    <th>Size</th>
                                                    <th>Uploaded By</th>
                                                    <th>Uploaded At</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($documents as $doc)
                                                    <tr>
                                                        <td class="fw-semi-bold">
                                                            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank">
                                                                <span class="far fa-file-alt me-1 text-primary"></span>{{ $doc->document_name }}
                                                            </a>
                                                        </td>
                                                        <td><span class="badge badge-subtle-secondary">{{ $doc->document_type }}</span></td>
                                                        <td>{{ number_format($doc->file_size / 1024, 1) }} KB</td>
                                                        <td>{{ $doc->uploader->name ?? 'System' }}</td>
                                                        <td>{{ $doc->created_at->format('d-M-Y H:i') }}</td>
                                                        <td class="text-end">
                                                            <div class="d-flex justify-content-end gap-2">
                                                                <a class="btn btn-link p-0 text-primary" href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" title="View Document">
                                                                    <span class="fas fa-eye"></span>
                                                                </a>
                                                                <button class="btn btn-link p-0 text-danger" wire:click="deleteDocument({{ $doc->id }})" title="Delete Document">
                                                                    <span class="fas fa-trash-alt"></span>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center py-5 text-muted">
                                                            <span class="far fa-file-alt fa-2x d-block mb-2 text-300"></span>
                                                            No documents uploaded yet.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- CRM Log Timeline Tab -->
                    @if($activeTab === 'crm')
                        <div class="tab-pane fade show active">
                            @if(session('success_crm'))
                                <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
                                    <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
                                    <p class="mb-0 flex-1">{{ session('success_crm') }}</p>
                                    <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="row g-4">
                                <!-- Create CRM Log Form -->
                                <div class="col-md-4">
                                    <h5 class="mb-3 text-800"><span class="fas fa-pen-nib me-2 text-primary"></span>Log Interaction</h5>
                                    <form wire:submit.prevent="logCommunication" class="bg-light p-3 rounded border">
                                        <div class="mb-3">
                                            <label class="form-label" for="comm_medium">Interaction Channel *</label>
                                            <select wire:model="comm_medium" class="form-select form-select-sm @error('comm_medium') is-invalid @enderror" id="comm_medium" required>
                                                @foreach($mediums as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('comm_medium') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="comm_subject">Subject (Optional)</label>
                                            <input wire:model="comm_subject" type="text" class="form-control form-control-sm @error('comm_subject') is-invalid @enderror" id="comm_subject" placeholder="e.g. Call regarding package options">
                                            @error('comm_subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="comm_content">Interaction Details *</label>
                                            <textarea wire:model="comm_content" class="form-control form-control-sm @error('comm_content') is-invalid @enderror" id="comm_content" rows="4" required placeholder="Detail the call notes, emails exchanged, or general conversation..."></textarea>
                                            @error('comm_content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <button class="btn btn-primary btn-sm w-100" type="submit">
                                            <span wire:loading wire:target="logCommunication" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                            <span class="fas fa-plus-circle me-1"></span>Log Activity
                                        </button>
                                    </form>
                                </div>

                                <!-- Timeline -->
                                <div class="col-md-8">
                                    <h5 class="mb-3 text-800"><span class="fas fa-history me-2 text-primary"></span>Interaction History Timeline</h5>
                                    <div class="timelineTimeline border-start border-2 border-300 ps-4 py-2 position-relative">
                                        @forelse($communicationLogs as $log)
                                            <div class="mb-4 position-relative">
                                                <!-- Timeline Bullet Icon -->
                                                @php
                                                    $icons = [
                                                        'Call' => 'fa-phone text-success bg-subtle-success',
                                                        'WhatsApp' => 'fa-whatsapp text-success bg-subtle-success',
                                                        'SMS' => 'fa-sms text-info bg-subtle-info',
                                                        'Email' => 'fa-envelope text-primary bg-subtle-primary',
                                                        'Note' => 'fa-sticky-note text-warning bg-subtle-warning'
                                                    ];
                                                    $logIcon = $icons[$log->communication_medium] ?? 'fa-comment text-secondary bg-subtle-secondary';
                                                @endphp
                                                <div class="rounded-circle d-flex align-items-center justify-content-center border" style="width: 28px; height: 28px; position: absolute; left: -39px; top: 0; background: white; z-index: 1;">
                                                    <span class="fab {{ str_contains($logIcon, 'whatsapp') ? 'fa-whatsapp' : 'fas' }} {{ str_replace('bg-subtle-success', '', str_replace('bg-subtle-info', '', str_replace('bg-subtle-primary', '', str_replace('bg-subtle-warning', '', $logIcon)))) }} fs-11"></span>
                                                </div>

                                                <div class="bg-light p-3 rounded border shadow-none">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-1">
                                                        <h6 class="mb-0 text-900 fw-semi-bold">
                                                            @if($log->subject)
                                                                {{ $log->subject }}
                                                            @else
                                                                Logged a {{ $mediums[$log->communication_medium] ?? $log->communication_medium }}
                                                            @endif
                                                        </h6>
                                                        <small class="text-500 fs-11">{{ $log->created_at->diffForHumans() }} ({{ $log->created_at->format('d-M-Y H:i') }})</small>
                                                    </div>
                                                    <p class="mb-0 text-800 fs-10" style="white-space: pre-wrap;">{{ $log->content }}</p>
                                                    <div class="mt-2 text-600 fs-11 text-end">
                                                        Logged by: <strong>{{ $log->logger->name ?? 'System' }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-5 text-muted">
                                                <span class="fas fa-comments fa-2x d-block mb-2 text-300"></span>
                                                No communication logs recorded yet.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
