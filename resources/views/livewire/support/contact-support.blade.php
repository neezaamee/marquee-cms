<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support &amp; Help</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Contact Support</li>
                </ol>
            </nav>
            <h4 class="mb-0 text-1000 fw-bold">Contact Enterprise Support Desk</h4>
            <p class="text-secondary fs-10 mb-0">Reach our product specialists, account executives, or technical engineers directly.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        {{-- Left: Form --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-envelope-open-text me-2 text-primary"></i>Inquiry Details</h6>
                </div>
                <div class="card-body p-4">
                    <form wire:submit.prevent="submitInquiry">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-semibold text-secondary" for="name">Your Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" wire:model="name" class="form-control @error('name') is-invalid @enderror">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-semibold text-secondary" for="email">Email Address <span class="text-danger">*</span></label>
                                <input type="email" id="email" wire:model="email" class="form-control @error('email') is-invalid @enderror">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-semibold text-secondary" for="phone">Phone / Mobile Number</label>
                                <input type="text" id="phone" wire:model="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="e.g. 0300-1234567">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-semibold text-secondary" for="company_name">Marquee / Company Name</label>
                                <input type="text" id="company_name" wire:model="company_name" class="form-control @error('company_name') is-invalid @enderror">
                                @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-semibold text-secondary" for="category">Inquiry Department <span class="text-danger">*</span></label>
                                <select id="category" wire:model="category" class="form-select @error('category') is-invalid @enderror">
                                    <option value="general">General Support</option>
                                    <option value="sales">Sales &amp; Upgrades</option>
                                    <option value="billing">Billing &amp; Invoices</option>
                                    <option value="partnership">Partner &amp; Vendor Integration</option>
                                    <option value="enterprise">Custom Multi-Branch Setup</option>
                                </select>
                                @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-semibold text-secondary" for="preferred_contact_method">Preferred Contact Channel <span class="text-danger">*</span></label>
                                <select id="preferred_contact_method" wire:model="preferred_contact_method" class="form-select @error('preferred_contact_method') is-invalid @enderror">
                                    <option value="email">Email Message</option>
                                    <option value="phone">Direct Phone Call</option>
                                    <option value="whatsapp">WhatsApp Business</option>
                                </select>
                                @error('preferred_contact_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-11 fw-semibold text-secondary" for="subject">Subject <span class="text-danger">*</span></label>
                            <input type="text" id="subject" wire:model="subject" class="form-control @error('subject') is-invalid @enderror" placeholder="e.g. Schedule onboarding session for our new branch managers">
                            @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fs-11 fw-semibold text-secondary" for="message">Detailed Message <span class="text-danger">*</span></label>
                            <textarea id="message" wire:model="message" rows="5" class="form-control @error('message') is-invalid @enderror" placeholder="Provide full details regarding your inquiry..."></textarea>
                            @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="text-end border-top pt-3">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitInquiry"><i class="fas fa-paper-plane me-1"></i> Send Inquiry</span>
                                <span wire:loading wire:target="submitInquiry"><i class="fas fa-spinner fa-spin me-1"></i> Sending...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: Direct Channels Sidebar --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-headset me-2 text-primary"></i>Direct Channels</h6>
                </div>
                <div class="card-body fs-10">
                    <div class="mb-3">
                        <span class="text-secondary d-block fw-semibold">Email Desk:</span>
                        <a href="mailto:support@marquee-cms.com" class="fw-bold text-decoration-none">support@marquee-cms.com</a>
                    </div>
                    <div class="mb-3">
                        <span class="text-secondary d-block fw-semibold">Support Desk Operating Hours:</span>
                        <span class="text-1000">Monday &ndash; Saturday: 09:00 AM &ndash; 06:00 PM (PKT)</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-secondary d-block fw-semibold">Emergency Event-Day Hotline:</span>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle p-2 font-monospace fs-11 d-block mt-1">
                            <i class="fas fa-phone-alt me-1"></i> +92 (300) 000-0000 (Available 24/7)
                        </span>
                    </div>
                    <div>
                        <span class="text-secondary d-block fw-semibold">Turnaround Commitment:</span>
                        <span class="text-1000">Inquiries submitted during business hours are reviewed within 4 hours.</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-info-circle me-2 text-info"></i>Need Immediate Help?</h6>
                </div>
                <div class="card-body fs-10">
                    <p class="text-secondary mb-3">If you are currently experiencing a technical bug or active booking blocker, consider opening a priority ticket or starting a live chat session:</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('support.tickets.create') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-ticket-alt me-1"></i> Open Support Ticket
                        </a>
                        <a href="{{ route('support.chat') }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-comments me-1"></i> Start Live Chat
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
