<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $voucher->voucher_type }} - {{ $voucher->voucher_no }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Inter', Roboto, Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            background: #f1f5f9;
            padding: 20px 10px;
            line-height: 1.35;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .font-mono {
            font-family: 'Consolas', 'JetBrains Mono', 'Courier New', monospace;
        }

        /* Screen Controls Toolbar (Hidden on Print) */
        .toolbar {
            width: 5.8in;
            max-width: 100%;
            margin: 0 auto 15px auto;
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 11.5px;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s ease-in-out;
        }

        .btn-default {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #475569;
        }
        .btn-default:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-primary {
            background: #0f172a;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #1e293b;
        }

        /* 5.8 x 8.3 inches Portrait Layout (A5 Portrait) */
        .voucher-card {
            width: 5.8in;
            max-width: 5.8in;
            height: 8.3in;
            max-height: 8.3in;
            margin: 0 auto;
            background: #ffffff;
            padding: 6mm 8mm;
            box-sizing: border-box;
            border-radius: 4px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            border: 1.5px solid #0f172a;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Header Row */
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .org-name {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.2px;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .org-sub {
            font-size: 9px;
            color: #475569;
            margin-top: 2px;
            line-height: 1.25;
        }

        .title-box {
            text-align: right;
        }

        .voucher-title {
            font-size: 13.5px;
            font-weight: 800;
            color: {{ $voucher->voucher_type === 'CPV' ? '#047857' : '#1d4ed8' }};
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.15;
        }

        .voucher-no {
            font-size: 12px;
            font-weight: 700;
            margin-top: 1px;
            color: #0f172a;
        }

        .date-badge-row {
            display: flex;
            gap: 5px;
            align-items: center;
            justify-content: flex-end;
            margin-top: 2px;
            font-size: 9.5px;
        }

        .status-stamp {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            @if($voucher->status === 'posted')
                background: #dcfce7;
                color: #15803d;
                border: 1px solid #86efac;
            @elseif($voucher->status === 'approved')
                background: #fef3c7;
                color: #b45309;
                border: 1px solid #fcd34d;
            @elseif($voucher->status === 'draft')
                background: #f1f5f9;
                color: #475569;
                border: 1px solid #cbd5e1;
            @else
                background: #fee2e2;
                color: #b91c1c;
                border: 1px solid #fca5a5;
            @endif
        }

        /* Meta Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            margin-bottom: 8px;
        }

        .meta-group {
            display: flex;
            flex-direction: column;
            gap: 2.5px;
        }

        .meta-row {
            display: flex;
            align-items: baseline;
            font-size: 10px;
        }

        .meta-label {
            width: 95px;
            font-weight: 600;
            color: #475569;
            flex-shrink: 0;
            font-size: 9.5px;
        }

        .meta-val {
            flex: 1;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .items-table th {
            background: #0f172a;
            color: #ffffff;
            padding: 4px 6px;
            font-size: 9px;
            font-weight: 700;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #0f172a;
        }

        .items-table td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
            vertical-align: middle;
        }

        /* Words Box */
        .words-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 3.5px solid {{ $voucher->voucher_type === 'CPV' ? '#059669' : '#2563eb' }};
            border-radius: 3px;
            padding: 4px 8px;
            margin-bottom: 8px;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .words-label {
            font-size: 8.5px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .words-text {
            font-size: 10px;
            font-weight: 700;
            color: #0f172a;
            font-style: italic;
            flex: 1;
        }

        /* Status Notice Box */
        .notice-box {
            border-radius: 3px;
            padding: 4px 8px;
            margin-bottom: 8px;
            font-size: 9px;
            line-height: 1.3;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .notice-unposted {
            background: #fffbeb;
            border: 1px dashed #f59e0b;
            color: #92400e;
        }

        .notice-posted {
            background: #f0fdf4;
            border: 1px dashed #22c55e;
            color: #166534;
        }

        /* 4 Signatures (2x2 Grid for Portrait Elegance) */
        .signatures-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .sig-box {
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            background: #ffffff;
            padding: 5px 8px;
            height: 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sig-title {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #334155;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 2px;
            letter-spacing: 0.2px;
        }

        .sig-meta {
            font-size: 8px;
            color: #64748b;
            line-height: 1.15;
        }

        .sig-line {
            margin-top: auto;
            border-top: 1px solid #94a3b8;
            padding-top: 2px;
            font-size: 8px;
            color: #1e293b;
            font-weight: 700;
            text-align: center;
        }

        /* Watermark */
        .watermark-overlay {
            position: absolute;
            top: 48%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 42px;
            font-weight: 900;
            opacity: 0.04;
            pointer-events: none;
            white-space: nowrap;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #000;
        }

        /* Print Settings for Portrait (5.8 x 8.3 inches / A5 Portrait) */
        @page {
            size: 5.8in 8.3in;
            margin: 0 !important;
        }

        @media print {
            body {
                width: 5.8in !important;
                height: 8.3in !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }

            .d-print-none {
                display: none !important;
            }

            .voucher-card {
                box-shadow: none !important;
                border: 1.5px solid #0f172a !important;
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 6mm 8mm !important;
                width: 5.8in !important;
                max-width: 5.8in !important;
                height: 8.3in !important;
                max-height: 8.3in !important;
                box-sizing: border-box !important;
                page-break-inside: avoid !important;
                overflow: hidden !important;
            }
        }
    </style>
</head>
<body>

    <!-- Controls Bar (Hidden on Print) -->
    <div class="toolbar d-print-none">
        <div>
            <a href="{{ route('finance.payment-vouchers.show', $voucher->id) }}" class="btn btn-default">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="{{ route('finance.payment-vouchers.index') }}" class="btn btn-default" style="margin-left: 6px;">
                <i class="fas fa-list"></i> List
            </a>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <span class="status-stamp">{{ $voucher->status_label }}</span>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print (Portrait)
            </button>
        </div>
    </div>

    <!-- Formal Voucher Document (5.8 x 8.3 inches Portrait) -->
    <div class="voucher-card">
        <div class="watermark-overlay">
            {{ $voucher->status === 'posted' ? 'PAID & POSTED' : ($voucher->status === 'approved' ? 'AUTHORIZED VOUCHER' : $voucher->status) }}
        </div>

        <div>
            <!-- Header -->
            <div class="header-row">
                <div>
                    <h1 class="org-name">{{ $voucher->marquee->name ?? config('app.name', 'MARQUEE CMS') }}</h1>
                    <div class="org-sub">
                        <strong>{{ $voucher->branch->name ?? 'Main Branch' }}</strong> | {{ $voucher->branch->address ?? ($voucher->marquee->address ?? 'Main Boulevard') }}<br>
                        Phone: {{ $voucher->branch->phone ?? ($voucher->marquee->contact_phone ?? '—') }}
                    </div>
                </div>
                <div class="title-box">
                    <div class="voucher-title">{{ $voucher->voucher_type === 'CPV' ? 'Cash Payment Voucher' : 'Bank Payment Voucher' }}</div>
                    <div class="voucher-no font-mono">{{ $voucher->voucher_no }}</div>
                    <div class="date-badge-row">
                        <span>Date: <strong>{{ $voucher->voucher_date->format('d M, Y') }}</strong></span>
                        <span class="status-stamp">{{ $voucher->status_label }}</span>
                    </div>
                    @if($voucher->journalVoucher)
                        <div class="font-mono" style="font-size: 8.5px; color: #15803d; font-weight: 700; margin-top: 1px;">
                            GL JV: #{{ $voucher->journalVoucher->voucher_no }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Meta Information Grid -->
            <div class="meta-grid">
                <div class="meta-group">
                    <div class="meta-row">
                        <span class="meta-label">Payee:</span>
                        <span class="meta-val" style="font-size: 11px;">{{ $voucher->payee_name }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Category:</span>
                        <span class="meta-val" style="text-transform: capitalize;">
                            {{ $voucher->payee_type === 'vendor' ? 'Third-Party Vendor' : ($voucher->payee_type === 'expense' ? 'Operating Expense' : ucfirst($voucher->payee_type)) }}
                        </span>
                    </div>
                    @if($voucher->payee_cnic)
                        <div class="meta-row">
                            <span class="meta-label">CNIC:</span>
                            <span class="meta-val font-mono">{{ $voucher->payee_cnic }}</span>
                        </div>
                    @endif
                    @if($voucher->payee_phone)
                        <div class="meta-row">
                            <span class="meta-label">Contact:</span>
                            <span class="meta-val font-mono">{{ $voucher->payee_phone }}</span>
                        </div>
                    @endif
                    @if($voucher->reference_no)
                        <div class="meta-row">
                            <span class="meta-label">Bill / Ref #:</span>
                            <span class="meta-val font-mono">{{ $voucher->reference_no }}</span>
                        </div>
                    @endif
                </div>

                <div class="meta-group">
                    <div class="meta-row">
                        <span class="meta-label">Mode:</span>
                        <span class="meta-val">{{ $voucher->payment_method }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">Paid From:</span>
                        <span class="meta-val">
                            {{ $voucher->cashBankAccount->account->name ?? 'Cash/Bank' }}
                            @if($voucher->cashBankAccount && $voucher->cashBankAccount->type === 'bank' && $voucher->cashBankAccount->bank_name)
                                ({{ $voucher->cashBankAccount->bank_name }})
                            @endif
                        </span>
                    </div>
                    @if($voucher->cheque_no)
                        <div class="meta-row">
                            <span class="meta-label">Cheque #:</span>
                            <span class="meta-val font-mono">{{ $voucher->cheque_no }} @if($voucher->cheque_date) ({{ $voucher->cheque_date->format('d/m/Y') }}) @endif</span>
                        </div>
                    @endif
                    <div class="meta-row">
                        <span class="meta-label">Debit Head:</span>
                        <span class="meta-val font-mono">
                            [{{ $voucher->debitAccount->account_code ?? '—' }}] {{ $voucher->debitAccount->name ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Particulars Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">#</th>
                        <th style="width: 32%;">Account Head</th>
                        <th style="width: 38%;">Particulars / Description</th>
                        <th style="width: 24%; text-align: right;">Amount (PKR)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-mono">1</td>
                        <td>
                            <strong class="font-mono">[{{ $voucher->debitAccount->account_code ?? '—' }}]</strong><br>
                            {{ $voucher->debitAccount->name ?? 'Expense Account' }}
                        </td>
                        <td>
                            {{ $voucher->description ?: 'Payment towards ' . ($voucher->payee_type === 'supplier' ? 'Supplier Accounts Payable' : ($voucher->payee_type === 'vendor' ? 'Vendor Service Settlement' : 'Authorized operating expense')) }}
                            @if($voucher->reference_no)
                                <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">Ref: {{ $voucher->reference_no }}</div>
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: 700; font-size: 11.5px;" class="font-mono">
                            {{ number_format($voucher->amount, 2) }}
                        </td>
                    </tr>
                    <tr style="background: #f8fafc; font-weight: 700;">
                        <td colspan="3" style="text-align: right; text-transform: uppercase; font-size: 9.5px; padding-right: 8px;">
                            Total Amount Payable:
                        </td>
                        <td style="text-align: right; font-size: 12px; color: #0f172a; font-weight: 800;" class="font-mono">
                            Rs. {{ number_format($voucher->amount, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Amount in Words -->
            <div class="words-box">
                <span class="words-label">In Words:</span>
                <span class="words-text">{{ $voucher->amount_in_words ?: 'Rupees ' . number_format($voucher->amount, 2) }}</span>
            </div>

            <!-- Status Notice -->
            @if($voucher->status !== 'posted')
                <div class="notice-box notice-unposted">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Pre-Payment Voucher:</strong> Unposted document for payee acknowledgment. Funds will be posted to General Ledger upon disbursement confirmation.</span>
                </div>
            @else
                <div class="notice-box notice-posted">
                    <i class="fas fa-check-circle"></i>
                    <span><strong>Confirmed & Disbursed:</strong> Paid on <strong>{{ $voucher->disbursed_at ? $voucher->disbursed_at->format('d M, Y h:i A') : $voucher->voucher_date->format('d M, Y') }}</strong> by <strong>{{ $voucher->disbursedBy->name ?? 'Cashier' }}</strong>. Posted to GL Voucher #{{ $voucher->journalVoucher->voucher_no ?? '—' }}.</span>
                </div>
            @endif
        </div>

        <div>
            <!-- 4 Formal Signature Blocks (2x2 Grid for Portrait) -->
            <div class="signatures-container">
                <!-- Prepared By -->
                <div class="sig-box">
                    <div>
                        <div class="sig-title">1. Prepared By</div>
                        <div class="sig-meta">
                            {{ $voucher->preparedBy->name ?? 'Staff' }}<br>
                            {{ $voucher->created_at ? $voucher->created_at->format('d/m/Y H:i') : '' }}
                        </div>
                    </div>
                    <div class="sig-line">Signature</div>
                </div>

                <!-- Checked By -->
                <div class="sig-box">
                    <div>
                        <div class="sig-title">2. Checked By</div>
                        <div class="sig-meta">
                            {{ $voucher->checkedBy->name ?? 'Accounts' }}<br>
                            Audit OK
                        </div>
                    </div>
                    <div class="sig-line">Signature</div>
                </div>

                <!-- Approved By -->
                <div class="sig-box">
                    <div>
                        <div class="sig-title">3. Approved By</div>
                        <div class="sig-meta">
                            {{ $voucher->approvedBy->name ?? 'Management' }}<br>
                            Payment Authorized
                        </div>
                    </div>
                    <div class="sig-line">Signature</div>
                </div>

                <!-- Received By (Payee) -->
                <div class="sig-box" style="background: #fdfefe; border-color: #64748b;">
                    <div>
                        <div class="sig-title" style="color: #0f172a; font-weight: 800;">4. Payee Acknowledgment</div>
                        <div class="sig-meta">
                            Received full sum in cash/cheque.
                        </div>
                    </div>
                    <div class="sig-line" style="border-top-color: #475569;">
                        Receiver Sign & Stamp
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
