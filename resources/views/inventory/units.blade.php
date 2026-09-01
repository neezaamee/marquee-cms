@extends('layouts.admin')

@section('title', 'Units of Measure')

@section('content')
<div class="mb-3">
    <h4 class="mb-0"><span class="fas fa-balance-scale me-2 text-primary"></span>Units of Measure</h4>
    <p class="text-muted fs-10 mb-0">Manage measurement units and their conversion rates across all inventory operations.</p>
</div>

<ul class="nav nav-tabs mb-3" id="unitsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="units-tab" data-bs-toggle="tab" data-bs-target="#units-pane" type="button" role="tab" aria-controls="units-pane" aria-selected="true">
            <span class="fas fa-ruler me-2"></span>Units of Measure
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="conversions-tab" data-bs-toggle="tab" data-bs-target="#conversions-pane" type="button" role="tab" aria-controls="conversions-pane" aria-selected="false">
            <span class="fas fa-exchange-alt me-2"></span>Conversion Engine
        </button>
    </li>
</ul>

<div class="tab-content" id="unitsTabsContent">
    <div class="tab-pane fade show active" id="units-pane" role="tabpanel" aria-labelledby="units-tab">
        <livewire:inventory.unit-list />
    </div>
    <div class="tab-pane fade" id="conversions-pane" role="tabpanel" aria-labelledby="conversions-tab">
        <livewire:inventory.unit-conversion-list />
    </div>
</div>
@endsection
