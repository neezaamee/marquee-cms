@extends('layouts.admin')

@section('title', 'Expense Voucher Details')

@section('content')
    <livewire:finance.expense-detail :id="$id" />
@endsection
