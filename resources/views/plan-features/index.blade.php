@extends('layouts.admin')

@section('title', 'Plan Features')

@section('content')
    <div class="card mb-3">
        <div class="card-header bg-light">
            <div class="row align-items-center justify-content-between">
                <div class="col-6">
                    <h5 class="mb-0">Plan Features & Limits Matrix</h5>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <ul class="nav nav-tabs px-3" id="planFeaturesTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="matrix-tab" data-bs-toggle="tab" data-bs-target="#matrix" type="button" role="tab" aria-controls="matrix" aria-selected="true">
                        <span class="fas fa-th me-2"></span>Features Matrix
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab" aria-controls="features" aria-selected="false">
                        <span class="fas fa-list me-2"></span>Feature Definitions (CRUD)
                    </button>
                </li>
            </ul>
            <div class="tab-content p-3" id="planFeaturesTabContent">
                <div class="tab-pane fade show active" id="matrix" role="tabpanel" aria-labelledby="matrix-tab">
                    <livewire:feature-matrix />
                </div>
                <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab">
                    <livewire:features-list />
                </div>
            </div>
        </div>
    </div>
@endsection
