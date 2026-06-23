@extends('layouts.admin')

@section('title', 'Supplier Ledger')

@section('content')
    <livewire:inventory.supplier-ledger-view :supplier="$supplier" />
@endsection
