@extends('layouts.admin')

@section('title', 'Tax Configuration')

@section('content')
<div class="mb-3">
    <h4 class="mb-0"><span class="fas fa-percentage me-2 text-warning"></span>Tax & FBR Configuration</h4>
    <p class="text-muted fs-10 mb-0">Configure default tax rates and FBR POS integration credentials for each branch.</p>
</div>
<livewire:finance.tax-configuration />
@endsection
