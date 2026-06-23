@extends('layouts.admin')

@section('title', 'Edit Journal Voucher')

@section('content')
    <livewire:finance.journal-voucher-form :id="$id" />
@endsection
