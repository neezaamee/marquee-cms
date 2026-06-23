@extends('layouts.admin')

@section('title', 'Purchase Invoice Form')

@section('content')
    <livewire:purchases.purchase-invoice-form :id="$id" />
@endsection
