<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking Invoice - #{{ $booking->booking_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .invoice-box {
            max-width: 100%;
            margin: auto;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
        }
        .header-table td {
            vertical-align: top;
        }
        .title-brand {
            font-size: 20px;
            color: #0056b3;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .title-invoice {
            font-size: 16px;
            color: #28a745;
            font-weight: bold;
            text-align: right;
            margin: 0;
            text-transform: uppercase;
        }
        .ref-text {
            font-family: monospace;
            font-size: 10px;
            color: #666;
            text-align: right;
        }
        .details-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .details-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px 0 0;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #777;
            border-bottom: 1px solid #ddd;
            margin-bottom: 5px;
            padding-bottom: 3px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 0;
            font-size: 10px;
        }
        .meta-label {
            color: #666;
            font-weight: bold;
            width: 100px;
        }
        .meta-value {
            color: #111;
            font-weight: bold;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .item-table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 6px;
            font-weight: bold;
            text-align: left;
            font-size: 10px;
            color: #495057;
        }
        .item-table td {
            border: 1px solid #dee2e6;
            padding: 6px;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .menu-instructions {
            width: 100%;
            margin-bottom: 20px;
        }
        .menu-instructions td {
            width: 50%;
            vertical-align: top;
        }
        .menu-box {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
            background-color: #fcfcfc;
            min-height: 100px;
            margin-right: 10px;
        }
        .instructions-box {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
            background-color: #fcfcfc;
            min-height: 100px;
        }
        .menu-list {
            margin: 0;
            padding-left: 15px;
            font-size: 10px;
        }
        .menu-list li {
            margin-bottom: 3px;
        }
        .summary-wrapper {
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-wrapper td {
            vertical-align: top;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 4px 8px;
            font-size: 10px;
        }
        .summary-label {
            text-align: right;
            color: #555;
        }
        .summary-value {
            text-align: right;
            font-weight: bold;
            width: 120px;
        }
        .total-row td {
            border-top: 1px solid #333;
            border-bottom: 2px double #333;
            font-size: 12px;
            font-weight: bold;
            color: #0056b3;
        }
        .ledger-section {
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .terms-box {
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 20px;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
        }
        .signature-table td {
            width: 45%;
            text-align: center;
            font-size: 10px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-bottom: 5px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    @php
        $isFinal = !empty($booking->finalBill);
        $billing = $isFinal ? $booking->finalBill : $booking;
        $addonsList = $isFinal ? $booking->finalBill->extraServices : $booking->extraServices;
        $totalPaid = $booking->payments->sum('amount');
        $balanceDue = max(0, $billing->grand_total - $totalPaid);
        $marquee = $booking->effective_marquee ?? $booking->marquee ?? null;
        $branch = $booking->effective_branch ?? $booking->branch ?? ($booking->hall?->branch ?? null);
    @endphp
    <div class="invoice-box">
        <!-- Header Info -->
        <table class="header-table">
            <tr>
                <td>
                    <div class="title-brand">{{ $marquee->name ?? 'Royal Event Marquee' }}</div>
                    @if($branch)
                        <div style="font-size: 11px; font-weight: bold; color: #444; text-transform: uppercase; margin-top: 2px;">
                            {{ $branch->name }} @if($branch->is_head_office) (Head Office) @endif
                        </div>
                        <div style="font-size: 10px; color: #666; margin-top: 2px;">
                            {{ $branch->address ? $branch->address . ', ' : '' }}{{ $branch->city ?? ($marquee->city ?? '') }}{{ $branch->province ? ', ' . $branch->province : '' }}
                            @if($branch->phone || ($marquee->phone ?? null)) | Ph: {{ $branch->phone ?: $marquee->phone }} @endif
                            @if($branch->branch_manager) | Mgr: {{ $branch->branch_manager }} @endif
                        </div>
                    @else
                        <div style="font-size: 10px; color: #666; margin-top: 2px;">
                            {{ $marquee->address ?? 'Main Boulevard' }}, {{ $marquee->city ?? 'Lahore' }}
                            @if($marquee->phone ?? null) | Ph: {{ $marquee->phone }} @endif
                        </div>
                    @endif
                </td>
                <td>
                    <div class="title-invoice">{{ $isFinal ? 'Final Invoice' : 'Invoice' }}</div>
                    <div class="ref-text">
                        <strong>Invoice Ref:</strong> #INV-{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}<br>
                        <strong>Booking Number:</strong> #{{ $booking->booking_number }}<br>
                        @if($branch)
                            <strong>Branch:</strong> {{ $branch->name }}<br>
                        @endif
                        <strong>Invoice Type:</strong> {{ $isFinal ? 'Actual Event-Day' : 'Contract Original' }}<br>
                        <strong>Date Generated:</strong> {{ now()->format('Y-m-d') }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- Customer & Event Details -->
        <table class="details-table">
            <tr>
                <td>
                    <div class="section-title">Customer / Host Details</div>
                    @if($booking->customer)
                        <table class="meta-table">
                            <tr>
                                <td class="meta-label">Full Name:</td>
                                <td class="meta-value">{{ $booking->customer->full_name }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Contact:</td>
                                <td class="meta-value">{{ $booking->customer->phone_number }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Email:</td>
                                <td>{{ $booking->customer->email ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">CNIC / ID:</td>
                                <td>{{ $booking->customer->cnic_national_id ?? '—' }}</td>
                            </tr>
                        </table>
                    @else
                        <p style="color: #888; font-style: italic; margin: 0;">No customer profile linked.</p>
                    @endif
                </td>
                <td>
                    <div class="section-title">Event details</div>
                    <table class="meta-table">
                        @if($branch)
                            <tr>
                                <td class="meta-label">Branch:</td>
                                <td class="meta-value">{{ $branch->name }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="meta-label">Event Date:</td>
                            <td class="meta-value">{{ $booking->booking_date->format('l, F d, Y') }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Hall / Venue:</td>
                            <td class="meta-value">
                                @if($booking->halls->isNotEmpty())
                                    {{ $booking->halls->pluck('hall_name')->implode(', ') }}
                                @else
                                    {{ $booking->hall->hall_name ?? '—' }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="meta-label">Shift / Timings:</td>
                            <td class="meta-value">{{ $booking->slot->slot_name ?? 'Custom' }} ({{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }})</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Event Type:</td>
                            <td>{{ $booking->eventType->event_type_name ?? '—' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Invoice Charges Table -->
        <div class="section-title">Itemized Billing details</div>
        <table class="item-table">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Charge Description</th>
                    <th class="text-right" style="width: 100px;">Rate</th>
                    <th class="text-center" style="width: 80px;">Qty / Guests</th>
                    <th class="text-right" style="width: 120px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $rowNo = 1; @endphp
                @if(!$booking->no_food)
                    <tr>
                        <td class="text-center">{{ $rowNo++ }}</td>
                        <td>
                            <strong>{{ $booking->package->package_name ?? 'Catering Plan' }}</strong>
                            <div style="font-size: 9px; color: #777;">Per Head Menu Package</div>
                        </td>
                        <td class="text-right font-monospace">Rs. {{ number_format($billing->per_plate_price, 2) }}</td>
                        <td class="text-center">{{ $billing->guest_count }}</td>
                        <td class="text-right font-monospace">Rs. {{ number_format($billing->package_amount, 2) }}</td>
                    </tr>
                @endif
                @if($billing->hall_charges > 0)
                    <tr>
                        <td class="text-center">{{ $rowNo++ }}</td>
                        <td>
                            <strong>Hall Rent / Setup Charges</strong>
                            <div style="font-size: 9px; color: #777;">Exclusive banquet hall rental fee</div>
                        </td>
                        <td class="text-right font-monospace">Rs. {{ number_format($billing->hall_charges, 2) }}</td>
                        <td class="text-center">1</td>
                        <td class="text-right font-monospace">Rs. {{ number_format($billing->hall_charges, 2) }}</td>
                    </tr>
                @endif
                @if($billing->extra_charges > 0)
                    <tr>
                        <td class="text-center">{{ $rowNo++ }}</td>
                        <td>
                            <strong>Extra Services & Add-ons</strong>
                            <div style="font-size: 9px; color: #777;">
                                @if($isFinal)
                                    Consumed: {{ $addonsList->pluck('service_name')->implode(', ') }}
                                @else
                                    Custom decor setup, lights, sound or stage amenities
                                @endif
                            </div>
                        </td>
                        <td class="text-right font-monospace">Rs. {{ number_format($billing->extra_charges, 2) }}</td>
                        <td class="text-center">1</td>
                        <td class="text-right font-monospace">Rs. {{ number_format($billing->extra_charges, 2) }}</td>
                    </tr>
                @endif
                @if($booking->vendorSales && $booking->vendorSales->isNotEmpty())
                    @foreach($booking->vendorSales as $vSale)
                        @if($vSale->status !== 'cancelled')
                            @php
                                $custCharge = (float) $vSale->sale_amount;
                                $custAdv = (float) $vSale->customer_paid;
                                $custRem = (float) $vSale->customer_remaining;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $rowNo++ }}</td>
                                <td>
                                    <strong>{{ $vSale->service->service_name ?? 'Specialized Service' }}</strong>
                                    <div style="font-size: 9px; color: #777;">
                                        Partner: {{ $vSale->vendor->name ?? 'Service Provider' }} ({{ $vSale->vendor->vendor_type ?? 'Vendor' }})
                                        @if(!$vSale->include_in_invoice)
                                            — <span style="color: #856404; font-weight: bold;">Direct Payment by Customer (Excluded from Invoice Total)</span>
                                        @elseif($custAdv > 0)
                                            — <span style="color: #28a745; font-weight: bold;">Advance Paid: Rs. {{ number_format($custAdv, 2) }}</span> (Net Due: Rs. {{ number_format($custRem, 2) }})
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right font-monospace">
                                    @if($vSale->include_in_invoice)
                                        Rs. {{ number_format($custCharge, 2) }}
                                    @else
                                        <span style="color: #888;">(Direct Pay)</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ (int)$vSale->quantity ?: 1 }}</td>
                                <td class="text-right font-monospace">
                                    @if($vSale->include_in_invoice)
                                        Rs. {{ number_format($custCharge, 2) }}
                                    @else
                                        <span style="color: #888;">Rs. 0.00</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endif
            </tbody>
        </table>

        <!-- Billing Summary Mathematics -->
        <table class="summary-wrapper">
            <tr>
                <td style="width: 50%;">
                    <!-- Security Deposit Note -->
                    <div style="border-left: 3px solid #17a2b8; padding-left: 10px; margin-top: 10px;">
                        <span style="font-weight: bold; color: #17a2b8; font-size: 10px;">SECURITY DEPOSIT STATUS</span>
                        <div style="font-size: 9px; color: #555; margin-top: 2px;">
                            <strong>Amount Held:</strong> Rs. {{ number_format($booking->security_deposit, 2) }}<br>
                            <strong>Status:</strong> 
                            @if($booking->deposit_status === 'Refunded')
                                Refunded (Released in Full)
                            @elseif($booking->deposit_status === 'Deducted')
                                Deducted (Rs. {{ number_format($booking->deposit_deducted_amount, 2) }} charged for damages/loss)
                            @else
                                Held (Refundable post-event, excluded from direct revenue)
                            @endif
                        </div>
                    </div>
                </td>
                <td style="width: 50%;">
                    <table class="summary-table">
                        @if(!$booking->no_food)
                            <tr>
                                <td class="summary-label">Package Charges:</td>
                                <td class="summary-value">Rs. {{ number_format($billing->package_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($billing->hall_charges > 0)
                            <tr>
                                <td class="summary-label">Hall Setup Charges:</td>
                                <td class="summary-value">Rs. {{ number_format($billing->hall_charges, 2) }}</td>
                            </tr>
                        @endif
                        @if($billing->extra_charges > 0)
                            <tr>
                                <td class="summary-label">Add-ons Subtotal:</td>
                                <td class="summary-value">Rs. {{ number_format($billing->extra_charges, 2) }}</td>
                            </tr>
                        @endif
                        @php
                            $invoicedVendorSalesSum = $booking->vendorSales->where('status', '!=', 'cancelled')->where('include_in_invoice', true)->sum('sale_amount');
                        @endphp
                        @if($invoicedVendorSalesSum > 0)
                            <tr>
                                <td class="summary-label">Service Providers (Billed):</td>
                                <td class="summary-value">Rs. {{ number_format($invoicedVendorSalesSum, 2) }}</td>
                            </tr>
                        @endif
                        @if($billing->discount_amount > 0)
                            <tr style="color: red;">
                                <td class="summary-label">Discount Applied:</td>
                                <td class="summary-value">- Rs. {{ number_format($billing->discount_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="summary-label" style="font-weight: bold; border-top: 1px solid #dee2e6;">Subtotal:</td>
                            <td class="summary-value" style="border-top: 1px solid #dee2e6;">Rs. {{ number_format($billing->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label">Tax Amount:</td>
                            <td class="summary-value">Rs. {{ number_format($billing->tax_amount, 2) }}</td>
                        </tr>
                        @if($booking->security_deposit > 0)
                            <tr style="color: #0c5460;">
                                <td class="summary-label">Refundable Security Deposit:</td>
                                <td class="summary-value">Rs. {{ number_format($booking->security_deposit, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td class="summary-label">Grand Total:</td>
                            <td class="summary-value">Rs. {{ number_format($billing->grand_total, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Payments Tracker History Ledger -->
        <div class="ledger-section">
            <div class="section-title">Payment History Ledger (Installment Tracker)</div>
            <table class="item-table" style="margin-bottom: 5px;">
                <thead>
                    <tr>
                        <th style="width: 100px;">Payment Date</th>
                        <th style="width: 120px;">Payment Method</th>
                        <th>Reference / Cheque #</th>
                        <th class="text-right" style="width: 140px;">Amount Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalPaid = 0; @endphp
                    @forelse($booking->payments as $payment)
                        @php $totalPaid += $payment->amount; @endphp
                        <tr>
                            <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td><span class="badge badge-info">{{ $payment->payment_method }}</span></td>
                            <td style="font-family: monospace;">{{ $payment->transaction_reference ?? '—' }}</td>
                            <td class="text-right font-monospace fw-bold" style="color: green;">Rs. {{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center" style="color: #666; font-style: italic;">No payments recorded for this booking ledger.</td>
                        </tr>
                    @endforelse
                    
                    <tr style="background-color: #f1f8f5; font-weight: bold;">
                        <td colspan="3" class="text-right">Total Payments Collected:</td>
                        <td class="text-right font-monospace" style="color: green;">Rs. {{ number_format($totalPaid, 2) }}</td>
                    </tr>
                    <tr style="background-color: #fff3cd; font-weight: bold;">
                        <td colspan="3" class="text-right">Remaining Balance Outstanding:</td>
                        <td class="text-right font-monospace" style="color: #856404;">
                            Rs. {{ number_format($balanceDue, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin-top: 5px; font-size: 10px;">
                <strong>Payment Status:</strong> 
                @if($booking->payment_status === 'Paid')
                    <span class="badge badge-success" style="font-size: 9px; padding: 2px 6px;">Paid</span>
                @elseif($booking->payment_status === 'Partially Paid')
                    <span class="badge badge-warning" style="font-size: 9px; padding: 2px 6px;">Partially Paid</span>
                @else
                    <span class="badge badge-danger" style="font-size: 9px; padding: 2px 6px;">Unpaid</span>
                @endif
            </div>
        </div>

        <!-- Signature Lines --><br>
        <table class="signature-table" style="page-break-inside: avoid;">
            <tr> 
                <td>
                    <div class="signature-line"></div>
                    Received By (Authorized Officer Stamp & Sign)
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
