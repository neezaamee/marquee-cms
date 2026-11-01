<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Register - {{ $marquee->name ?? config('app.name', 'Marquee CMS') }}</title>
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
            font-size: 9.5px;
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
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .kpi-value.primary { color: #1e3a8a; }
        .kpi-value.success { color: #059669; }
        .kpi-value.danger  { color: #dc2626; }
        .kpi-value.info    { color: #0284c7; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
            vertical-align: middle;
        }
        .data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 8.5px;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .font-bold {
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-warning { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-danger  { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-info    { background-color: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
        .badge-default { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        .footer-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            margin-top: 15px;
            font-size: 8px;
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
            font-size: 11px;
            font-weight: bold;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-primary { background-color: #1e3a8a; color: #fff; }
        .btn-secondary { background-color: #64748b; color: #fff; }
        .btn-info { background-color: #0284c7; color: #fff; }
    </style>
</head>
<body>

    @if(!isset($isPdf) || !$isPdf)
        <div class="print-btn-bar no-print">
            <span style="font-weight: bold; color: #1e293b; font-size: 13px;">
                Print Preview: Expense Register ({{ $count }} Records)
            </span>
            <div>
                <button onclick="window.print()" class="btn btn-primary" style="margin-right: 6px;">Print Register</button>
                <a href="{{ route('expenses.pdf', request()->query()) }}" class="btn btn-info" style="margin-right: 6px;">Download PDF</a>
                <button onclick="window.close()" class="btn btn-secondary">Close Window</button>
            </div>
        </div>
    @endif

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <h1 class="company-title">{{ $marquee->name ?? config('app.name', 'Marquee CMS') }}</h1>
                <p class="company-subtitle">
                    {{ $marquee->address ?? 'Central Procurement & Finance Office' }}<br>
                    Phone: {{ $marquee->contact_phone ?? '—' }} &bull; Email: {{ $marquee->email ?? '—' }}
                </p>
            </td>
            <td style="width: 45%;">
                <h2 class="statement-title">Expense Register Log</h2>
                <div class="statement-meta">
                    @php
                        $periodText = 'All Dates';
                        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                            $periodText = \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') . ' to ' . \Carbon\Carbon::parse($filters['end_date'])->format('d M Y');
                        } elseif (!empty($filters['start_date'])) {
                            $periodText = 'From ' . \Carbon\Carbon::parse($filters['start_date'])->format('d M Y');
                        } elseif (!empty($filters['end_date'])) {
                            $periodText = 'Up to ' . \Carbon\Carbon::parse($filters['end_date'])->format('d M Y');
                        }
                    @endphp
                    <strong>Period:</strong> {{ $periodText }}<br>
                    <strong>Branch:</strong> {{ $branchName ?: 'All Branches' }}
                    @if($categoryName) &bull; <strong>Category:</strong> {{ $categoryName }} @endif
                    @if(!empty($filters['status'])) &bull; <strong>Status:</strong> {{ $filters['status'] }} @endif
                    <br>
                    <strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }} &bull; <strong>User:</strong> {{ auth()->user()->name ?? 'System' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Summary KPI Cards -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Total Expenses Logged</div>
                <div class="kpi-value info">{{ $count }} Vouchers</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Subtotal Amount</div>
                <div class="kpi-value">{{ number_format($totalSubtotal, 2) }} PKR</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Tax Base / Withholding</div>
                <div class="kpi-value danger">{{ number_format($totalTax, 2) }} PKR</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Grand Total Outflow</div>
                <div class="kpi-value primary">{{ number_format($totalAmount, 2) }} PKR</div>
            </td>
        </tr>
    </table>

    <!-- Main Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;" class="text-center">#</th>
                <th style="width: 10%;">Voucher #</th>
                <th style="width: 8%;">Date</th>
                <th style="width: 12%;">Branch / Dept</th>
                <th style="width: 13%;">Category</th>
                <th style="width: 14%;">Payee / Supplier</th>
                <th style="width: 13%;">Description</th>
                <th style="width: 9%;" class="text-right">Subtotal</th>
                <th style="width: 8%;" class="text-right">Tax</th>
                <th style="width: 10%;" class="text-right">Total (PKR)</th>
                <th style="width: 8%;" class="text-center">Method</th>
                <th style="width: 7%;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $idx => $exp)
                @php
                    $payee = '—';
                    if ($exp->supplier) {
                        $payee = $exp->supplier->name;
                    } elseif ($exp->employee) {
                        $payee = trim($exp->employee->first_name . ' ' . $exp->employee->last_name);
                    }

                    $catName = $exp->is_multiline ? 'Split Entries' : ($exp->category->name ?? '—');
                    
                    $stBadge = 'badge-default';
                    if ($exp->status === 'Approved' || $exp->status === 'Paid' || $exp->status === 'Posted') {
                        $stBadge = 'badge-success';
                    } elseif ($exp->status === 'Pending Approval' || $exp->status === 'Submitted') {
                        $stBadge = 'badge-warning';
                    } elseif ($exp->status === 'Rejected') {
                        $stBadge = 'badge-danger';
                    }
                @endphp
                <tr>
                    <td class="text-center" style="font-size: 8px; color: #64748b;">{{ $idx + 1 }}</td>
                    <td class="font-bold">{{ $exp->expense_number }}</td>
                    <td>{{ $exp->expense_date ? $exp->expense_date->format('Y-m-d') : '—' }}</td>
                    <td>
                        {{ $exp->branch->name ?? '—' }}
                        @if($exp->department)
                            <div style="font-size: 8px; color: #64748b;">{{ $exp->department }}</div>
                        @endif
                    </td>
                    <td>{{ $catName }}</td>
                    <td>{{ $payee }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($exp->description ?? '—', 30) }}</td>
                    <td class="text-right">{{ number_format($exp->amount, 2) }}</td>
                    <td class="text-right" style="color: #dc2626;">{{ number_format($exp->tax_amount, 2) }}</td>
                    <td class="text-right font-bold" style="color: #1e3a8a;">{{ number_format($exp->total_amount, 2) }}</td>
                    <td class="text-center" style="font-size: 8px;">
                        {{ $exp->payment_method }}
                        @if($exp->payment_method === 'Accounts Payable')
                            @php
                                $payColor = $exp->payment_status === 'Paid' ? '#059669' : ($exp->payment_status === 'Partially Paid' ? '#d97706' : '#dc2626');
                            @endphp
                            <div style="font-size: 7.5px; color: {{ $payColor }}; font-weight: bold;">
                                ({{ $exp->payment_status }})
                            </div>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $stBadge }}">{{ $exp->status }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 20px; color: #64748b;">
                        No expense records match the specified filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($count > 0)
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 2px solid #334155;">
                    <td colspan="7" class="text-right" style="text-transform: uppercase;">Total of {{ $count }} Expenses:</td>
                    <td class="text-right">{{ number_format($totalSubtotal, 2) }}</td>
                    <td class="text-right" style="color: #dc2626;">{{ number_format($totalTax, 2) }}</td>
                    <td class="text-right" style="color: #1e3a8a; font-size: 10.5px;">{{ number_format($totalAmount, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Footer Meta -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                Marquee CMS &bull; Automated Expense Register Statement
            </td>
            <td style="width: 50%; text-align: right;">
                Confidential &bull; Generated on {{ now()->format('d M Y, h:i:s A') }}
            </td>
        </tr>
    </table>

</body>
</html>

