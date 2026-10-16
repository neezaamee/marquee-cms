<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Ledger Statement - {{ $marquee->name ?? config('app.name', 'Marquee CMS') }}</title>
    <style>
        @page {
            margin: 20px 25px 25px 25px;
            size: a4 portrait;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.35;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-table td { vertical-align: top; }
        .company-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .company-subtitle {
            font-size: 9px;
            color: #475569;
            margin: 0;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #0284c7;
            text-align: right;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .report-meta {
            font-size: 8.5px;
            color: #475569;
            text-align: right;
        }
        .filter-badges {
            width: 100%;
            margin-bottom: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 8.5px;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .kpi-table td {
            padding: 6px 10px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        .kpi-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            display: block;
        }
        .kpi-value {
            font-size: 13px;
            font-weight: bold;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }
        .ledger-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 4px;
            border: 1px solid #1e3a8a;
            text-align: left;
        }
        .ledger-table td {
            padding: 5px 4px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            word-wrap: break-word;
        }
        .ledger-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'DejaVu Sans Mono', monospace; }
        .text-success { color: #15803d; }
        .text-danger { color: #b91c1c; }
        .text-primary { color: #1d4ed8; }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            background: #e2e8f0;
            color: #334155;
        }
        .footer {
            clear: both;
            margin-top: 15px;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            text-align: center;
        }
        .no-print {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 8px 12px;
            margin-bottom: 12px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-block;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-primary { background: #1e3a8a; color: #fff; }
        .btn-secondary { background: #64748b; color: #fff; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    @if(!isset($isPdf) || !$isPdf)
        <div class="no-print">
            <span style="font-weight: bold; color: #1e3a8a; font-size: 12px;">Print Preview: Stock Ledger Audit Trail</span>
            <div>
                <button onclick="window.print()" class="btn btn-primary">Print Ledger</button>
                <button onclick="window.close()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    @endif

    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <h1 class="company-title">{{ $marquee->name ?? config('app.name', 'Marquee CMS') }}</h1>
                <p class="company-subtitle">
                    {{ $marquee->address ?? 'Central Warehouse & Locations' }}
                    @if(!empty($marquee->contact_phone)) &bull; Phone: {{ $marquee->contact_phone }} @endif
                </p>
            </td>
            <td style="width: 40%;">
                <h2 class="report-title">Stock Ledger Statement</h2>
                <div class="report-meta">
                    <strong>Period:</strong> {{ $dateFrom ?: 'All Time' }} to {{ $dateTo ?: 'Today' }}<br>
                    <strong>Generated:</strong> {{ date('d M, Y h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="filter-badges">
        <strong>Filters:</strong>
        Branch: <strong>{{ $branchName ?? 'All Branches' }}</strong> &bull;
        Item: <strong>{{ $itemName ?? 'All Items' }}</strong> &bull;
        Type: <strong>{{ $filterType ?: 'All Transaction Types' }}</strong>
        @if(!empty($search)) &bull; Keyword: <strong>"{{ $search }}"</strong> @endif
    </div>

    <table class="kpi-table">
        <tr>
            <td style="width: 33.3%;">
                <span class="kpi-label">Total Stock In</span>
                <span class="kpi-value text-success font-mono">{{ number_format($totalIn, 2) }}</span>
            </td>
            <td style="width: 33.3%;">
                <span class="kpi-label">Total Stock Out</span>
                <span class="kpi-value text-danger font-mono">{{ number_format($totalOut, 2) }}</span>
            </td>
            <td style="width: 33.3%; background: #e0f2fe; border-color: #7dd3fc;">
                <span class="kpi-label" style="color: #0369a1;">Total Cost Value</span>
                <span class="kpi-value text-primary font-mono">PKR {{ number_format($totalCost, 2) }}</span>
            </td>
        </tr>
    </table>

    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 65px;">Date</th>
                <th style="width: 70px;">Voucher / Ref</th>
                <th style="width: 80px;">Branch</th>
                <th style="width: 60px;">Code</th>
                <th style="width: 130px;">Item Description</th>
                <th style="width: 70px;">Type</th>
                <th style="width: 50px;" class="text-end">Qty In</th>
                <th style="width: 50px;" class="text-end">Qty Out</th>
                <th style="width: 60px;" class="text-end">Unit Cost</th>
                <th style="width: 70px;" class="text-end">Total Cost</th>
                <th style="width: 60px;" class="text-end">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledgers as $l)
                @php
                    $isPositive = (float)$l->qty_in > 0;
                @endphp
                <tr>
                    <td class="font-mono">{{ $l->transaction_date ? \Carbon\Carbon::parse($l->transaction_date)->format('Y-m-d') : '—' }}</td>
                    <td class="font-mono">{{ $l->reference_type ?? ($l->voucher_no ?? '—') }}</td>
                    <td>{{ $l->branch_name ?? '—' }}</td>
                    <td class="font-mono">{{ $l->item_code }}</td>
                    <td><strong>{{ $l->item_name }}</strong></td>
                    <td><span class="badge">{{ $l->transaction_type }}</span></td>
                    <td class="text-end font-mono {{ $isPositive ? 'text-success' : 'text-muted' }}">
                        {{ (float)$l->qty_in > 0 ? number_format($l->qty_in, 2) : '—' }}
                    </td>
                    <td class="text-end font-mono {{ (float)$l->qty_out > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ (float)$l->qty_out > 0 ? number_format($l->qty_out, 2) : '—' }}
                    </td>
                    <td class="text-end font-mono">PKR {{ number_format($l->unit_price, 2) }}</td>
                    <td class="text-end font-mono">PKR {{ number_format($l->total_cost, 2) }}</td>
                    <td class="text-end font-mono fw-bold">{{ number_format($l->running_balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px;">No stock ledger entries found for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Confidential Inventory Document &bull; Generated from {{ $marquee->name ?? config('app.name', 'Marquee CMS') }} &bull; Page printed on {{ date('d M, Y h:i A') }}
    </div>

</body>
</html>
