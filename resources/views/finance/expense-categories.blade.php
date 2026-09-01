@extends('layouts.admin')

@section('title', 'Expense Categories')

@section('content')
<div class="mb-3">
    <h4 class="mb-0"><span class="fas fa-folder-open me-2 text-primary"></span>Expense Categories</h4>
    <p class="text-muted fs-10 mb-0">Manage expense classification hierarchy, default GL accounts, tax rates and budgets.</p>
</div>
<livewire:finance.expense-category-manager />
@endsection
