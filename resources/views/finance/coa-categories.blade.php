@extends('layouts.admin')

@section('title', 'COA Categories')

@section('content')
<div class="mb-3">
    <h4 class="mb-0"><span class="fas fa-tags me-2 text-primary"></span>Chart of Accounts — Categories</h4>
    <p class="text-muted fs-10 mb-0">Manage account type classifications (Asset, Liability, Equity, Income, Expense) used in the Chart of Accounts.</p>
</div>
<livewire:finance.account-type-manager />
@endsection
