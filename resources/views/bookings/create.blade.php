@extends('layouts.admin')

@section('title', 'Create Booking')

@section('styles')
    <link href="{{ asset('vendors/choices/choices.min.css') }}" rel="stylesheet">
@endsection

@section('content')
    <livewire:booking-wizard />
@endsection

@section('scripts')
    <script src="{{ asset('vendors/choices/choices.min.js') }}"></script>
    <script src="{{ asset('vendors/imask/imask.min.js') }}"></script>
@endsection
