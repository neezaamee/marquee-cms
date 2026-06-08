@extends('layouts.admin')

@section('title', 'Edit Booking')

@section('styles')
    <link href="{{ asset('vendors/choices/choices.min.css') }}" rel="stylesheet">
@endsection

@section('content')
    <livewire:booking-edit :booking="$booking" />
@endsection

@section('scripts')
    <script src="{{ asset('vendors/choices/choices.min.js') }}"></script>
    <script src="{{ asset('vendors/imask/imask.min.js') }}"></script>
@endsection
