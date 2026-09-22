@extends('layouts.admin')

@section('title', 'Payment Voucher Details')

@section('content')
    <livewire:finance.payment-voucher-detail :id="$id" />
@endsection
