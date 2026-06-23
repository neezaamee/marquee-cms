@extends('layouts.admin')

@section('title', 'Purchase Order Form')

@section('content')
    <livewire:purchases.purchase-order-form :id="$id" />
@endsection
