<div>
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3 animate__animated animate__fadeIn" role="alert">
            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
            <p class="mb-0 flex-1">{{ session('success') }}</p>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($isSuperAdmin && count($marquees) > 0)
        <div class="card border border-200 mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><span class="fas fa-building me-2 text-primary"></span>Select Business (Super Admin)</h6>
            </div>
            <div class="card-body">
                <select wire:model.live="selectedMarqueeId" class="form-select form-select-sm" style="max-width: 400px;">
                    @foreach($marquees as $marquee)
                        <option value="{{ $marquee->id }}">{{ $marquee->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    @forelse($branchData as $branchId => $branch)
        <div class="card border border-200 mb-3">
            <div class="card-header bg-light d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">
                        <span class="fas fa-store me-2 text-primary"></span>{{ $branch['name'] }}
                        @if($branch['is_head_office'])
                            <span class="badge badge-subtle-warning ms-2 fs-11">Head Office</span>
                        @endif
                    </h5>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Tax Rate -->
                    <div class="col-md-3">
                        <label class="form-label" for="tax-rate-{{ $branchId }}">
                            <span class="fas fa-percentage me-1 text-warning"></span>Default Tax Rate (%)
                        </label>
                        <div class="input-group input-group-sm">
                            <input wire:model="branchData.{{ $branchId }}.tax_rate"
                                   type="number" step="0.01" min="0" max="100"
                                   class="form-control @error("branchData.{$branchId}.tax_rate") is-invalid @enderror"
                                   id="tax-rate-{{ $branchId }}"
                                   placeholder="0.00" />
                            <span class="input-group-text">%</span>
                        </div>
                        @error("branchData.{$branchId}.tax_rate")
                            <div class="text-danger fs-11 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- FBR POS ID -->
                    <div class="col-md-3">
                        <label class="form-label" for="fbr-pos-id-{{ $branchId }}">
                            <span class="fas fa-cash-register me-1 text-info"></span>FBR POS ID
                        </label>
                        <input wire:model="branchData.{{ $branchId }}.fbr_pos_id"
                               type="text"
                               class="form-control form-control-sm @error("branchData.{$branchId}.fbr_pos_id") is-invalid @enderror"
                               id="fbr-pos-id-{{ $branchId }}"
                               placeholder="e.g. POS-12345" />
                        @error("branchData.{$branchId}.fbr_pos_id")
                            <div class="text-danger fs-11 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- FBR POS Key -->
                    <div class="col-md-4">
                        <label class="form-label" for="fbr-pos-key-{{ $branchId }}">
                            <span class="fas fa-key me-1 text-warning"></span>FBR POS Secret Key
                        </label>
                        <input wire:model="branchData.{{ $branchId }}.fbr_pos_key"
                               type="password"
                               class="form-control form-control-sm @error("branchData.{$branchId}.fbr_pos_key") is-invalid @enderror"
                               id="fbr-pos-key-{{ $branchId }}"
                               placeholder="FBR POS secret key" />
                        @error("branchData.{$branchId}.fbr_pos_key")
                            <div class="text-danger fs-11 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- FBR Sandbox Mode -->
                    <div class="col-md-2 d-flex align-items-end pb-1">
                        <div class="form-check form-switch">
                            <input wire:model="branchData.{{ $branchId }}.fbr_sandbox_mode"
                                   class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="fbr-sandbox-{{ $branchId }}" />
                            <label class="form-check-label" for="fbr-sandbox-{{ $branchId }}">
                                Sandbox Mode
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button wire:click="saveBranch({{ $branchId }})" class="btn btn-falcon-primary btn-sm">
                        <span class="fas fa-save me-1"></span>Save Branch Settings
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="card border border-200">
            <div class="card-body text-center py-5 text-muted">
                <span class="fas fa-store fa-2x mb-2 d-block"></span>
                No branches found for this business. Please add branches first.
            </div>
        </div>
    @endforelse
</div>
