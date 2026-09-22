@extends('layouts.admin')

@section('title', 'Edit Payment Voucher')

@section('content')
    <livewire:finance.payment-voucher-form :id="$id" />
@endsection
