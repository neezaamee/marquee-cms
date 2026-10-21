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
    <div class="card bg-white shadow-none border p-3 p-md-4" id="printable-receipt">
        <div class="row align-items-center mb-2">
            <!-- Brand Info -->
            <div class="col-sm-6 text-start">
                <h3 class="text-primary fw-black mb-1">{{ $marquee->name ?? 'MARQUEE CMS' }}</h3>
                @if($branch)
                    <div class="text-900 fw-bold fs-10 text-uppercase mb-1">
                        <span class="fas fa-building me-1 text-primary"></span>{{ $branch->name }}
                        @if($branch->is_head_office)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1 fs-11">Head Office</span>
                        @endif
                    </div>
                    <div class="fs-10 text-800">
                        <span class="fas fa-map-marker-alt me-1 text-700"></span>{{ $branch->address ? $branch->address . ', ' : '' }}{{ $branch->city ?? ($marquee->city ?? '') }}{{ $branch->province ? ', ' . $branch->province : '' }}
                        @if($branch->phone || ($marquee->phone ?? null))
                            | <span class="fas fa-phone me-1 text-700"></span><strong class="text-900">{{ $branch->phone ?: $marquee->phone }}</strong>
                        @endif
                        @if($branch->branch_manager)
                            | <strong class="text-900">Manager:</strong> {{ $branch->branch_manager }}
                        @endif
                    </div>
                @else
                    <div class="fs-10 text-800">
                        {{ $marquee->address ?? 'Main Boulevard, Gulberg' }}, {{ $marquee->city ?? 'Lahore' }}
                        @if($marquee->phone ?? null) | <span class="fas fa-phone me-1 text-700"></span><strong class="text-900">{{ $marquee->phone }}</strong> @endif
                    </div>
                @endif
            </div>
            <!-- Receipt Voucher Reference -->
            <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                <h3 class="text-success fw-bolder mb-1">PAYMENT RECEIPT</h3>
                <div class="fs-10 font-monospace text-800 fw-semibold">RECEIPT VOUCHER: <span class="text-1000 fw-bold">#REC-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</span></div>
                <div class="fs-10 font-monospace text-800 fw-semibold">BOOKING REFERENCE: <span class="text-1000 fw-bold">#{{ $booking->booking_number }}</span></div>
                {{--@if($branch)
                    <div class="fs-10 text-800 font-monospace">Branch: {{ $branch->name }}</div>
                @endif--}}
            </div>
        </div>

        <hr class="border-300 opacity-75 my-2" />

        <!-- Meta Grid: Customer & Booking Info -->
        <div class="row g-3 my-1">
            <div class="col-sm-6">
                <span class="text-900 fw-bold d-block text-uppercase fs-10 mb-2 pb-1 border-bottom border-200">
                    <span class="fas fa-user me-1 text-primary"></span> Payer / Customer Details
                </span>
                @if($booking->customer)
                    <table class="table table-sm table-borderless fs-10 mb-0">
                        <tr>
                            <td class="text-800 fw-semibold px-0 py-1" style="width: 120px;">Full Name:</td>
                            <td class="text-1000 fw-bold px-0 py-1">{{ $booking->customer->full_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-800 fw-semibold px-0 py-1">Contact:</td>
                            <td class="text-1000 fw-bold font-monospace px-0 py-1">{{ $booking->customer->phone_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-800 fw-semibold px-0 py-1">Email:</td>
                            <td class="text-900 px-0 py-1">{{ $booking->customer->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-800 fw-semibold px-0 py-1">CNIC / NTN:</td>
                            <td class="text-900 font-monospace px-0 py-1">
                                {{ $booking->customer->cnic_national_id ?? '—' }} 
                                @if($booking->customer->ntn_number)
                                    / {{ $booking->customer->ntn_number }}
                                @endif
                            </td>
                        </tr>
                    </table>
                @else
                    <p class="text-700 fs-10 mb-0">No customer detail attached.</p>
                @endif
            </div>

            <div class="col-sm-6">
                <span class="text-900 fw-bold d-block text-uppercase fs-10 mb-2 pb-1 border-bottom border-200">
                    <span class="fas fa-calendar-alt me-1 text-primary"></span> Booking & Event Details
                </span>
                <table class="table table-sm table-borderless fs-10 mb-0">
                    <tr>
                        <td class="text-800 fw-semibold px-0 py-1" style="width: 120px;">Event Date:</td>
                        <td class="text-1000 fw-bold px-0 py-1">{{ $booking->booking_date->format('l, F d, Y') }}</td>
                    </tr>  
                
               {{-- @if($branch)
                        <tr>
                            <td class="text-800 fw-semibold px-0 py-1" style="width: 120px;">Branch:</td>
                            <td class="text-1000 fw-bold px-0 py-1">{{ $branch->name }}</td>
                        </tr>
                    @endif--}}
                    <tr>
                        <td class="text-800 fw-semibold px-0 py-1">Booking Hall(s):</td>
                        <td class="text-1000 fw-bold px-0 py-1">
                            @if($booking->halls->isNotEmpty())
                                {{ $booking->halls->pluck('hall_name')->implode(', ') }}
                            @else
                                {{ $booking->hall->hall_name ?? '—' }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-800 fw-semibold px-0 py-1">Shift Slot:</td>
                        <td class="text-1000 fw-bold px-0 py-1">{{ $booking->slot->slot_name ?? 'Custom Shift' }}</td>
                    </tr>
                    <tr>
                        <td class="text-800 fw-semibold px-0 py-1">Timings:</td>
                        <td class="text-1000 font-monospace fw-bold px-0 py-1">
                            {{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-800 fw-semibold px-0 py-1">Guest Count:</td>
                        <td class="text-1000 fw-bold px-0 py-1">
                            @if($booking->effective_guest_count > 0)
                                <span class="fs-9 fw-bolder">{{ number_format($booking->effective_guest_count) }}</span> Persons
                                @if($booking->is_guest_confirmed)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-11 ms-1">Confirmed</span>
                                @elseif($booking->tentative_guests)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-11 ms-1">Tentative</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-800 fw-semibold px-0 py-1">Event Type:</td>
                        <td class="text-900 fw-semibold px-0 py-1">{{ $booking->eventType->event_type_name ?? '—' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Particulars -->
        <div class="my-2">
            <span class="text-900 fw-bold d-block text-uppercase fs-10 mb-1 pb-1 border-bottom border-200">
                <span class="fas fa-money-check-alt me-1 text-primary"></span> Payment Particulars
            </span>
            <div class="table-responsive">
                <table class="table table-sm table-striped border border-300 fs-10 align-middle mb-0">
                    <thead class="bg-200 text-1000">
                        <tr>
                            <th class="ps-3 py-1 text-900 fw-bold">Payment Date</th>
                            <th class="py-1 text-900 fw-bold">Method</th>
                            <th class="py-1 text-900 fw-bold">Transaction Reference</th>
                            <th class="py-1 text-900 fw-bold">Recorded By</th>
                            <th class="py-1 text-900 fw-bold">Notes / Memo</th>
                            <th class="text-end pe-3 py-1 text-900 fw-bold" style="width: 180px;">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="ps-3 py-1 fw-bold text-1000">{{ $payment->payment_date->format('F d, Y') }}</td>
                            <td class="py-1"><span class="badge bg-success-subtle text-success border border-success-subtle fs-11 fw-bold">{{ $payment->payment_method }}</span></td>
                            <td class="py-1 font-monospace text-900 fw-semibold">{{ $payment->transaction_reference ?? '—' }}</td>
                            <td class="py-1 text-900">{{ $payment->recorder->name ?? 'Staff User' }}</td>
                            <td class="py-1 text-800">{{ $payment->notes ?? '—' }}</td>
                            <td class="py-1 text-end pe-3 font-monospace fw-black text-success fs-9">Rs. {{ number_format($payment->amount, 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Amount in Words & Financial Summary -->
        <div class="row align-items-center g-2 my-2">
            <div class="col-sm-7">
                <div class="p-2 border border-300 rounded bg-light">
                    <span class="text-800 fw-bold d-block text-uppercase fs-11 mb-1">
                        <span class="fas fa-quote-left me-1 text-primary"></span>Amount in Words
                    </span>
                    <div class="fs-10 fw-bold text-1000 font-monospace">
                        {{ $payment->amount_in_words }}
                    </div>
                </div>
            </div>
            <div class="col-sm-5">
                <div class="p-2 border border-300 rounded bg-light text-end">
                    <span class="text-800 fw-bold d-block text-uppercase fs-11 mb-1">Total Paid Amount</span>
                    <div class="fs-8 fw-black text-success font-monospace">
                        Rs. {{ number_format($payment->amount, 0) }}
                    </div>
                </div>
            </div>
        </div>

        <hr class="border-300 opacity-75 my-2" />

        <!-- Signatures layout -->
        <div class="row justify-content-between align-items-end mt-4 pt-2">
            <div class="col-sm-5 text-center">
                <hr class="mb-1 border-400 opacity-100" />
                <span class="fs-10 fw-bold text-800">Accountant/Cashier Signature</span>
            </div>
            <div class="col-sm-5 text-center">
                <hr class="mb-1 border-400 opacity-100" />
                <span class="fs-10 fw-bold text-800">Received By (Booking Officer)</span>
            </div>
        </div>
    </div>

    <!-- Print Custom Stylesheet Injection -->
    <style>
        #printable-receipt {
            color: #232e3c;
            font-size: 0.8333333333rem;
        }
        #printable-receipt table {
            color: #232e3c;
        }
        #printable-receipt .table-borderless td {
            color: #232e3c;
            padding-top: 2px;
            padding-bottom: 2px;
        }
        #printable-receipt .table-striped > tbody > tr:nth-of-type(odd) > * {
            --falcon-table-bg-type: rgba(0, 0, 0, 0.02);
            color: #232e3c;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 12mm 8mm 12mm !important;
            }
            html, body {
                width: 100% !important;
                height: auto !important;
                background: #fff !important;
                color: #000 !important;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .main, .content, .container, .container-fluid {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #printable-receipt {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .card, table, tr, td, th, .row {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            hr {
                margin-top: 0.35rem !important;
                margin-bottom: 0.35rem !important;
            }
            .my-1, .my-2, .my-3, .my-4 {
                margin-top: 0.25rem !important;
                margin-bottom: 0.25rem !important;
            }
            .mb-2, .mb-4 {
                margin-bottom: 0.25rem !important;
            }
            .mt-2, .mt-3, .mt-4, .mt-5 {
                margin-top: 0.6rem !important;
            }
            .pt-2, .pt-4 {
                padding-top: 0.15rem !important;
            }
            .p-2, .p-3, .p-4, .p-md-5 {
                padding: 0.35rem 0.5rem !important;
            }
            table td, table th {
                padding-top: 2px !important;
                padding-bottom: 2px !important;
            }
            #printable-receipt * {
                color: #000 !important;
            }
            #printable-receipt .text-success {
                color: #00753a !important;
            }
            #printable-receipt .text-primary {
                color: #1a5cb8 !important;
            }
            #printable-receipt .badge {
                border: 1px solid #444 !important;
                color: #000 !important;
                background-color: transparent !important;
            }
            nav, .navbar, .navbar-vertical, .d-print-none, footer, .footer {
                display: none !important;
            }
        }
    </style>
</div>
@endsection
