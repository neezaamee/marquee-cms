<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supplier Directory - {{ $marquee->name ?? config('app.name', 'Marquee CMS') }}</title>
    <style>
        @page {
            margin: 20px 25px 30px 25px;
            size: a4 landscape;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.35;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2.5px solid #1e3a8a;
            padding-bottom: 8px;
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
            font-size: 9px;
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
            font-size: 9px;
            color: #475569;
            text-align: right;
            line-height: 1.4;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 8px 0;
        }
        .kpi-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 12px;
            vertical-align: middle;
        }
        .kpi-label {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            font-family: 'Courier New', Courier, monospace;
        }
        .kpi-value.danger {
            color: #dc2626;
        }
        .kpi-value.success {
            color: #16a34a;
        }
        .filter-badge-bar {
            background: #f1f5f9;
            border-left: 3px solid #0284c7;
            padding: 5px 10px;
            margin-bottom: 12px;
            font-size: 9px;
            color: #334155;
        }
        .filter-badge-bar strong {
            color: #0f172a;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 6px 8px;
            border: 1px solid #1e3a8a;
            text-align: left;
        }
        .data-table th.text-center, .data-table td.text-center {
            text-align: center;
        }
        .data-table th.text-end, .data-table td.text-end {
            text-align: right;
        }
        .data-table td {
            padding: 5.5px 8px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            font-size: 9px;
            color: #1e293b;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table tr.total-row {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
        }
        .data-table tr.total-row td {
            font-size: 10px;
            color: #0f172a;
            padding: 7px 8px;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
        .fw-bold {
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 7.5px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-active {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .badge-inactive {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .badge-cat {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            margin-right: 2px;
        }
        .footer-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            margin-top: 15px;
            font-size: 8.5px;
            color: #64748b;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #fff;
                margin: 0;
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
            <span style="font-weight: bold; color: #1e293b; font-size: 13px;">Print Preview: Supplier Directory &amp; Accounts Payable</span>
            <div>
                <button onclick="window.print()" class="btn btn-primary">Print Directory</button>
                <button onclick="window.close()" class="btn btn-secondary">Close Window</button>
            </div>
        </div>
    @endif

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <h1 class="company-title">{{ $marquee->name ?? config('app.name', 'Marquee CMS') }}</h1>
                <p class="company-subtitle">
                    {{ $marquee->address ?? 'Central Procurement Office' }}<br>
                    Phone: {{ $marquee->contact_phone ?? '—' }} &bull; Email: {{ $marquee->email ?? '—' }}
                </p>
            </td>
            <td style="width: 40%;">
                <h2 class="statement-title">Supplier Directory</h2>
                <div class="statement-meta">
                    <strong>Generated Date:</strong> {{ date('d M, Y h:i A') }}<br>
                    <strong>Generated By:</strong> {{ auth()->user()->name ?? 'System' }}
                </div>
            </td>
        </tr>
    </table>

    @php
        $totalSuppliers = $suppliers->count();
        $activeSuppliers = $suppliers->where('status', 'Active')->count();
        $totalOutstanding = $suppliers->sum('current_balance');
    @endphp

    <!-- KPI Summary Row -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-card" style="width: 33.3%;">
                <div class="kpi-label">Total Suppliers Listed</div>
                <div class="kpi-value">{{ $totalSuppliers }}</div>
            </td>
            <td class="kpi-card" style="width: 33.3%;">
                <div class="kpi-label">Active Vendors</div>
                <div class="kpi-value success">{{ $activeSuppliers }} <span style="font-size: 9px; color: #64748b; font-weight: normal;">({{ $totalSuppliers - $activeSuppliers }} Inactive)</span></div>
            </td>
            <td class="kpi-card" style="width: 33.3%;">
                <div class="kpi-label">Total Outstanding Payables (AP)</div>
                <div class="kpi-value danger">Rs. {{ number_format($totalOutstanding, 2) }}</div>
            </td>
        </tr>
    </table>

    <!-- Applied Filters Bar -->
    <div class="filter-badge-bar">
        <strong>Filters Applied:</strong>
        Category: <strong>{{ ($categoryFilter && $categoryFilter !== 'all') ? (\App\Models\SupplierCategory::find($categoryFilter)?->name ?? 'Selected') : 'All Categories' }}</strong> &bull;
        Balance Status: <strong>{{ $balanceFilter === 'outstanding' ? 'Outstanding (> 0)' : 'All Balances' }}</strong>
        @if(!empty($search))
            &bull; Search Query: <strong>"{{ $search }}"</strong>
        @endif
    </div>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">#</th>
                <th style="width: 75px;">Code</th>
                <th style="width: 170px;">Supplier / Vendor Name</th>
                <th style="width: 140px;">Categories</th>
                <th style="width: 110px;">Contact Person</th>
                <th style="width: 100px;">Mobile</th>
                <th style="width: 100px;">WhatsApp</th>
                <th style="width: 80px;">City</th>
                <th class="text-center" style="width: 65px;">Status</th>
                <th class="text-end" style="width: 110px;">Outstanding (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suppliers as $idx => $s)
                <tr>
                    <td class="text-center font-mono">{{ $idx + 1 }}</td>
                    <td class="font-mono fw-bold">{{ $s->supplier_code ?: '—' }}</td>
                    <td class="fw-bold">{{ $s->name }}</td>
                    <td>
                        @if($s->categories->isNotEmpty())
                            @foreach($s->categories->take(2) as $sc)
                                <span class="badge badge-cat">{{ $sc->name }}</span>
                            @endforeach
                            @if($s->categories->count() > 2)
                                <span class="badge badge-inactive">+{{ $s->categories->count() - 2 }}</span>
                            @endif
                        @else
                            <span style="color: #94a3b8;">—</span>
                        @endif
                    </td>
                    <td>{{ $s->contact_person ?: '—' }}</td>
                    <td class="font-mono">{{ $s->mobile_number ?: '—' }}</td>
                    <td class="font-mono">{{ $s->whatsapp_number ?: '—' }}</td>
                    <td>{{ $s->city ?: '—' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $s->status === 'Active' ? 'badge-active' : 'badge-inactive' }}">
                            {{ $s->status }}
                        </span>
                    </td>
                    <td class="text-end font-mono fw-bold {{ $s->current_balance > 0 ? 'text-danger' : '' }}" style="{{ $s->current_balance > 0 ? 'color: #dc2626;' : 'color: #16a34a;' }}">
                        Rs. {{ number_format($s->current_balance, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #94a3b8;">No suppliers found matching the criteria.</td>
                </tr>
            @endforelse

            <!-- Total Row -->
            <tr class="total-row">
                <td colspan="9" class="text-end">TOTAL OUTSTANDING PAYABLES (AP):</td>
                <td class="text-end font-mono" style="color: #dc2626;">Rs. {{ number_format($totalOutstanding, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Footer Information -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                This document is a confidential business record generated from {{ config('app.name', 'Marquee CMS') }}.
            </td>
            <td style="width: 50%; text-align: right;">
                Printed on {{ date('d-m-Y H:i:s') }}
            </td>
        </tr>
    </table>

    @if(!isset($isPdf) || !$isPdf)
        <script>
            window.onload = function() {
                // Auto trigger browser print dialogue
                setTimeout(function() { window.print(); }, 400);
            };
        </script>
    @endif

</body>
</html>
