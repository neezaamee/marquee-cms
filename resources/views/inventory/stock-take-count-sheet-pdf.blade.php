<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Physical Stock Count Sheet - {{ $marquee->name ?? config('app.name', 'Marquee CMS') }}</title>
    <style>
        @page {
            margin: 20px 25px 25px 25px;
            size: a4 portrait;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9.5px;
            line-height: 1.4;
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
            font-size: 10.5px;
            font-weight: bold;
            color: #0f172a;
        }
        .sheet-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }
        .sheet-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 7px 5px;
            border: 1px solid #1e3a8a;
            text-align: left;
        }
        .sheet-table td {
            padding: 8px 5px;
            border: 1px solid #94a3b8;
            font-size: 8.5px;
            word-wrap: break-word;
        }
        .sheet-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'DejaVu Sans Mono', monospace; }
        .count-box {
            display: inline-block;
            width: 100%;
            height: 18px;
            border: 1px dashed #64748b;
            background: #ffffff;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
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
            <span style="font-weight: bold; color: #1e3a8a; font-size: 12px;">Print Preview: Physical Stock Count Worksheet</span>
            <div>
                <button onclick="window.print()" class="btn btn-primary">Print Worksheet</button>
                <button onclick="window.close()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    @endif

    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <h1 class="company-title">{{ $marquee->name ?? config('app.name', 'Marquee CMS') }}</h1>
                <p class="company-subtitle">
                    {{ $branch->name ?? 'All Branches' }} &bull; {{ $branch->city ?? '' }}
                </p>
            </td>
            <td style="width: 40%;">
                <h2 class="report-title">Physical Count Sheet</h2>
                <div class="report-meta">
                    <strong>Printed On:</strong> {{ date('d M, Y h:i A') }}<br>
                    <strong>Category:</strong> {{ $categoryName ?? 'All Categories' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="info-grid">
        <tr>
            <td style="width: 25%;">
                <span class="info-label">Branch / Warehouse</span>
                <span class="info-val">{{ $branch->name ?? 'Central Storage' }}</span>
            </td>
            <td style="width: 25%;">
                <span class="info-label">Count Date</span>
                <span class="info-val">____ / ____ / 2026</span>
            </td>
            <td style="width: 25%;">
                <span class="info-label">Assigned Auditor</span>
                <span class="info-val">____________________</span>
            </td>
            <td style="width: 25%;">
                <span class="info-label">Supervising Officer</span>
                <span class="info-val">____________________</span>
            </td>
        </tr>
    </table>

    <table class="sheet-table">
        <thead>
            <tr>
                <th style="width: 35px;" class="text-center">#</th>
                <th style="width: 75px;">Item Code</th>
                <th style="width: 170px;">Item Name / Description</th>
                <th style="width: 90px;">Category</th>
                <th style="width: 45px;" class="text-center">Unit</th>
                <th style="width: 70px;" class="text-end">System Qty</th>
                <th style="width: 85px;" class="text-center">Physical Count</th>
                <th style="width: 65px;" class="text-center">Discrepancy</th>
                <th style="width: 110px;">Remarks / Shelf</th>
            </tr>
        </thead>
        <tbody>
            @php $count = 1; @endphp
            @forelse($items as $it)
                <tr>
                    <td class="text-center font-mono">{{ $count++ }}</td>
                    <td class="font-mono"><strong>{{ $it['item_code'] }}</strong></td>
                    <td><strong>{{ $it['name'] }}</strong></td>
                    <td>{{ $it['category_name'] ?? '—' }}</td>
                    <td class="text-center">{{ $it['unit_name'] ?? 'Pcs' }}</td>
                    <td class="text-end font-mono">{{ number_format($it['system_qty'], 2) }}</td>
                    <td style="background: #ffffff; padding: 3px;">
                        <span class="count-box"></span>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">No inventory items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                <div class="sig-line">Physical Counted By</div>
                <div>Name: _______________________</div>
            </td>
            <td>
                <div class="sig-line">Verified & Re-counted By</div>
                <div>Name: _______________________</div>
            </td>
            <td>
                <div class="sig-line">Approved for Adjustment</div>
                <div>Name: _______________________</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Floor Stock Count Worksheet &bull; Generated from {{ $marquee->name ?? config('app.name', 'Marquee CMS') }} &bull; Print date: {{ date('d M, Y h:i A') }}
    </div>

</body>
</html>
