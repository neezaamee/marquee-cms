<div>
    {{-- Header & Breadcrumbs --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Administration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.support.dashboard') }}">Support Operations</a></li>
                        <li class="breadcrumb-item active" aria-current="page">SLA &amp; Support Settings</li>
                    </ol>
                </nav>
                <h4 class="mb-0 text-1000 fw-bold">Support Desk Configuration &amp; SLA Matrix</h4>
                <p class="text-secondary fs-10 mb-0">Configure enterprise ticketing parameters, operating hours, and SLA response/resolution guarantees.</p>
            </div>
            <div>
                <a href="{{ route('admin.support.dashboard') }}" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-chart-line me-1"></i> Operations Dashboard
                </a>
                <button type="button" wire:click="saveSettings" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                    <span wire:loading.remove wire:target="saveSettings"><i class="fas fa-save me-1"></i> Save Changes</span>
                    <span wire:loading wire:target="saveSettings"><span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...</span>
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fs-7 me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form wire:submit.prevent="saveSettings">
        <div class="row g-3">
            {{-- Column Left: General Desk & Business Hours --}}
            <div class="col-lg-6">
                {{-- Card 1: General Desk Settings --}}
                <div class="card mb-3">
                    <div class="card-header bg-body-tertiary py-2 d-flex align-items-center">
                        <i class="fas fa-sliders-h text-primary me-2"></i>
                        <h6 class="mb-0 fw-bold">General Support Desk Parameters</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-10">Ticket Number Prefix <span class="text-danger">*</span></label>
                            <input type="text" wire:model="ticketPrefix" class="form-control form-control-sm @error('ticketPrefix') is-invalid @enderror" placeholder="e.g. TKT" style="text-transform: uppercase;">
                            <div class="form-text fs-10">Used when generating unique sequential numbers (e.g. <span class="fw-bold">{{ $ticketPrefix ?: 'TKT' }}-{{ date('Y') }}-0001</span>).</div>
                            @error('ticketPrefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold fs-10">Support Desk Email <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-envelope text-secondary"></i></span>
                                    <input type="email" wire:model="supportEmail" class="form-control @error('supportEmail') is-invalid @enderror" placeholder="support@marquee-cms.com">
                                </div>
                                @error('supportEmail') <div class="text-danger fs-10 mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold fs-10">Hotline / Phone Number</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-phone text-secondary"></i></span>
                                    <input type="text" wire:model="supportPhone" class="form-control @error('supportPhone') is-invalid @enderror" placeholder="+92 300 1234567">
                                </div>
                                @error('supportPhone') <div class="text-danger fs-10 mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold fs-10">Auto-Close Resolved Tickets (Days) <span class="text-danger">*</span></label>
                                <input type="number" wire:model="autoCloseDays" min="1" max="90" class="form-control form-control-sm @error('autoCloseDays') is-invalid @enderror">
                                <div class="form-text fs-10">Days before a resolved ticket is permanently marked closed.</div>
                                @error('autoCloseDays') <div class="text-danger fs-10 mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold fs-10">Default Assigned Specialist</label>
                                <select wire:model="defaultAgentId" class="form-select form-select-sm @error('defaultAgentId') is-invalid @enderror">
                                    <option value="">-- Round-Robin / Unassigned --</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->role?->name ?? 'Staff' }})</option>
                                    @endforeach
                                </select>
                                <div class="form-text fs-10">Default specialist assigned to new unrouted tickets.</div>
                                @error('defaultAgentId') <div class="text-danger fs-10 mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="enableLiveChat" wire:model="enableLiveChat">
                            <label class="form-check-label fw-semibold fs-10" for="enableLiveChat">
                                Enable Real-Time Customer Live Chat Widget
                            </label>
                            <div class="text-secondary fs-10">When enabled, tenants and marquee owners can initiate real-time conversational support sessions.</div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Business Operating Schedule --}}
                <div class="card mb-3">
                    <div class="card-header bg-body-tertiary py-2 d-flex align-items-center">
                        <i class="fas fa-clock text-info me-2"></i>
                        <h6 class="mb-0 fw-bold">Support Desk Operating Hours</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-10">Active Operating Days <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($weekDays as $day)
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input" type="checkbox" id="day_{{ $day }}" value="{{ $day }}" wire:model="businessDays">
                                        <label class="form-check-label fs-10" for="day_{{ $day }}">{{ substr($day, 0, 3) }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('businessDays') <div class="text-danger fs-10 mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold fs-10">Shift Start Time <span class="text-danger">*</span></label>
                                <input type="time" wire:model="businessStart" class="form-control form-control-sm @error('businessStart') is-invalid @enderror">
                                @error('businessStart') <div class="text-danger fs-10 mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold fs-10">Shift End Time <span class="text-danger">*</span></label>
                                <input type="time" wire:model="businessEnd" class="form-control form-control-sm @error('businessEnd') is-invalid @enderror">
                                @error('businessEnd') <div class="text-danger fs-10 mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold fs-10">Operating Timezone <span class="text-danger">*</span></label>
                            <select wire:model="businessTimezone" class="form-select form-select-sm @error('businessTimezone') is-invalid @enderror">
                                <option value="Asia/Karachi">Asia/Karachi (PKT, UTC+5)</option>
                                <option value="UTC">UTC (Coordinated Universal Time)</option>
                                <option value="Asia/Dubai">Asia/Dubai (GST, UTC+4)</option>
                                <option value="Europe/London">Europe/London (GMT/BST)</option>
                                <option value="America/New_York">America/New_York (EST/EDT)</option>
                                <option value="America/Los_Angeles">America/Los_Angeles (PST/PDT)</option>
                                <option value="Asia/Singapore">Asia/Singapore (SGT, UTC+8)</option>
                            </select>
                            @error('businessTimezone') <div class="text-danger fs-10 mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Column Right: SLA Targets Matrix --}}
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-header bg-body-tertiary py-2 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-stopwatch text-danger me-2"></i>
                            <h6 class="mb-0 fw-bold">Service Level Agreement (SLA) Matrix</h6>
                        </div>
                        <span class="badge badge-subtle-danger fs-11">Enforced Automatically</span>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-subtle-primary p-2 fs-10 mb-3">
                            <i class="fas fa-info-circle me-1"></i> SLA turnaround deadlines are calculated when a customer submits a ticket. If an issue is not resolved within the target timeframe, it is automatically flagged as <strong>SLA Breached</strong> on the Operations Dashboard.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0 fs-10">
                                <thead class="bg-body-secondary text-secondary">
                                    <tr>
                                        <th>PRIORITY</th>
                                        <th style="width: 38%;">FIRST RESPONSE (HRS)</th>
                                        <th style="width: 38%;">RESOLUTION (HRS)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Urgent --}}
                                    <tr>
                                        <td>
                                            <span class="badge badge-subtle-danger"><i class="fas fa-radiation me-1"></i> Urgent</span>
                                            <div class="text-secondary fs-11 mt-1">Outages / Critical</div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" wire:model="slaUrgentResponse" min="1" max="168" class="form-control text-center @error('slaUrgentResponse') is-invalid @enderror">
                                                <span class="input-group-text">hrs</span>
                                            </div>
                                            @error('slaUrgentResponse') <div class="text-danger fs-11">{{ $message }}</div> @enderror
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" wire:model="slaUrgentResolution" min="1" max="336" class="form-control text-center @error('slaUrgentResolution') is-invalid @enderror">
                                                <span class="input-group-text">hrs</span>
                                            </div>
                                            @error('slaUrgentResolution') <div class="text-danger fs-11">{{ $message }}</div> @enderror
                                        </td>
                                    </tr>

                                    {{-- High --}}
                                    <tr>
                                        <td>
                                            <span class="badge badge-subtle-warning"><i class="fas fa-exclamation-circle me-1"></i> High</span>
                                            <div class="text-secondary fs-11 mt-1">Major degradation</div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" wire:model="slaHighResponse" min="1" max="168" class="form-control text-center @error('slaHighResponse') is-invalid @enderror">
                                                <span class="input-group-text">hrs</span>
                                            </div>
                                            @error('slaHighResponse') <div class="text-danger fs-11">{{ $message }}</div> @enderror
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" wire:model="slaHighResolution" min="1" max="336" class="form-control text-center @error('slaHighResolution') is-invalid @enderror">
                                                <span class="input-group-text">hrs</span>
                                            </div>
                                            @error('slaHighResolution') <div class="text-danger fs-11">{{ $message }}</div> @enderror
                                        </td>
                                    </tr>

                                    {{-- Normal --}}
                                    <tr>
                                        <td>
                                            <span class="badge badge-subtle-primary"><i class="fas fa-circle me-1"></i> Normal</span>
                                            <div class="text-secondary fs-11 mt-1">Standard operations</div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" wire:model="slaNormalResponse" min="1" max="168" class="form-control text-center @error('slaNormalResponse') is-invalid @enderror">
                                                <span class="input-group-text">hrs</span>
                                            </div>
                                            @error('slaNormalResponse') <div class="text-danger fs-11">{{ $message }}</div> @enderror
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" wire:model="slaNormalResolution" min="1" max="336" class="form-control text-center @error('slaNormalResolution') is-invalid @enderror">
                                                <span class="input-group-text">hrs</span>
                                            </div>
                                            @error('slaNormalResolution') <div class="text-danger fs-11">{{ $message }}</div> @enderror
                                        </td>
                                    </tr>

                                    {{-- Low --}}
                                    <tr>
                                        <td>
                                            <span class="badge badge-subtle-secondary"><i class="fas fa-arrow-down me-1"></i> Low</span>
                                            <div class="text-secondary fs-11 mt-1">General queries</div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" wire:model="slaLowResponse" min="1" max="168" class="form-control text-center @error('slaLowResponse') is-invalid @enderror">
                                                <span class="input-group-text">hrs</span>
                                            </div>
                                            @error('slaLowResponse') <div class="text-danger fs-11">{{ $message }}</div> @enderror
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" wire:model="slaLowResolution" min="1" max="336" class="form-control text-center @error('slaLowResolution') is-invalid @enderror">
                                                <span class="input-group-text">hrs</span>
                                            </div>
                                            @error('slaLowResolution') <div class="text-danger fs-11">{{ $message }}</div> @enderror
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <button type="button" wire:click="loadSettings" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-undo me-1"></i> Discard &amp; Reset
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-sm px-4">
                            <span wire:loading.remove wire:target="saveSettings"><i class="fas fa-save me-1"></i> Save Configuration</span>
                            <span wire:loading wire:target="saveSettings"><span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
