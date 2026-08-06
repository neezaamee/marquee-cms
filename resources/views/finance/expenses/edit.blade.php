@extends('layouts.admin')

@section('title', 'Edit Expense Voucher')

@section('content')
    <livewire:finance.expense-form :id="$id" />
@endsection
