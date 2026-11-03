<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support &amp; Help</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('support.tickets.index') }}">Support Tickets</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Submit New Ticket</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0 text-1000 fw-bold">Submit Support Ticket</h4>
                    <p class="text-secondary fs-10 mb-0">Describe the issue or assistance you require from our technical and billing team.</p>
                </div>
                <a href="{{ route('support.tickets.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Tickets
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Left Column: Ticket Creation Form --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-edit me-2 text-primary"></i>Ticket Information</h6>
                </div>
                <div class="card-body p-4">
                    <form wire:submit.prevent="submit">
                        {{-- Subject --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="subject">Subject / Issue Summary <span class="text-danger">*</span></label>
                            <input type="text" id="subject" wire:model="subject" class="form-control @error('subject') is-invalid @enderror" placeholder="e.g. Cannot generate invoice PDF for Booking #1042">
                            @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Category & Priority --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="category_id">Category <span class="text-danger">*</span></label>
                                <select id="category_id" wire:model="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                    <option value="">Select a category...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="priority">Priority Level <span class="text-danger">*</span></label>
                                <select id="priority" wire:model="priority" class="form-select @error('priority') is-invalid @enderror">
                                    <option value="low">Low (General guidance, minor cosmetic)</option>
                                    <option value="normal">Normal (Standard operational inquiry)</option>
                                    <option value="high">High (Feature not functioning, blocking work)</option>
                                    <option value="urgent">Urgent (System outage, payment failure)</option>
                                </select>
                                @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Related Module & Reference ID --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="related_module">Related System Module (Optional)</label>
                                <select id="related_module" wire:model="related_module" class="form-select @error('related_module') is-invalid @enderror">
                                    <option value="">-- None / General --</option>
                                    <option value="bookings">Bookings &amp; Events</option>
                                    <option value="accounts">Accounts &amp; Expense Vouchers</option>
                                    <option value="billing">Subscription &amp; Invoicing</option>
                                    <option value="inventory">Inventory &amp; Supplies</option>
                                    <option value="kitchen">Kitchen &amp; Menu</option>
                                    <option value="pos">FBR / POS Terminal</option>
                                    <option value="users">Staff &amp; Permissions</option>
                                </select>
                                @error('related_module') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="related_record_id">Reference ID (Optional)</label>
                                <input type="number" id="related_record_id" wire:model="related_record_id" class="form-control @error('related_record_id') is-invalid @enderror" placeholder="e.g. Booking ID or Invoice ID">
                                <span class="fs-11 text-secondary">Optional: Enter record number if issue pertains to a specific item.</span>
                                @error('related_record_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="description">Detailed Description <span class="text-danger">*</span></label>
                            <textarea id="description" wire:model="description" rows="6" class="form-control @error('description') is-invalid @enderror" placeholder="Please describe step-by-step what happened, any error messages displayed, and what you expected to see..."></textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Attachments --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="attachments">Upload Attachments / Screenshots (Optional)</label>
                            <input type="file" id="attachments" wire:model="attachments" multiple class="form-control @error('attachments.*') is-invalid @enderror">
                            <span class="fs-11 text-secondary d-block mt-1">Accepted formats: JPG, PNG, PDF, Word DOCX, Excel, ZIP (Max 10 MB per file, up to 5 files).</span>
                            @error('attachments.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                            {{-- File Uploading State --}}
                            <div wire:loading wire:target="attachments" class="text-primary fs-11 mt-2">
                                <i class="fas fa-spinner fa-spin me-1"></i> Uploading files to secure staging...
                            </div>

                            {{-- Selected Attachments Preview --}}
                            @if(!empty($attachments))
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    @foreach($attachments as $index => $file)
                                        <div class="badge bg-light text-dark border p-2 d-flex align-items-center">
                                            <i class="far fa-file me-2 text-primary"></i>
                                            <span class="text-truncate me-2" style="max-width: 200px;">{{ $file->getClientOriginalName() }}</span>
                                            <button type="button" wire:click="removeAttachment({{ $index }})" class="btn-close btn-close-sm" aria-label="Remove"></button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Submit Button --}}
                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <a href="{{ route('support.tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submit"><i class="fas fa-paper-plane me-1"></i> Create Ticket</span>
                                <span wire:loading wire:target="submit"><i class="fas fa-spinner fa-spin me-1"></i> Creating Ticket...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Column: Guidance Sidebar --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-clock me-2 text-warning"></i>SLA Response Standards</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush fs-10">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="badge bg-danger me-1">Urgent</span> Outages &amp; Critical Blockers
                            </div>
                            <span class="fw-bold text-1000">&lt; 1 Hour</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="badge bg-warning text-dark me-1">High</span> Major Features
                            </div>
                            <span class="fw-bold text-1000">&lt; 2 Hours</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="badge bg-info text-dark me-1">Normal</span> General Questions
                            </div>
                            <span class="fw-bold text-1000">&lt; 4 Hours</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="badge bg-secondary me-1">Low</span> Minor Requests
                            </div>
                            <span class="fw-bold text-1000">&lt; 8 Hours</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-body-tertiary">
                    <h6 class="mb-0 text-1000 fw-semibold"><i class="fas fa-lightbulb me-2 text-info"></i>Tips for Speedy Resolution</h6>
                </div>
                <div class="card-body fs-10 text-secondary">
                    <ul class="ps-3 mb-0">
                        <li class="mb-2">Include exact error messages or error code numbers.</li>
                        <li class="mb-2">Attach screenshots of your screen showing the entire window.</li>
                        <li class="mb-2">Specify the booking number, customer name, or receipt ID if applicable.</li>
                        <li>If urgent, verify your marquee internet connection before escalating.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

