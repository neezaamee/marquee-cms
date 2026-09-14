<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Return Debit Note - {{ $return->return_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #2c3e50;
            margin: 0;
            padding: 15px;
            background: #fff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #b71c1c;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-title {
            font-size: 20px;
            font-weight: 800;
            color: #b71c1c;
            text-transform: uppercase;
            margin: 0 0 4px 0;
        }
        .company-subtitle {
            font-size: 10px;
            color: #666;
            margin: 0;
        }
        .return-title {
            font-size: 18px;
            font-weight: 800;
            color: #d32f2f;
            text-align: right;
            text-transform: uppercase;
            margin: 0 0 4px 0;
        }
        .return-meta {
            font-size: 10px;
            color: #444;
            text-align: right;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 8px 12px;
            background: #fff5f5;
            border: 1px solid #fed7d7;
        }
        .info-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #9b2c2c;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .info-value {
            font-size: 12px;
            font-weight: 600;
            color: #2c3e50;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #c53030;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 8px 10px;
            border: 1px solid #c53030;
        }
        .items-table td {
            padding: 7px 10px;
            border: 1px solid #dee2e6;
            font-size: 10px;
        }
        .items-table tr:nth-child(even) {
            background-color: #fcfcfd;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: monospace;
        }
        .fw-bold {
            font-weight: 700;
        }
        .totals-table {
            float: right;
            width: 260px;
            border: 1px solid #dee2e6;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .totals-table td {
            padding: 6px 12px;
            font-size: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .totals-table tr.grand-total {
            background-color: #fff5f5;
            font-size: 13px;
            font-weight: 800;
            color: #c53030;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: 700;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-posted {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .badge-draft {
            background-color: #e2e3e5;
            color: #41464b;
        }
        .footer-note {
            clear: both;
            padding-top: 30px;
            font-size: 9px;
            color: #888;
            border-top: 1px dashed #ccc;
            text-align: center;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
        .print-btn-bar {
            background: #eef2f7;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-block;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-danger {
            background-color: #c53030;
            color: #fff;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
        }
    </style>
</head>
<body>

    @if(!isset($isPdf) || !$isPdf)
        <div class="print-btn-bar no-print">
            <span style="font-weight: bold; color: #333;">Print Preview: Purchase Return #{{ $return->return_number }}</span>
            <div>
                <button onclick="window.print()" class="btn btn-danger">Print Debit Note</button>
                <button onclick="window.close()" class="btn btn-secondary">Close Window</button>
            </div>
        </div>
    @endif

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <h1 class="company-title">{{ $return->marquee->name ?? config('app.name', 'Marquee CMS') }}</h1>
                <p class="company-subtitle">
                    {{ $return->branch->name ?? 'Main Branch' }}<br>
                    {{ $return->branch->address ?? ($return->marquee->address ?? '') }}<br>
                    Phone: {{ $return->branch->phone ?? ($return->marquee->contact_phone ?? '—') }}
                </p>
            </td>
            <td style="width: 45%;">
                <h2 class="return-title">Purchase Return Note</h2>
                <div class="return-meta">
                    <strong>Debit Note #:</strong> <span class="font-mono" style="font-size: 12px; font-weight: bold;">{{ $return->return_number }}</span><br>
                    <strong>Return Date:</strong> {{ $return->return_date->format('d M, Y') }}<br>
                    @if($return->purchaseInvoice)
                        <strong>Against Inv #:</strong> <span class="font-mono">#{{ $return->purchaseInvoice->invoice_number }}</span><br>
                    @endif
                    <strong>Status:</strong>
                    <span class="badge {{ $return->status === 'Posted' ? 'badge-posted' : 'badge-draft' }}">
                        {{ $return->status }}
                    </span>
                    @if($return->journalVoucher)
                        <br><strong>JV #:</strong> <span class="font-mono">{{ $return->journalVoucher->voucher_no }}</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <div class="info-label">Vendor / Supplier (Debited)</div>
                <div class="info-value">{{ $return->supplier->name ?? '—' }}</div>
                <div style="font-size: 10px; color: #666; margin-top: 2px;">
                    Code: <span class="font-mono">{{ $return->supplier->supplier_code ?? '—' }}</span> | Phone: {{ $return->supplier->mobile_number ?? '—' }}<br>
                    {{ $return->supplier->address ?? '' }} {{ $return->supplier->city ?? '' }}
                </div>
            </td>
            <td style="width: 50%;">
                <div class="info-label">Return Reason / Notes</div>
                <div class="info-value">{{ $return->reason ?: 'Goods Return / Adjustment' }}</div>
                @if($return->notes)
                    <div style="font-size: 10px; color: #666; margin-top: 2px;">{{ $return->notes }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">#</th>
                <th style="width: 100px;">Item Code</th>
                <th>Item Description</th>
                <th style="width: 70px;" class="text-center">Unit</th>
                <th style="width: 90px;" class="text-end">Return Qty</th>
                <th style="width: 100px;" class="text-end">Rate (Rs.)</th>
                <th style="width: 110px;" class="text-end">Credit / Debit (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($return->details as $idx => $line)
                <tr>
                    <td class="text-center font-mono">{{ $idx + 1 }}</td>
                    <td class="font-mono fw-bold">{{ $line->item->item_code ?? '—' }}</td>
                    <td class="fw-bold">{{ $line->item->name ?? 'Item #' . $line->item_id }}</td>
                    <td class="text-center">{{ $line->item->unit->short_code ?? 'Pcs' }}</td>
                    <td class="text-end font-mono">{{ number_format($line->quantity, 2) }}</td>
                    <td class="text-end font-mono">{{ number_format($line->unit_cost, 2) }}</td>
                    <td class="text-end font-mono fw-bold">{{ number_format($line->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Items Subtotal:</td>
            <td class="text-end font-mono">Rs. {{ number_format($return->gross_amount, 2) }}</td>
        </tr>
        @if($return->tax > 0)
            <tr>
                <td>Tax:</td>
                <td class="text-end font-mono">+ Rs. {{ number_format($return->tax, 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td>Total Debit Note:</td>
            <td class="text-end font-mono">Rs. {{ number_format($return->net_amount, 2) }}</td>
        </tr>
    </table>

    <div class="footer-note">
        This is an official Purchase Return Debit Note generated from {{ $return->marquee->name ?? config('app.name', 'Marquee CMS') }}.<br>
        Date generated: {{ date('d M, Y h:i A') }}.
    </div>

</body>
</html>
