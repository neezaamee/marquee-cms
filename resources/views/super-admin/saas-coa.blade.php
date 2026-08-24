@extends('layouts.admin')

@section('title', 'SaaS Chart of Accounts')

@section('content')
    <div class="mb-3">
        <h4 class="mb-0 text-primary">SaaS Chart of Accounts</h4>
        <p class="text-muted fs-11">Manage platform-level accounts used for subscription billing and financial records.</p>
    </div>
    
    <livewire:finance.chart-of-accounts :isSaas="true" />
@endsection
