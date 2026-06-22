@extends('layouts.admin')

@section('title', 'Create Booking')

@section('styles')
    <link href="{{ asset('vendors/choices/choices.min.css') }}" rel="stylesheet">
@endsection

@section('content')
    @php
        $approach = request()->query('approach', 'wizard');
    @endphp

    <div class="card mb-3">
        <div class="card-body p-3">
            <div class="row align-items-center justify-content-between g-2">
                <div class="col-sm-auto">
                    <h4 class="mb-0 text-primary fw-bold">
                        <span class="fas fa-calendar-plus me-2"></span>Create New Booking
                    </h4>
                </div>
                <div class="col-sm-auto">
                    <div class="nav nav-pills" role="tablist">
                        <a href="{{ route('bookings.create', ['approach' => 'wizard']) }}" class="nav-link py-1.5 px-3 {{ $approach === 'wizard' ? 'active' : '' }}">
                            <span class="fas fa-magic me-2"></span>Wizard Form
                        </a>
                        <a href="{{ route('bookings.create', ['approach' => 'one-page']) }}" class="nav-link py-1.5 px-3 {{ $approach === 'one-page' ? 'active' : '' }}">
                            <span class="fas fa-file-alt me-2"></span>One-Page Form
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($approach === 'one-page')
        <livewire:booking-one-page />
    @else
        <livewire:booking-wizard />
    @endif
@endsection

@section('scripts')
    <script src="{{ asset('vendors/choices/choices.min.js') }}"></script>
    <script src="{{ asset('vendors/imask/imask.min.js') }}"></script>
@endsection
