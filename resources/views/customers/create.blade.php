@extends('layouts.admin')

@section('title', 'Add New Customer')

@section('styles')
    <link href="{{ asset('vendors/choices/choices.min.css') }}" rel="stylesheet">
@endsection

@section('content')
    <livewire:customer-form />
@endsection

@section('scripts')
    <script src="{{ asset('vendors/choices/choices.min.js') }}"></script>
    <script src="{{ asset('vendors/imask/imask.min.js') }}"></script>
@endsection
