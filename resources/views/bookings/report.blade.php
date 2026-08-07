<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Report - {{ $marquee->name ?? 'Marquee CMS' }}</title>
    
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/theme.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/user.css') }}" rel="stylesheet">

    <style>
        body {
            background-color: #fff;
            color: #333;
            font-size: 11px;
            padding: 20px;
        }
        .report-header {
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .table th, .table td {
            padding: 6px 8px !important;
            vertical-align: middle;
        }
        .summary-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 12px;
            text-align: center;
        }
        .summary-card h4 {
            margin-bottom: 5px;
            font-weight: 700;
        }
        .summary-card p {
            margin-bottom: 0;
            color: #6c757d;
            font-size: 10px;
            text-transform: uppercase;
        }
        @media print {
            .d-print-none {
                display: none !important;
            }
            body {
                padding: 0;
            }
            @page {
                size: A4 landscape;
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Action Toolbar -->
        <div class="row mb-4 d-print-none justify-content-between align-items-center bg-light p-3 rounded border">
            <div>
                <h5 class="mb-0 text-900"><span class="fas fa-file-invoice-dollar me-2 text-primary"></span>Booking Report Viewer</h5>
                <p class="mb-0 fs-11 text-600">This page is pre-formatted for landscape printing and saving as PDF.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-primary btn-sm">
                    <span class="fas fa-print me-1"></span>Print / Save PDF
                </button>
                <button onclick="window.close()" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-times me-1"></span>Close
                </button>
            </div>
        </div>

        <!-- Report Header -->
        <div class="report-header">
            <div class="row justify-content-between align-items-end">
                <div class="col-auto">
                    <h2 class="mb-1 text-primary fw-bold">{{ $marquee->name ?? 'Marquee Banquet Hall' }}</h2>
                    <p class="mb-0 text-600">
                        <span class="fas fa-map-marker-alt me-1"></span>{{ $marquee->address ?? '' }}, {{ $marquee->city ?? '' }}
                        @if($marquee->phone) | <span class="fas fa-phone me-1"></span>{{ $marquee->phone }} @endif
                    </p>
                </div>
                <div class="col-auto text-end">
                    <h4 class="mb-1 text-900 fw-semi-bold">Bookings Summary Report</h4>
                    <p class="mb-0 text-600 font-monospace fs-11">Generated on: {{ now()->format('d-M-Y h:i A') }}</p>
                </div>
            </div>
            
            <!-- Filters Summary -->
            <div class="mt-3 bg-light p-2 rounded border fs-11 text-700">
                <strong>Report Filters:</strong>
                <span class="mx-2">|</span> Search: <code>{{ request('search') ?: 'All' }}</code>
                <span class="mx-2">|</span> Status: <span class="badge bg-secondary text-dark">{{ request('filterStatus') ?: 'All' }}</span>
                <span class="mx-2">|</span> Payment: <span class="badge bg-secondary text-dark">{{ request('filterPaymentStatus') ?: 'All' }}</span>
                <span class="mx-2">|</span> Date Range: <code>{{ request('filterDateStart') ?: 'Any' }}</code> to <code>{{ request('filterDateEnd') ?: 'Any' }}</code>
            </div>
        </div>

        <!-- Metrics Overview -->
        @php
            $totalBookings = $bookings->count();
            $totalGuests = $bookings->sum(fn($b) => $b->effective_guest_count);
            $tentativeGuestsSum = $bookings->sum(fn($b) => $b->tentative_guests ?? $b->guest_count);
            $confirmedGuestsSum = $bookings->sum(fn($b) => $b->confirmed_guests ?? 0);
            $totalAmount = $bookings->sum('grand_total');
            $receivedAmount = $bookings->sum(fn($b) => $b->payments->sum('amount'));
            $balanceAmount = max(0.00, $totalAmount - $receivedAmount);
        @endphp
        <div class="row g-3 mb-4">
            <div class="col">
                <div class="summary-card">
                    <h4 class="text-primary">{{ number_format($totalBookings) }}</h4>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="col">
                <div class="summary-card">
                    <h4 class="text-info">{{ number_format($tentativeGuestsSum) }}</h4>
                    <p>Tentative Guests</p>
                </div>
            </div>
            <div class="col">
                <div class="summary-card">
                    <h4 class="text-success">{{ number_format($confirmedGuestsSum) }}</h4>
                    <p>Confirmed Guests</p>
                </div>
            </div>
            <div class="col">
                <div class="summary-card">
                    <h4 class="text-success">Rs. {{ number_format($totalAmount, 2) }}</h4>
                    <p>Total Amount</p>
                </div>
            </div>
            <div class="col">
                <div class="summary-card">
                    <h4 class="text-warning">Rs. {{ number_format($receivedAmount, 2) }}</h4>
                    <p>Received Amount</p>
                </div>
            </div>
            <div class="col">
                <div class="summary-card">
                    <h4 class="text-danger">Rs. {{ number_format($balanceAmount, 2) }}</h4>
                    <p>Balance Amount</p>
                </div>
            </div>
        </div>

        <!-- Bookings Table -->
        <table class="table table-sm table-striped table-bordered align-middle">
            <thead class="bg-300 text-900 font-sans-serif">
                <tr>
                    <th style="width: 80px;">Booking #</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Event Type</th>
                    <th>Hall / Venue</th>
                    <th>Shift / Slot</th>
                    <th style="width: 80px;">Event Date</th>
                    <th class="text-end" style="width: 70px;">Guests</th>
                    <th class="text-end" style="width: 90px;">Per Head</th>
                    <th class="text-end" style="width: 100px;">Total</th>
                    <th class="text-end" style="width: 100px;">Received</th>
                    <th class="text-end" style="width: 100px;">Balance</th>
                    <th class="text-center" style="width: 80px;">Status</th>
                    <th class="text-center" style="width: 80px;">Payment</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                    @php
                        $received = $booking->payments->sum('amount');
                        $balance = max(0.00, $booking->grand_total - $received);
                    @endphp
                    <tr>
                        <td class="font-monospace fw-semi-bold">{{ $booking->booking_number }}</td>
                        <td>{{ $booking->customer->full_name ?? '—' }}</td>
                        <td class="font-monospace fs-11">{{ $booking->customer->phone_number ?? '—' }}</td>
                        <td>{{ $booking->eventType->event_type_name ?? '—' }}</td>
                        <td>
                            @if($booking->halls->isNotEmpty())
                                {{ $booking->halls->pluck('hall_name')->implode(', ') }}
                            @else
                                {{ $booking->hall->hall_name ?? '—' }}
                            @endif
                        </td>
                        <td>{{ $booking->slot->slot_name ?? 'Custom Time' }}</td>
                        <td>{{ $booking->booking_date->format('d-M-Y') }}</td>
                        <td class="text-end">
                            <div>{{ number_format($booking->effective_guest_count) }}</div>
                            <small class="text-muted fs-12">T: {{ $booking->tentative_guests ?? $booking->guest_count }} | C: {{ $booking->confirmed_guests ?? '—' }}</small>
                        </td>
                        <td class="text-end font-monospace">
                            @if($booking->no_food)
                                No Food
                            @else
                                Rs. {{ number_format($booking->per_plate_price, 2) }}
                            @endif
                        </td>
                        <td class="text-end fw-semi-bold font-monospace">Rs. {{ number_format($booking->grand_total, 2) }}</td>
                        <td class="text-end font-monospace">Rs. {{ number_format($received, 2) }}</td>
                        <td class="text-end font-monospace fw-bold text-{{ $balance > 0 ? 'danger' : 'dark' }}">Rs. {{ number_format($balance, 2) }}</td>
                        <td class="text-center">
                            <span class="badge border border-{{ $booking->booking_status === 'Confirmed' ? 'success' : ($booking->booking_status === 'Cancelled' ? 'danger' : 'secondary') }} text-dark px-2 py-0.5 rounded-pill fs-11">
                                {{ $booking->booking_status }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge border border-{{ $booking->payment_status === 'Paid' ? 'success' : ($booking->payment_status === 'Partially Paid' ? 'warning' : 'danger') }} text-dark px-2 py-0.5 rounded-pill fs-11">
                                {{ $booking->payment_status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center py-4 text-muted">No bookings found matching selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- FontAwesome JS for printing symbols -->
    <script src="{{ asset('vendors/fontawesome/all.min.js') }}"></script>
    <script>
        // Auto trigger window print on load
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
