<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier Ledger - {{ $supplier->name }}</title>
    <style>
        @page {
            margin: 25px 30px 35px 30px;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10.5px;
            line-height: 1.4;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2.5px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 3px 0;
        }
        .company-subtitle {
            font-size: 9.5px;
            color: #475569;
            margin: 0;
            line-height: 1.35;
        }
        .statement-title {
            font-size: 16px;
            font-weight: bold;
            color: #0284c7;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 3px 0;
        }
        .statement-meta {
            font-size: 9.5px;
            color: #475569;
            text-align: right;
            line-height: 1.4;
        }
        .info-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 8px 10px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
        }
        .info-label {
            font-size: 8.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 11.5px;
            font-weight: bold;
            color: #0f172a;
        }
        .balance-card {
            background-color: #fef2f2 !important;
            border: 1px solid #fecaca !important;
        }
        .balance-amount {
            font-size: 15px;
            font-weight: bold;
            color: #dc2626;
            margin-top: 2px;
        }
        .ledger-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .ledger-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 7px 6px;
            border: 1px solid #1e3a8a;
        }
        .ledger-table td {
            padding: 6px 6px;
            border: 1px solid #e2e8f0;
            font-size: 9.5px;
            word-wrap: break-word;
        }
        .ledger-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
            font-size: 9.5px;
        }
        .text-success {
            color: #15803d;
        }
        .text-danger {
            color: #b91c1c;
        }
        .fw-bold {
            font-weight: bold;
        }
        .badge-type {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: bold;
            background-color: #e2e8f0;
            color: #334155;
        }
        .summary-container {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .summary-container td {
            vertical-align: top;
            padding: 0;
        }
        .summary-box {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }
        .summary-box td {
            padding: 6px 10px;
            font-size: 10px;
            border-bottom: 1px solid #cbd5e1;
        }
        .summary-box tr.highlight {
            background-color: #f1f5f9;
            font-size: 11.5px;
            font-weight: bold;
        }
        .footer {
            clear: both;
            margin-top: 25px;
            font-size: 8.5px;
            color: #94a3b8;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
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
            background: #f1f5f9;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-block;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-primary {
            background-color: #1e3a8a;
            color: #fff;
        }
        .btn-secondary {
            background-color: #64748b;
            color: #fff;
        }
    </style>
</head>
<body>

    @if(!isset($isPdf) || !$isPdf)
        <div class="print-btn-bar no-print">
            <span style="font-weight: bold; color: #1e293b; font-size: 13px;">Print Preview: Supplier Ledger Statement</span>
            <div>
                <button onclick="window.print()" class="btn btn-primary">Print Statement</button>
                <button onclick="window.close()" class="btn btn-secondary">Close Window</button>
            </div>
        </div>
    @endif

    <table class="header-table">
        <tr>
            <td style="width: 58%;">
                <h1 class="company-title">{{ $marquee->name ?? config('app.name', 'Marquee CMS') }}</h1>
                <p class="company-subtitle">
                    {{ $marquee->address ?? 'Central Office' }}<br>
                    Phone: {{ $marquee->contact_phone ?? '—' }} &bull; Email: {{ $marquee->email ?? '—' }}
                </p>
            </td>
            <td style="width: 42%;">
                <h2 class="statement-title">Supplier Ledger Statement</h2>
                <div class="statement-meta">
                    <strong>Generated Date:</strong> {{ date('d M, Y h:i A') }}<br>
                    <strong>Supplier Code:</strong> <span class="font-mono fw-bold">{{ $supplier->supplier_code }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width: 35%;">
                <div class="info-label">Supplier / Vendor</div>
                <div class="info-value">{{ $supplier->name }}</div>
                @if($supplier->contact_person)
                    <div style="font-size: 9.5px; color: #475569; margin-top: 2px;">Attn: {{ $supplier->contact_person }}</div>
                @endif
            </td>
            <td style="width: 35%;">
                <div class="info-label">Contact & Location</div>
                <div class="info-value" style="font-size: 10.5px;">{{ $supplier->mobile_number }}</div>
                @if($supplier->email)
                    <div style="font-size: 9px; color: #475569;">{{ $supplier->email }}</div>
                @endif
                <div style="font-size: 9px; color: #475569;">{{ $supplier->city ?: 'No City' }}</div>
            </td>
            <td style="width: 30%;" class="balance-card">
                <div class="info-label" style="color: #991b1b;">Current Outstanding Balance</div>
                <div class="balance-amount font-mono">
                    Rs. {{ number_format($supplier->current_balance, 2) }}
                </div>
                <div style="font-size: 8.5px; color: #7f1d1d; margin-top: 1px;">(Payable Liability)</div>
            </td>
        </tr>
    </table>

    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 15%;">Voucher #</th>
                <th style="width: 14%;">Type</th>
                <th style="width: 27%;">Description</th>
                <th style="width: 10%;" class="text-end">Debit (Paid)</th>
                <th style="width: 10%;" class="text-end">Credit (Billed)</th>
                <th style="width: 12%;" class="text-end">Balance</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totDebit = 0;
                $totCredit = 0;
            @endphp
            @forelse($ledgers as $row)
                @php
                    $totDebit += (float)$row->debit;
                    $totCredit += (float)$row->credit;
                @endphp
                <tr>
                    <td class="font-mono text-center">{{ $row->transaction_date->format('Y-m-d') }}</td>
                    <td class="font-mono fw-bold">{{ $row->voucher_no ?: '—' }}</td>
                    <td>
                        <span class="badge-type">{{ $row->reference_type }}</span>
                    </td>
                    <td>{{ $row->description }}</td>
                    <td class="text-end font-mono text-success fw-bold">
                        {{ $row->debit > 0 ? number_format($row->debit, 2) : '—' }}
                    </td>
                    <td class="text-end font-mono text-dark fw-bold">
                        {{ $row->credit > 0 ? number_format($row->credit, 2) : '—' }}
                    </td>
                    <td class="text-end font-mono fw-bold text-danger">
                        {{ number_format($row->running_balance, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">No transaction history found in this supplier ledger.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-container">
        <tr>
            <td style="width: 55%; padding-right: 20px; vertical-align: bottom;">
                <div style="padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 9px; color: #64748b;">
                    <strong>Note:</strong> Debit amounts represent vendor payments and purchase returns. Credit amounts represent billed purchase invoices.
                </div>
            </td>
            <td style="width: 45%;">
                <table class="summary-box">
                    <tr>
                        <td>Total Invoiced (Credit):</td>
                        <td class="text-end font-mono fw-bold">Rs. {{ number_format($totCredit, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total Paid / Ret (Debit):</td>
                        <td class="text-end font-mono fw-bold text-success">Rs. {{ number_format($totDebit, 2) }}</td>
                    </tr>
                    <tr class="highlight">
                        <td>Closing Balance Payable:</td>
                        <td class="text-end font-mono text-danger">Rs. {{ number_format($supplier->current_balance, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        This is a computer-generated statement and does not require a physical signature.<br>
        Extracted from {{ $marquee->name ?? config('app.name', 'Marquee CMS') }} system on {{ date('d M, Y h:i A') }}.
    </div>

</body>
</html>
