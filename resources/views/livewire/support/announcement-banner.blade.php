<div>
    @foreach($announcements as $ann)
        <div class="{{ $ann->alert_class }} d-flex justify-content-between align-items-center mb-3 shadow-sm rounded-3 py-2 px-3 border" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="{{ $ann->severity_icon }} fs-3 me-1"></i>
                <div>
                    <strong class="d-inline-block">{{ $ann->title }}:</strong>
                    <span class="fs-10">{!! nl2br(e($ann->message)) !!}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 ms-3">
                @if($ann->requires_acknowledgment)
                    <button type="button" wire:click="acknowledge({{ $ann->id }})" class="btn btn-sm btn-outline-dark fs-11 py-1 px-2 text-nowrap">
                        <i class="fas fa-check me-1"></i> I Acknowledge
                    </button>
                @endif
            </div>
        </div>
    @endforeach
</div>
