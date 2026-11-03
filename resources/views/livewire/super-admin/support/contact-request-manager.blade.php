<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Administration</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support Operations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Contact Inquiries</li>
                </ol>
            </nav>
            <h4 class="mb-0 text-1000 fw-bold">Contact Requests &amp; Sales Inquiries</h4>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Main Filter & Table Card --}}
    <div class="card">
        <div class="card-header bg-body-tertiary py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-secondary"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by name, email, phone, company, or subject...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="statusFilter" class="form-select form-select-sm">
                        <option value="all">All Statuses</option>
                        <option value="new">New Inquiries</option>
                        <option value="contacted">Contacted</option>
                        <option value="in_progress">In Discussion</option>
                        <option value="completed">Completed / Won</option>
                        <option value="closed">Closed / Archived</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select wire:model.live="categoryFilter" class="form-select form-select-sm">
                        <option value="all">All Departments</option>
                        <option value="general">General Support</option>
                        <option value="sales">Sales &amp; Upgrades</option>
                        <option value="billing">Billing &amp; Invoices</option>
                        <option value="partnership">Partnerships</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <button wire:click="$set('search', ''); $set('statusFilter', 'all'); $set('categoryFilter', 'all');" class="btn btn-outline-secondary btn-sm w-100" title="Reset Filters">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-10">
                    <thead class="bg-body-secondary text-secondary">
                        <tr>
                            <th class="ps-3">CONTACT PERSON</th>
                            <th>COMPANY / MARQUEE</th>
                            <th>DEPARTMENT</th>
                            <th>SUBJECT &amp; MESSAGE</th>
                            <th>PREFERRED METHOD</th>
                            <th>ASSIGNED TO</th>
                            <th>STATUS</th>
                            <th>RECEIVED</th>
                            <th class="pe-3 text-end">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contacts as $c)
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-bold text-1000 d-block">{{ $c->name }}</span>
                                    <span class="font-monospace text-secondary fs-12">{{ $c->email }}</span>
                                    @if($c->phone)
                                        <div class="font-monospace text-secondary fs-12">{{ $c->phone }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold text-1000">{{ $c->company_name ?: ($c->marquee?->name ?? '—') }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ ucfirst($c->category) }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-1000 d-block">{{ $c->subject }}</span>
                                    <span class="text-secondary text-truncate d-inline-block" style="max-width: 250px;">
                                        {{ Str::limit($c->message, 65) }}
                                    </span>
                                </td>
                                <td>
                                    @if($c->preferred_contact_method === 'whatsapp')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="fab fa-whatsapp me-1"></i> WhatsApp
                                        </span>
                                    @elseif($c->preferred_contact_method === 'phone')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                                            <i class="fas fa-phone-alt me-1"></i> Phone
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <i class="fas fa-envelope me-1"></i> Email
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-secondary fs-11">{{ $c->assignedAgent?->name ?? 'Unassigned' }}</span>
                                </td>
                                <td>
                                    <span class="{{ $c->status_badge }} px-2 py-0 fs-11">{{ $c->status_label }}</span>
                                </td>
                                <td class="text-secondary fs-11">{{ $c->created_at->format('M d, Y') }}</td>
                                <td class="pe-3 text-end">
                                    <button type="button" wire:click="review({{ $c->id }})" class="btn btn-primary btn-xs" data-bs-toggle="modal" data-bs-target="#contactModal">
                                        Manage
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-secondary">
                                    <p class="mb-0">No contact requests matching criteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($contacts->hasPages())
            <div class="card-footer bg-body-tertiary py-2">
                {{ $contacts->links() }}
            </div>
        @endif
    </div>

    {{-- Review Modal --}}
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                @if($activeContact)
                    <form wire:submit.prevent="saveReview">
                        <div class="modal-header">
                            <h5 class="modal-title" id="contactModalLabel">Manage Inquiry: {{ $activeContact->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            {{-- Info Banner --}}
                            <div class="bg-light p-3 rounded-3 mb-3 border">
                                <div class="row g-2 fs-10">
                                    <div class="col-sm-6">
                                        <span class="text-secondary">Client Name:</span> <strong class="text-1000">{{ $activeContact->name }}</strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-secondary">Email:</span> <strong class="text-1000 font-monospace">{{ $activeContact->email }}</strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-secondary">Phone:</span> <strong class="text-1000 font-monospace">{{ $activeContact->phone ?: 'Not provided' }}</strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-secondary">Company:</span> <strong class="text-1000">{{ $activeContact->company_name ?: '—' }}</strong>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-secondary">Preferred Channel:</span> <span class="badge bg-light text-dark border">{{ ucfirst($activeContact->preferred_contact_method) }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-secondary">Department:</span> <span class="badge bg-primary-subtle text-primary">{{ ucfirst($activeContact->category) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Subject & Message --}}
                            <div class="mb-3">
                                <h6 class="fw-bold text-1000 mb-1">{{ $activeContact->subject }}</h6>
                                <div class="p-3 bg-body-tertiary rounded-3 fs-10 text-1000 border" style="white-space: pre-line;">{!! nl2br(e($activeContact->message)) !!}</div>
                            </div>

                            {{-- Operational Controls --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-semibold text-secondary">Inquiry Status</label>
                                    <select wire:model="contactStatus" class="form-select form-select-sm">
                                        <option value="new">New Inquiry</option>
                                        <option value="contacted">Contacted Client</option>
                                        <option value="in_progress">In Discussion</option>
                                        <option value="completed">Completed / Won</option>
                                        <option value="closed">Closed / Archived</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-semibold text-secondary">Assign Specialist</label>
                                    <select wire:model="assignedTo" class="form-select form-select-sm">
                                        <option value="">-- Unassigned --</option>
                                        @foreach($agents as $ag)
                                            <option value="{{ $ag->id }}">{{ $ag->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-11 fw-semibold text-secondary">Follow-Up Date</label>
                                    <input type="date" wire:model="followUpDate" class="form-control form-control-sm">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fs-11 fw-semibold text-secondary">Internal Follow-Up Notes</label>
                                <textarea wire:model="internalNotes" rows="3" class="form-control form-control-sm" placeholder="Record notes regarding call outcome, quote sent, or next steps..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" data-bs-dismiss="modal" class="btn btn-primary btn-sm">Update Inquiry</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
