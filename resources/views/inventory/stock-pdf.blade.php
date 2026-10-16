<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Valuation & Status Report - {{ $marquee->name ?? config('app.name', 'Marquee CMS') }}</title>
    <style>
        @page {
            margin: 20px 25px 25px 25px;
            size: a4 landscape;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9.5px;
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
        .header-table td {
            vertical-align: top;
        }
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
            font-size: 9px;
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
            color: #0f172a;
        }
        .stock-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }
        .stock-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 4px;
            border: 1px solid #1e3a8a;
            text-align: left;
        }
        .stock-table td {
            padding: 5px 4px;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
            word-wrap: break-word;
        }
        .stock-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .stock-table tfoot td {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 2px solid #64748b;
            font-size: 9px;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'DejaVu Sans Mono', monospace; }
        .text-success { color: #15803d; }
        .text-warning { color: #b45309; }
        .text-danger { color: #b91c1c; }
        .text-primary { color: #1d4ed8; }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .badge-info { background: #e0f2fe; color: #0369a1; }
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
            <span style="font-weight: bold; color: #1e3a8a; font-size: 12px;">Print Preview: Current Stock Levels & Valuation Report</span>
            <div>
                <button onclick="window.print()" class="btn btn-primary">Print Report</button>
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
                <h2 class="report-title">Stock Valuation Report</h2>
                <div class="report-meta">
                    <strong>Generated:</strong> {{ date('d M, Y h:i A') }}<br>
                    <strong>Generated By:</strong> {{ auth()->user()->name ?? 'System User' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="filter-badges">
        <strong>Report Scope:</strong>
        Branch: <strong>{{ $branchName ?? 'All Branches' }}</strong> &bull;
        Category: <strong>{{ $categoryName ?? 'All Categories' }}</strong> &bull;
        Status Filter: <strong>{{ !empty($filterStatus) ? ucfirst(str_replace('_', ' ', $filterStatus)) : 'All Statuses' }}</strong>
        @if(!empty($search)) &bull; Keyword: <strong>"{{ $search }}"</strong> @endif
    </div>

    <table class="kpi-table">
        <tr>
            <td style="width: 20%;">
                <span class="kpi-label">Cataloged Items</span>
                <span class="kpi-value text-primary">{{ number_format($stats['total'] ?? count($items)) }}</span>
            </td>
            <td style="width: 20%;">
                <span class="kpi-label">Healthy Stock</span>
                <span class="kpi-value text-success">{{ number_format($stats['good'] ?? 0) }}</span>
            </td>
            <td style="width: 20%;">
                <span class="kpi-label">Reorder / Critical</span>
                <span class="kpi-value text-warning">{{ number_format(($stats['reorder'] ?? 0) + ($stats['low'] ?? 0)) }}</span>
            </td>
            <td style="width: 20%;">
                <span class="kpi-label">Out of Stock</span>
                <span class="kpi-value text-danger">{{ number_format($stats['out'] ?? 0) }}</span>
            </td>
            <td style="width: 20%; background: #e0f2fe; border-color: #7dd3fc;">
                <span class="kpi-label" style="color: #0369a1;">Total Stock Valuation</span>
                <span class="kpi-value text-primary">PKR {{ number_format($stats['total_stock_value'] ?? 0, 2) }}</span>
            </td>
        </tr>
    </table>

    <table class="stock-table">
        <thead>
            <tr>
                <th style="width: 70px;">Code</th>
                <th style="width: 140px;">Item Name</th>
                <th style="width: 90px;">Category</th>
                <th style="width: 40px;" class="text-center">Unit</th>
                <th style="width: 65px;" class="text-end">Opening</th>
                <th style="width: 65px;" class="text-end">Purchased</th>
                <th style="width: 60px;" class="text-end">Returned</th>
                <th style="width: 65px;" class="text-end">Net Issued</th>
                <th style="width: 65px;" class="text-end">Adjusted</th>
                <th style="width: 75px;" class="text-end">Current Stock</th>
                <th style="width: 70px;" class="text-end">Unit Cost</th>
                <th style="width: 85px;" class="text-end">Stock Value</th>
                <th style="width: 75px;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $pageStock = 0;
                $pageValuation = 0;
            @endphp
            @forelse($items as $item)
                @php
                    $opening = (float) ($item->total_opening ?? 0);
                    $received = (float) ($item->total_received ?? 0);
                    $returned = (float) ($item->total_returned ?? 0);
                    $netIssued = (float) (($item->total_issued ?? 0) - ($item->total_dept_returned ?? 0));
                    $adjusted = (float) ($item->total_adjusted ?? 0);
                    $stock = $opening + $received - $returned - $netIssued + $adjusted;

                    $unitCost = (float)($item->average_cost > 0 ? $item->average_cost : ($item->last_purchase_cost > 0 ? $item->last_purchase_cost : $item->default_purchase_rate));
                    $itemValuation = max(0, $stock) * $unitCost;

                    $pageStock += $stock;
                    $pageValuation += $itemValuation;

                    if ($stock <= 0) {
                        $statusClass = 'badge-danger';
                        $statusText = 'Out of Stock';
                    } elseif ($stock <= $item->minimum_stock_level) {
                        $statusClass = 'badge-danger';
                        $statusText = 'Critical Low';
                    } elseif ($stock <= $item->reorder_level) {
                        $statusClass = 'badge-warning';
                        $statusText = 'Reorder Req.';
                    } else {
                        $statusClass = 'badge-success';
                        $statusText = 'Healthy';
                    }
                @endphp
                <tr>
                    <td class="font-mono">{{ $item->item_code }}</td>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ $item->category->name ?? '—' }}</td>
                    <td class="text-center">{{ $item->unit->short_code ?? 'Pcs' }}</td>
                    <td class="text-end font-mono">{{ number_format($opening, 2) }}</td>
                    <td class="text-end font-mono text-success">{{ number_format($received, 2) }}</td>
                    <td class="text-end font-mono text-warning">{{ number_format($returned, 2) }}</td>
                    <td class="text-end font-mono text-danger">{{ number_format($netIssued, 2) }}</td>
                    <td class="text-end font-mono">{{ ($adjusted > 0 ? '+' : '') . number_format($adjusted, 2) }}</td>
                    <td class="text-end font-mono fw-bold {{ $stock <= $item->minimum_stock_level ? 'text-danger' : 'text-primary' }}">
                        {{ number_format($stock, 2) }}
                    </td>
                    <td class="text-end font-mono">PKR {{ number_format($unitCost, 2) }}</td>
                    <td class="text-end font-mono fw-bold">PKR {{ number_format($itemValuation, 2) }}</td>
                    <td class="text-center">
                        <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center" style="padding: 20px;">No inventory items match the specified filters.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="9" class="text-end"><strong>Total:</strong></td>
                <td class="text-end font-mono fw-bold">{{ number_format($pageStock, 2) }}</td>
                <td></td>
                <td class="text-end font-mono fw-bold">PKR {{ number_format($pageValuation, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Confidential Inventory Document &bull; Generated from {{ $marquee->name ?? config('app.name', 'Marquee CMS') }} &bull; Page printed on {{ date('d M, Y h:i A') }}
    </div>

</body>
</html>
