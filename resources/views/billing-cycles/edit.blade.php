@extends('layouts.admin')

@section('title', 'Edit Billing Cycle')

@section('content')
    <livewire:billing-cycle-form :cycle="$cycle" />
@endsection
