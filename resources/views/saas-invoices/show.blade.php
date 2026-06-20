@extends('layouts.admin')

@section('title', 'Invoice Details')

@section('content')
    <livewire:saas-invoice-detail :invoice="$invoice" />
@endsection
