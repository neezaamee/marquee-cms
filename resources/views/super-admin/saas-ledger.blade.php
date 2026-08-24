@extends('layouts.admin')

@section('title', 'SaaS General Ledger')

@section('content')
    <div class="mb-3">
        <h4 class="mb-0 text-primary">SaaS General Ledger</h4>
        <p class="text-muted fs-11">Query and view double-entry transaction ledgers recorded at the platform level.</p>
    </div>
    
    <livewire:finance.general-ledger :isSaas="true" />
@endsection
