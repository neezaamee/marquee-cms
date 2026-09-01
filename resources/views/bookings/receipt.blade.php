@extends('layouts.admin')

@section('title', 'Payment Receipt')

@section('content')
<div class="container py-3">
    <!-- Screen-Only Controls (Hidden on Print) -->
    <div class="d-print-none card mb-3 bg-light">
        <div class="card-body d-flex justify-content-between align-items-center py-2">
            <span class="fs-11 text-700 fw-semi-bold">
                <span class="fas fa-info-circle me-1"></span>Use the button below or print page (Ctrl+P) to generate a PDF or paper receipt copy.
            </span>
            <div class="d-flex gap-2">
                <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-chevron-left me-1"></span> Back to Booking
                </a>
                <button onclick="window.print();" class="btn btn-success btn-sm px-4">
                    <span class="fas fa-print me-1"></span> Print Receipt
                </button>
            </div>
        </div>
    </div>

    @php
        $marquee = $booking->effective_marquee ?? $booking->marquee ?? (auth()->user()->marquee ?? null);
        $branch = $booking->effective_branch ?? $booking->branch ?? ($booking->hall?->branch ?? null);
    @endphp
    <!-- Printable Area -->
    <div class="card bg-white shadow-none border p-4 p-md-5" id="printable-receipt">
        <div class="row align-items-center mb-4">
            <!-- Brand Info -->
            <div class="col-sm-6 text-start">
                <h3 class="text-primary fw-black mb-1">{{ $marquee->name ?? 'MARQUEE CMS' }}</h3>
                @if($branch)
                    <div class="text-800 fw-bold fs-12 text-uppercase text-secondary mb-1">
                        <span class="fas fa-building me-1 text-primary"></span>{{ $branch->name }}
                        @if($branch->is_head_office)
                            <span class="badge bg-primary-subtle text-primary ms-1 fs-12">Head Office</span>
                        @endif
                    </div>
                    <div class="fs-12 text-600">
                        <span class="fas fa-map-marker-alt me-1"></span>{{ $branch->address ? $branch->address . ', ' : '' }}{{ $branch->city ?? ($marquee->city ?? '') }}{{ $branch->province ? ', ' . $branch->province : '' }}
                        @if($branch->phone || ($marquee->phone ?? null))
                            | <span class="fas fa-phone me-1"></span>{{ $branch->phone ?: $marquee->phone }}
                        @endif
                        @if($branch->branch_manager)
                            | <strong>Manager:</strong> {{ $branch->branch_manager }}
                        @endif
                    </div>
                @else
                    <div class="fs-12 text-600">
                        {{ $marquee->address ?? 'Main Boulevard, Gulberg' }}, {{ $marquee->city ?? 'Lahore' }}
                        @if($marquee->phone ?? null) | <span class="fas fa-phone me-1"></span>{{ $marquee->phone }} @endif
                    </div>
                @endif
            </div>
            <!-- Receipt Voucher Reference -->
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <h4 class="text-success fw-bold mb-1">PAYMENT RECEIPT</h4>
                <div class="fs-11 font-monospace text-secondary">RECEIPT VOUCHER: #REC-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="fs-11 font-monospace text-secondary">BOOKING REFERENCE: #{{ $booking->booking_number }}</div>
                @if($branch)
                    <div class="fs-12 text-600 font-monospace">Branch: {{ $branch->name }}</div>
                @endif
            </div>
        </div>

        <hr />

        <!-- Meta Grid: Customer & Booking Info -->
        <div class="row g-3 my-2">
            <div class="col-sm-6">
                <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-1">Payer / Customer Details</span>
                @if($booking->customer)
                    <table class="table table-sm table-borderless fs-11 mb-0">
                        <tr>
                            <td class="text-600 px-0 py-1" style="width: 110px;">Full Name:</td>
                            <td class="text-800 fw-bold px-0 py-1">{{ $booking->customer->full_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">Contact:</td>
                            <td class="text-800 fw-bold px-0 py-1">{{ $booking->customer->phone_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">Email:</td>
                            <td class="text-800 px-0 py-1">{{ $booking->customer->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-600 px-0 py-1">CNIC / NTN:</td>
                            <td class="text-800 px-0 py-1">
                                {{ $booking->customer->cnic_national_id ?? '—' }} 
                                @if($booking->customer->ntn_number)
                                    / {{ $booking->customer->ntn_number }}
                                @endif
                            </td>
                        </tr>
                    </table>
                @else
                    <p class="text-muted fs-11 mb-0">No customer detail attached.</p>
                @endif
            </div>

            <div class="col-sm-6">
                <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-1">Booking & Event Details</span>
                <table class="table table-sm table-borderless fs-11 mb-0">
                    @if($branch)
                        <tr>
                            <td class="text-600 px-0 py-1" style="width: 120px;">Branch:</td>
                            <td class="text-800 fw-bold px-0 py-1">{{ $branch->name }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-600 px-0 py-1" style="width: 120px;">Booking Hall(s):</td>
                        <td class="text-800 fw-bold px-0 py-1">
                            @if($booking->halls->isNotEmpty())
                                {{ $booking->halls->pluck('hall_name')->implode(', ') }}
                            @else
                                {{ $booking->hall->hall_name ?? '—' }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-600 px-0 py-1">Event Date:</td>
                        <td class="text-800 fw-bold px-0 py-1">{{ $booking->booking_date->format('l, F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-600 px-0 py-1">Event Type:</td>
                        <td class="text-800 px-0 py-1">{{ $booking->eventType->event_type_name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-600 px-0 py-1">Timings:</td>
                        <td class="text-800 font-monospace px-0 py-1">
                            {{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Particulars -->
        <div class="my-4">
            <span class="text-500 fw-bold d-block text-uppercase fs-12 mb-2">Payment Particulars</span>
            <div class="table-responsive">
                <table class="table table-sm table-striped border fs-11 align-middle mb-0">
                    <thead class="bg-light text-900">
                        <tr>
                            <th class="ps-3">Payment Date</th>
                            <th>Method</th>
                            <th>Transaction Reference</th>
                            <th>Recorded By</th>
                            <th>Notes / Memo</th>
                            <th class="text-end pe-3" style="width: 180px;">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="ps-3 fw-bold">{{ $payment->payment_date->format('F d, Y') }}</td>
                            <td><span class="badge badge-subtle-success">{{ $payment->payment_method }}</span></td>
                            <td class="font-monospace text-700">{{ $payment->transaction_reference ?? '—' }}</td>
                            <td>{{ $payment->recorder->name ?? 'Staff User' }}</td>
                            <td class="text-muted">{{ $payment->notes ?? '—' }}</td>
                            <td class="text-end pe-3 font-monospace fw-black text-success fs-10">Rs. {{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ledger Financial Summary (Account Balances) -->
        <div class="row justify-content-end mb-4">
            <div class="col-sm-5 text-end fs-11">
                <div class="d-flex justify-content-between fs-10 fw-black text-success mt-2">
                    <span>Paid Amount:</span>
                    <span class="font-monospace">Rs. {{ number_format($payment->amount, 2) }}</span>
                </div>
            </div>
        </div>

        <hr />

        <!-- Signatures layout -->
        <div class="row justify-content-between align-items-end mt-5 pt-4">
            <div class="col-sm-5 text-center">
                <hr class="mb-1" />
                <span class="fs-12 text-600">Payer / Customer Signature</span>
            </div>
            <div class="col-sm-5 text-center">
                <hr class="mb-1" />
                <span class="fs-12 text-600">Received By (Authorized Officer)</span>
            </div>
        </div>
    </div>

    <!-- Print Custom Stylesheet Injection -->
    <style>
        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .content, .main {
                padding: 0 !important;
                margin: 0 !important;
            }
            #printable-receipt {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            nav, .navbar, .navbar-vertical, .d-print-none, footer, .footer {
                display: none !important;
            }
        }
    </style>
</div>
@endsection
