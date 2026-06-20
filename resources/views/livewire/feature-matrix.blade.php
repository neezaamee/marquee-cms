<div>
    @if(session('success'))
        <div class="alert alert-success border-2 d-flex align-items-center mb-3" role="alert">
            <div class="bg-success me-3 icon-item"><span class="fas fa-check-circle text-white fs-8"></span></div>
            <p class="mb-0 flex-1">{{ session('success') }}</p>
            <button class="btn-close" type="button" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="table-responsive scrollbar mb-3">
        <table class="table table-sm table-bordered fs-10 mb-0">
            <thead class="bg-200 text-900">
                <tr>
                    <th class="align-middle px-3" style="min-width: 250px;">Features</th>
                    @foreach($plans as $plan)
                        <th class="align-middle text-center" style="min-width: 150px;">
                            <div><strong>{{ $plan->name }}</strong></div>
                            <span class="badge badge-subtle-primary fs-11 mt-1">{{ number_format($plan->monthly_price, 2) }} {{ $plan->currency }}/mo</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($features as $feature)
                    <tr>
                        <td class="align-middle px-3">
                            <div class="fw-semi-bold">{{ $feature->feature_name }}</div>
                            <small class="text-muted d-block">{{ $feature->description }}</small>
                            <code class="fs-11 text-600">{{ $feature->feature_key }}</code>
                        </td>
                        @foreach($plans as $plan)
                            <td class="align-middle text-center p-3">
                                <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               wire:model="matrix.{{ $plan->id }}.{{ $feature->id }}.enabled"
                                               id="check_{{ $plan->id }}_{{ $feature->id }}">
                                        <label class="form-check-label fs-11" for="check_{{ $plan->id }}_{{ $feature->id }}">Enabled</label>
                                    </div>

                                    @if(data_get($matrix, "{$plan->id}.{$feature->id}.enabled"))
                                        <input class="form-control form-control-sm text-center" type="text"
                                               wire:model="matrix.{{ $plan->id }}.{{ $feature->id }}.limit_value"
                                               placeholder="Limit (e.g. 5, Unlimited)" style="max-width: 120px;">
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($plans) + 1 }}" class="text-center py-4 text-muted">No active plan features defined. Please define features in the second tab first.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(count($features) > 0 && count($plans) > 0)
        <div class="d-flex justify-content-start gap-2">
            <button wire:click="saveMatrix" class="btn btn-primary btn-sm px-4">
                <span class="fas fa-save me-1"></span> Save Matrix Mappings
            </button>
            <button wire:click="loadMatrix" class="btn btn-falcon-default btn-sm px-3">
                <span class="fas fa-sync-alt me-1"></span> Reset
            </button>
        </div>
    @endif
</div>
