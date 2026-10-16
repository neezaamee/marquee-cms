<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Count & Valuation Audit #{{ $stockTake->stock_take_number }}</title>
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
        .info-grid {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 6px 8px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            font-size: 8.5px;
        }
        .info-label {
            color: #64748b;
            text-transform: uppercase;
            font-size: 7.5px;
            font-weight: bold;
            display: block;
        }
        .info-val {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
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
            font-size: 12px;
            font-weight: bold;
        }
        .count-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }
        .count-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 4px;
            border: 1px solid #1e3a8a;
            text-align: left;
        }
        .count-table td {
            padding: 5px 4px;
            border: 1px solid #e2e8f0;
            font-size: 8px;
            word-wrap: break-word;
        }
        .count-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .count-table tfoot td {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 2px solid #64748b;
            font-size: 8.5px;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'DejaVu Sans Mono', monospace; }
        .text-success { color: #15803d; }
        .text-danger { color: #b91c1c; }
        .text-primary { color: #1d4ed8; }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        .footer {
            clear: both;
            margin-top: 15px;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
            text-align: center;
        }
        .signature-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 33.3%;
            padding: 0 15px;
            text-align: center;
            font-size: 8.5px;
        }
        .sig-line {
            border-top: 1px solid #000;
            margin-top: 35px;
            padding-top: 4px;
            font-weight: bold;
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
            <span style="font-weight: bold; color: #1e3a8a; font-size: 12px;">Print Preview: Physical Stock Count & Valuation Audit</span>
            <div>
                <button onclick="window.print()" class="btn btn-primary">Print Audit Sheet</button>
                <button onclick="window.close()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    @endif

    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <h1 class="company-title">{{ $stockTake->marquee->name ?? config('app.name', 'Marquee CMS') }}</h1>
                <p class="company-subtitle">
                    {{ $stockTake->branch->name ?? 'All Branches' }} &bull; {{ $stockTake->branch->city ?? '' }}
                </p>
            </td>
            <td style="width: 40%;">
                <h2 class="report-title">Physical Stock Audit</h2>
                <div class="report-meta">
                    <strong>Ref #:</strong> {{ $stockTake->stock_take_number }}<br>
                    <strong>Count Date:</strong> {{ $stockTake->count_date->format('d M, Y') }}<br>
                    <strong>Status:</strong>
                    @if($stockTake->status === 'Approved')
                        <span class="badge badge-success">Approved & Posted</span>
                    @elseif($stockTake->status === 'Draft')
                        <span class="badge badge-warning">Draft Pending Approval</span>
                    @else
                        <span class="badge badge-secondary">{{ $stockTake->status }}</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="info-grid">
        <tr>
            <td style="width: 25%;">
                <span class="info-label">Branch</span>
                <span class="info-val">{{ $stockTake->branch->name ?? 'Central' }}</span>
            </td>
            <td style="width: 25%;">
                <span class="info-label">Count Date</span>
                <span class="info-val">{{ $stockTake->count_date->format('Y-m-d') }}</span>
            </td>
            <td style="width: 25%;">
                <span class="info-label">Counted By</span>
                <span class="info-val">{{ $stockTake->creator->name ?? 'Storekeeper' }}</span>
            </td>
            <td style="width: 25%;">
                <span class="info-label">Approved By</span>
                <span class="info-val">{{ $stockTake->approver->name ?? ($stockTake->status === 'Approved' ? 'Manager' : 'Pending') }}</span>
            </td>
        </tr>
        @if(!empty($stockTake->notes))
            <tr>
                <td colspan="4" style="background: #fff;">
                    <span class="info-label">Remarks / Notes:</span>
                    <span>{{ $stockTake->notes }}</span>
                </td>
            </tr>
        @endif
    </table>

    @php
        $totSysQty = 0;
        $totPhysQty = 0;
        $totSysVal = 0;
        $totPhysVal = 0;
        $totDiffVal = 0;

        foreach ($stockTake->items as $itemRow) {
            $item = $itemRow->item;
            $unitCost = (float)($item->average_cost > 0 ? $item->average_cost : ($item->last_purchase_cost > 0 ? $item->last_purchase_cost : $item->default_purchase_rate));
            $sysVal = (float)$itemRow->system_qty * $unitCost;
            $physVal = (float)$itemRow->physical_qty * $unitCost;
            $diffVal = (float)$itemRow->difference * $unitCost;

            $totSysQty += (float)$itemRow->system_qty;
            $totPhysQty += (float)$itemRow->physical_qty;
            $totSysVal += $sysVal;
            $totPhysVal += $physVal;
            $totDiffVal += $diffVal;
        }
    @endphp

    <table class="kpi-table">
        <tr>
            <td style="width: 25%;">
                <span class="kpi-label">System Book Value</span>
                <span class="kpi-value font-mono">PKR {{ number_format($totSysVal, 2) }}</span>
            </td>
            <td style="width: 25%;">
                <span class="kpi-label">Physical Counted Value</span>
                <span class="kpi-value font-mono text-primary">PKR {{ number_format($totPhysVal, 2) }}</span>
            </td>
            <td style="width: 25%; background: {{ $totDiffVal < 0 ? '#fee2e2' : ($totDiffVal > 0 ? '#dcfce7' : '#f1f5f9') }};">
                <span class="kpi-label" style="color: {{ $totDiffVal < 0 ? '#991b1b' : ($totDiffVal > 0 ? '#166534' : '#475569') }};">
                    Net Variance Value
                </span>
                <span class="kpi-value font-mono {{ $totDiffVal < 0 ? 'text-danger' : ($totDiffVal > 0 ? 'text-success' : '') }}">
                    {{ ($totDiffVal > 0 ? '+' : '') }}PKR {{ number_format($totDiffVal, 2) }}
                </span>
            </td>
            <td style="width: 25%;">
                <span class="kpi-label">Audit Discrepancies</span>
                <span class="kpi-value {{ $stockTake->items->where('difference', '!=', 0)->count() > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $stockTake->items->where('difference', '!=', 0)->count() }} Items Differ
                </span>
            </td>
        </tr>
    </table>

    <table class="count-table">
        <thead>
            <tr>
                <th style="width: 60px;">Code</th>
                <th style="width: 140px;">Item Description</th>
                <th style="width: 80px;">Category</th>
                <th style="width: 40px;" class="text-center">UOM</th>
                <th style="width: 60px;" class="text-end">System</th>
                <th style="width: 60px;" class="text-end">Physical</th>
                <th style="width: 55px;" class="text-end">Variance</th>
                <th style="width: 65px;" class="text-end">Unit Cost</th>
                <th style="width: 75px;" class="text-end">Phys. Value</th>
                <th style="width: 70px;" class="text-end">Var. Value</th>
                <th style="width: 90px;">Explanation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockTake->items as $row)
                @php
                    $unitCost = (float)($row->item->average_cost > 0 ? $row->item->average_cost : ($row->item->last_purchase_cost > 0 ? $row->item->last_purchase_cost : $row->item->default_purchase_rate));
                    $diff = (float)$row->difference;
                    $rowDiffVal = $diff * $unitCost;
                    $rowPhysVal = (float)$row->physical_qty * $unitCost;
                @endphp
                <tr>
                    <td class="font-mono">{{ $row->item->item_code ?? '—' }}</td>
                    <td><strong>{{ $row->item->name ?? '—' }}</strong></td>
                    <td>{{ $row->item->category->name ?? '—' }}</td>
                    <td class="text-center">{{ $row->item->unit->short_code ?? 'Pcs' }}</td>
                    <td class="text-end font-mono">{{ number_format($row->system_qty, 2) }}</td>
                    <td class="text-end font-mono fw-bold">{{ number_format($row->physical_qty, 2) }}</td>
                    <td class="text-end font-mono fw-bold {{ $diff == 0 ? 'text-muted' : ($diff > 0 ? 'text-success' : 'text-danger') }}">
                        {{ ($diff > 0 ? '+' : '') . number_format($diff, 2) }}
                    </td>
                    <td class="text-end font-mono">PKR {{ number_format($unitCost, 2) }}</td>
                    <td class="text-end font-mono">PKR {{ number_format($rowPhysVal, 2) }}</td>
                    <td class="text-end font-mono fw-bold {{ $rowDiffVal == 0 ? 'text-muted' : ($rowDiffVal > 0 ? 'text-success' : 'text-danger') }}">
                        {{ ($rowDiffVal > 0 ? '+' : '') . number_format($rowDiffVal, 2) }}
                    </td>
                    <td>{{ $row->reason ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end"><strong>Total Count Summary:</strong></td>
                <td class="text-end font-mono">{{ number_format($totSysQty, 2) }}</td>
                <td class="text-end font-mono fw-bold">{{ number_format($totPhysQty, 2) }}</td>
                <td class="text-end font-mono fw-bold">{{ ($totPhysQty - $totSysQty > 0 ? '+' : '') . number_format($totPhysQty - $totSysQty, 2) }}</td>
                <td></td>
                <td class="text-end font-mono fw-bold">PKR {{ number_format($totPhysVal, 2) }}</td>
                <td class="text-end font-mono fw-bold {{ $totDiffVal < 0 ? 'text-danger' : 'text-success' }}">
                    {{ ($totDiffVal > 0 ? '+' : '') . number_format($totDiffVal, 2) }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                <div class="sig-line">Physical Counted By</div>
                <div>Storekeeper / Auditor</div>
            </td>
            <td>
                <div class="sig-line">Verified By</div>
                <div>Inventory Controller / Accounts</div>
            </td>
            <td>
                <div class="sig-line">Approved By</div>
                <div>General Manager / Business Owner</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Confidential Internal Audit &bull; Generated from {{ $stockTake->marquee->name ?? config('app.name', 'Marquee CMS') }} &bull; Printed on {{ date('d M, Y h:i A') }}
    </div>

</body>
</html>
