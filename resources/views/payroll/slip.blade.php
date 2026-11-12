<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $item->salary_slip_number }} - {{ $item->employee ? $item->employee->name : 'Staff' }}</title>
    <link href="{{ asset('assets/css/theme.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendors/fontawesome/all.min.css') }}" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #212529;
            margin: 0;
            padding: 20px;
        }
        .payslip-container {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            border: 1px solid #dee2e6;
        }
        .org-header {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .payslip-title {
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
        }
        .meta-table td {
            padding: 4px 8px;
            font-size: 12px;
        }
        .salary-table th {
            background-color: #f1f4f9;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 12px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
        }
        .salary-table td {
            font-size: 12px;
            padding: 7px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .total-row {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 1px solid #cbd5e1 !important;
            border-bottom: 2px solid #cbd5e1 !important;
        }
        .net-salary-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%);
            border: 1px solid #86efac;
            border-radius: 6px;
            padding: 16px 20px;
            margin: 20px 0;
        }
        .signature-box {
            border-top: 1px solid #475569;
            padding-top: 6px;
            font-size: 11px;
            text-align: center;
            color: #475569;
            margin-top: 50px;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .payslip-container {
                box-shadow: none;
                border: none;
                padding: 15px 20px;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Print Navigation Bar -->
    <div class="payslip-container no-print mb-3 py-2 px-3 bg-white border d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <span class="fas fa-file-invoice-dollar text-primary"></span>
            <span class="fw-bold fs-11">Staff Salary Payslip &mdash; {{ $item->salary_slip_number }}</span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold">
                <span class="fas fa-print me-1"></span> Print Payslip
            </button>
            <button onclick="window.close()" class="btn btn-secondary btn-sm">
                <span class="fas fa-times me-1"></span> Close
            </button>
        </div>
    </div>

    <!-- Main Printable Payslip -->
    <div class="payslip-container">
        <!-- Organization Header -->
        <div class="org-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    @if($marquee && $marquee->logo)
                        <img src="{{ asset('storage/' . $marquee->logo) }}" alt="Logo" style="max-height: 48px; margin-bottom: 6px;">
                    @endif
                    <h3 class="mb-0 fw-bold text-900" style="color: #1e293b;">{{ $marquee ? $marquee->name : 'Marquee CMS' }}</h3>
                    <p class="text-muted mb-0 fs-11">
                        {{ $marquee ? $marquee->address : '' }}
                        @if($marquee && $marquee->phone) &bull; Phone: {{ $marquee->phone }} @endif
                        @if($marquee && $marquee->email) &bull; Email: {{ $marquee->email }} @endif
                    </p>
                </div>
                <div class="text-end">
                    <span class="badge bg-secondary-subtle text-dark border px-3 py-1 payslip-title">SALARY PAYSLIP</span>
                    <div class="fs-11 text-muted mt-2">
                        Slip #: <strong class="text-dark">{{ $item->salary_slip_number }}</strong>
                    </div>
                    <div class="fs-11 text-muted">
                        Batch: <strong class="text-dark">{{ $item->payroll ? $item->payroll->batch_number : '—' }}</strong>
                    </div>
                    <div class="fs-11 text-muted">
                        Month: <strong class="text-primary">{{ \Carbon\Carbon::parse(($item->payroll ? $item->payroll->payroll_month : date('Y-m')) . '-01')->format('F Y') }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Info Summary Table -->
        <table class="w-100 meta-table mb-3">
            <tbody>
                <tr>
                    <td class="text-muted" style="width: 18%;">Employee Name:</td>
                    <td class="fw-bold" style="width: 32%;">{{ $item->employee ? $item->employee->name : '—' }}</td>
                    <td class="text-muted" style="width: 18%;">Employee Code:</td>
                    <td class="fw-bold font-monospace" style="width: 32%;">{{ $item->employee ? $item->employee->employee_id : '—' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Designation:</td>
                    <td class="fw-semibold">{{ $item->employee ? $item->employee->designation : '—' }}</td>
                    <td class="text-muted">CNIC #:</td>
                    <td class="font-monospace">{{ $item->employee ? $item->employee->cnic : '—' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Branch / Hall:</td>
                    <td>{{ $item->branch ? $item->branch->name : ($item->employee && $item->employee->branch ? $item->employee->branch->name : 'All Branches') }}</td>
                    <td class="text-muted">Joining Date:</td>
                    <td>{{ $item->employee && $item->employee->joining_date ? \Carbon\Carbon::parse($item->employee->joining_date)->format('d M, Y') : '—' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Working Days:</td>
                    <td><strong>{{ $item->total_working_days }}</strong> Days</td>
                    <td class="text-muted">Attendance Summary:</td>
                    <td>
                        <span class="badge bg-success-subtle text-success border">{{ $item->present_days }} Present</span>
                        <span class="badge bg-danger-subtle text-danger border">{{ $item->absent_days }} Absent</span>
                        @if($item->late_days > 0)<span class="badge bg-warning-subtle text-warning border">{{ $item->late_days }} Late</span>@endif
                        @if($item->half_days > 0)<span class="badge bg-info-subtle text-info border">{{ $item->half_days }} Half</span>@endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Earnings vs Deductions Grid -->
        <div class="row g-3 mb-3">
            <!-- Earnings Column -->
            <div class="col-6">
                <table class="table salary-table mb-0 w-100">
                    <thead>
                        <tr>
                            <th>Earnings / Additions</th>
                            <th class="text-end">Amount (PKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Salary</td>
                            <td class="text-end fw-semibold">{{ number_format($item->basic_salary, 2) }}</td>
                        </tr>
                        @if($item->overtime_amount > 0)
                            <tr>
                                <td>
                                    Overtime Pay
                                    <div class="fs-11 text-muted">{{ $item->overtime_hours }} hrs @ Rs. {{ number_format($item->overtime_rate, 2) }}</div>
                                </td>
                                <td class="text-end fw-semibold text-info">{{ number_format($item->overtime_amount, 2) }}</td>
                            </tr>
                        @endif

                        @php
                            $allowances = (array) ($item->allowances ?? []);
                        @endphp
                        @if(!empty($allowances['bonus']) && $allowances['bonus'] > 0)
                            <tr>
                                <td>Performance Bonus</td>
                                <td class="text-end text-success">{{ number_format($allowances['bonus'], 2) }}</td>
                            </tr>
                        @endif
                        @if(!empty($allowances['conveyance']) && $allowances['conveyance'] > 0)
                            <tr>
                                <td>Conveyance Allowance</td>
                                <td class="text-end text-success">{{ number_format($allowances['conveyance'], 2) }}</td>
                            </tr>
                        @endif
                        @if(!empty($allowances['medical']) && $allowances['medical'] > 0)
                            <tr>
                                <td>Medical Allowance</td>
                                <td class="text-end text-success">{{ number_format($allowances['medical'], 2) }}</td>
                            </tr>
                        @endif
                        @if(!empty($allowances['other']) && $allowances['other'] > 0)
                            <tr>
                                <td>Other Allowances</td>
                                <td class="text-end text-success">{{ number_format($allowances['other'], 2) }}</td>
                            </tr>
                        @endif

                        <tr class="total-row">
                            <td class="fw-bold">Total Gross Earnings</td>
                            <td class="text-end fw-bold text-success">PKR {{ number_format($item->gross_salary, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Deductions Column -->
            <div class="col-6">
                <table class="table salary-table mb-0 w-100">
                    <thead>
                        <tr>
                            <th>Deductions / Recoveries</th>
                            <th class="text-end">Amount (PKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($item->absence_deduction > 0)
                            <tr>
                                <td>
                                    Absence Deduction
                                    <div class="fs-11 text-muted">{{ $item->absent_days }} Absent {{ $item->half_days > 0 ? '+ ' . $item->half_days . ' Half' : '' }}</div>
                                </td>
                                <td class="text-end text-danger">{{ number_format($item->absence_deduction, 2) }}</td>
                            </tr>
                        @endif
                        @if($item->advance_deduction > 0)
                            <tr>
                                <td>
                                    Salary Advance Recovery
                                    <div class="fs-11 text-muted">Mid-month loan settlement</div>
                                </td>
                                <td class="text-end text-danger">{{ number_format($item->advance_deduction, 2) }}</td>
                            </tr>
                        @endif
                        @if($item->late_deduction > 0)
                            <tr>
                                <td>Late In Deduction</td>
                                <td class="text-end text-danger">{{ number_format($item->late_deduction, 2) }}</td>
                            </tr>
                        @endif

                        @php
                            $deds = (array) ($item->other_deductions ?? []);
                        @endphp
                        @if(!empty($deds['fine']) && $deds['fine'] > 0)
                            <tr>
                                <td>Fine / Disciplinary Penalty</td>
                                <td class="text-end text-danger">{{ number_format($deds['fine'], 2) }}</td>
                            </tr>
                        @endif
                        @if(!empty($deds['other']) && $deds['other'] > 0)
                            <tr>
                                <td>Other Deductions</td>
                                <td class="text-end text-danger">{{ number_format($deds['other'], 2) }}</td>
                            </tr>
                        @endif

                        @if($item->total_deductions == 0)
                            <tr>
                                <td class="text-muted fst-italic">No deductions applicable</td>
                                <td class="text-end text-muted">0.00</td>
                            </tr>
                        @endif

                        <tr class="total-row">
                            <td class="fw-bold">Total Deductions</td>
                            <td class="text-end fw-bold text-danger">PKR {{ number_format($item->total_deductions, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Net Take-Home Salary Highlight Box -->
        <div class="net-salary-box">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="fs-11 text-uppercase text-muted fw-bold">Net Salary Payable</span>
                    <h3 class="mb-0 fw-bold text-success">PKR {{ number_format($item->net_salary, 2) }}</h3>
                    <div class="fs-11 text-dark mt-1">
                        Amount in Words: <em>{{ $item->amount_in_words ?? (app(\App\Services\PaymentVoucherService::class)->numberToWords($item->net_salary)) }}</em>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fs-11 text-muted">Payment Status:</div>
                    @if($item->payment_status === 'paid')
                        <span class="badge bg-success px-3 py-1">PAID</span>
                        <div class="fs-11 text-muted mt-1">
                            Paid via {{ ucfirst($item->payment_method ?? 'Cash') }}
                            @if($item->paymentVoucher) &bull; PV: {{ $item->paymentVoucher->voucher_no }} @endif
                        </div>
                    @else
                        <span class="badge bg-warning text-dark px-3 py-1">UNPAID / PENDING</span>
                    @endif
                </div>
            </div>
        </div>

        @if($item->notes)
            <div class="alert alert-light border py-2 px-3 fs-11 mb-3">
                <strong>Remarks / Notes:</strong> {{ $item->notes }}
            </div>
        @endif

        <!-- Signatures Footer -->
        <div class="row pt-4">
            <div class="col-4">
                <div class="signature-box">
                    <strong>Prepared By</strong><br>
                    Accounts / HR Executive
                </div>
            </div>
            <div class="col-4">
                <div class="signature-box">
                    <strong>Approved By</strong><br>
                    Owner / General Manager
                </div>
            </div>
            <div class="col-4">
                <div class="signature-box">
                    <strong>Received By</strong><br>
                    Employee Signature
                </div>
            </div>
        </div>

        <div class="text-center text-muted fs-11 mt-4 pt-2 border-top">
            This is a computer-generated salary payslip from Marquee CMS &bull; Date Printed: {{ date('d M, Y h:i A') }}
        </div>
    </div>

</body>
</html>
