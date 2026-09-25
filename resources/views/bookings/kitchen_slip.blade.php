<!DOCTYPE html>
<html lang="{{ $lang === 'urdu' ? 'ur' : 'en' }}" dir="{{ $lang === 'urdu' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Menu Slip — {{ $booking->booking_number }} (V{{ $booking->kitchen_print_version }})</title>

    <!-- Google Fonts for English & Urdu Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Noto+Nastaliq+Urdu:wght@400;700&family=Noto+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Dynamic @page CSS for Paper Size (A5 vs A4) -->
    @php
        $activePaper = $paper ?? 'a5';
    @endphp
    <style id="print-page-style">
        @page {
            size: {{ $activePaper === 'a4' ? 'A4' : 'A5' }} portrait;
            margin: {{ $activePaper === 'a4' ? '8mm 10mm 8mm 10mm' : '3mm 4mm 3mm 4mm' }};
        }
    </style>

    <style>
        body {
            font-family: 'Inter', 'Noto Sans Arabic', sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        .urdu-font {
            font-family: 'Noto Nastaliq Urdu', 'Noto Sans Arabic', serif;
            line-height: 1.4;
        }

        /* Base Container Styles */
        .slip-container {
            margin: 10px auto;
            background: #ffffff;
            border-radius: 6px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            border: 1px solid #cbd5e1;
            transition: max-width 0.2s ease, padding 0.2s ease;
        }

        /* ----------------------------------------------------
           A5 COMPACT MODE (Engineered to fit 20 Dishes on 1 Page)
           ---------------------------------------------------- */
        .slip-container.paper-a5 {
            max-width: 580px; /* Exact A5 proportion preview */
            padding: 10px 14px;
            font-size: 11px;
            line-height: 1.22;
        }

        .slip-container.paper-a5 .header-border {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }

        .slip-container.paper-a5 .header-logo {
            height: 34px;
            width: auto;
        }

        .slip-container.paper-a5 .marquee-title {
            font-size: 14.5px;
            font-weight: 800;
            line-height: 1.1;
            margin: 0;
            letter-spacing: -0.2px;
        }

        .slip-container.paper-a5 .branch-meta {
            font-size: 9.5px;
            line-height: 1.15;
            color: #475569;
        }

        .slip-container.paper-a5 .slip-title {
            font-size: 13.5px;
            font-weight: 900;
            line-height: 1.1;
            margin: 0;
        }

        .slip-container.paper-a5 .slip-subtitle-ur {
            font-size: 11px;
            line-height: 1.1;
            color: #475569;
            font-weight: 700;
        }

        .slip-container.paper-a5 .version-badge {
            background-color: #dc3545;
            color: #ffffff;
            font-weight: 800;
            font-size: 9px;
            padding: 1px 6px;
            border-radius: 12px;
        }

        .slip-container.paper-a5 .info-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 4px 8px;
            margin-bottom: 5px;
        }

        .slip-container.paper-a5 .info-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #475569;
            font-weight: 800;
            letter-spacing: 0.2px;
            line-height: 1;
            margin-bottom: 1px;
        }

        .slip-container.paper-a5 .info-value {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.15;
        }

        .slip-container.paper-a5 .info-value-badge {
            font-size: 9.5px;
            padding: 1px 5px;
            font-weight: 700;
        }

        .slip-container.paper-a5 .dept-header {
            background-color: #1e293b;
            color: #ffffff;
            padding: 2.5px 8px;
            font-size: 11.5px;
            font-weight: 700;
            border-radius: 3px;
            margin-top: 5px;
            margin-bottom: 2px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            line-height: 1.2;
        }

        .slip-container.paper-a5 .dept-count {
            font-size: 10px;
            opacity: 0.9;
            font-family: monospace;
        }

        .slip-container.paper-a5 .table-kitchen {
            border: 1px solid #cbd5e1;
            margin-bottom: 0;
            font-size: 11px;
        }

        .slip-container.paper-a5 .table-kitchen th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            border-bottom: 1.5px solid #94a3b8;
            padding: 2.5px 5px !important;
            font-size: 10px;
            line-height: 1.15;
        }

        .slip-container.paper-a5 .table-kitchen td {
            padding: 2.5px 5px !important;
            font-size: 11px;
            border-color: #e2e8f0;
            line-height: 1.18;
        }

        .slip-container.paper-a5 .dish-name-en {
            font-size: 11.5px;
            font-weight: 700;
            color: #0f172a;
            display: inline-block;
        }

        .slip-container.paper-a5 .dish-name-ur {
            font-size: 11.5px;
            font-weight: 700;
            color: #334155;
            display: inline-block;
            margin-left: 4px;
        }

        .slip-container.paper-a5 .instruction-cell {
            font-size: 10.5px;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.15;
        }

        .slip-container.paper-a5 .instructions-box {
            background-color: #fffbeb;
            border: 1px dashed #f59e0b;
            border-radius: 4px;
            padding: 4px 8px;
            margin-top: 5px;
            font-size: 10.5px;
            line-height: 1.25;
        }

        .slip-container.paper-a5 .print-footer {
            border-top: 1px solid #cbd5e1;
            margin-top: 5px;
            padding-top: 3px;
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.2;
        }

        /* ----------------------------------------------------
           A4 STANDARD MODE (Spacious Multi-Page or Larger View)
           ---------------------------------------------------- */
        .slip-container.paper-a4 {
            max-width: 860px;
            padding: 20px 28px;
            font-size: 13px;
            line-height: 1.4;
        }

        .slip-container.paper-a4 .header-border {
            border-bottom: 2.5px solid #1e293b;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .slip-container.paper-a4 .header-logo {
            height: 55px;
            width: auto;
        }

        .slip-container.paper-a4 .marquee-title {
            font-size: 20px;
            font-weight: 800;
            line-height: 1.2;
            margin: 0;
        }

        .slip-container.paper-a4 .branch-meta {
            font-size: 12px;
            color: #64748b;
        }

        .slip-container.paper-a4 .slip-title {
            font-size: 18px;
            font-weight: 900;
        }

        .slip-container.paper-a4 .slip-subtitle-ur {
            font-size: 14px;
            font-weight: 700;
            color: #475569;
        }

        .slip-container.paper-a4 .version-badge {
            background-color: #dc3545;
            color: #ffffff;
            font-weight: 800;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 20px;
        }

        .slip-container.paper-a4 .info-card {
            background-color: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 12px;
        }

        .slip-container.paper-a4 .info-label {
            font-size: 10.5px;
            text-transform: uppercase;
            color: #475569;
            font-weight: 700;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }

        .slip-container.paper-a4 .info-value {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .slip-container.paper-a4 .info-value-badge {
            font-size: 11px;
            padding: 2px 7px;
            font-weight: 700;
        }

        .slip-container.paper-a4 .dept-header {
            background-color: #1e293b;
            color: #ffffff;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 5px;
            margin-top: 12px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .slip-container.paper-a4 .table-kitchen th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            border-bottom: 2px solid #94a3b8;
            padding: 6px 8px !important;
            font-size: 12.5px;
        }

        .slip-container.paper-a4 .table-kitchen td {
            padding: 6px 8px !important;
            font-size: 13px;
            border-color: #e2e8f0;
        }

        .slip-container.paper-a4 .dish-name-en {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }

        .slip-container.paper-a4 .dish-name-ur {
            font-size: 14.5px;
            font-weight: 700;
            color: #334155;
            display: block;
        }

        .slip-container.paper-a4 .instruction-cell {
            font-size: 12.5px;
            font-weight: 600;
            color: #1e293b;
        }

        .slip-container.paper-a4 .instructions-box {
            background-color: #fffbeb;
            border: 1.5px dashed #f59e0b;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 12px;
            font-size: 13px;
        }

        .slip-container.paper-a4 .print-footer {
            border-top: 1px solid #cbd5e1;
            margin-top: 14px;
            padding-top: 8px;
            font-size: 10px;
            color: #64748b;
        }

        /* ----------------------------------------------------
           PRINT ENGINE MEDIA QUERY (A5 & A4 Precise Calibration)
           ---------------------------------------------------- */
        @media print {
            html, body {
                background-color: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .slip-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            /* A5 Print Overrides: Guaranteed Single Page for 20 Dishes */
            .slip-container.paper-a5 {
                font-size: 9.5px !important;
                line-height: 1.15 !important;
            }

            .slip-container.paper-a5 .header-border {
                padding-bottom: 2px !important;
                margin-bottom: 3px !important;
                border-bottom: 1.5px solid #000000 !important;
            }

            .slip-container.paper-a5 .header-logo {
                height: 28px !important;
            }

            .slip-container.paper-a5 .marquee-title {
                font-size: 13px !important;
                line-height: 1.1 !important;
            }

            .slip-container.paper-a5 .branch-meta {
                font-size: 8px !important;
                line-height: 1.1 !important;
                color: #333333 !important;
            }

            .slip-container.paper-a5 .slip-title {
                font-size: 12.5px !important;
                line-height: 1 !important;
            }

            .slip-container.paper-a5 .slip-subtitle-ur {
                font-size: 9.5px !important;
                line-height: 1 !important;
            }

            .slip-container.paper-a5 .version-badge {
                font-size: 8px !important;
                padding: 1px 4px !important;
                background-color: #dc3545 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .slip-container.paper-a5 .info-card {
                padding: 2.5px 5px !important;
                margin-bottom: 3px !important;
                background-color: #f8fafc !important;
                border: 1px solid #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .slip-container.paper-a5 .info-label {
                font-size: 7.5px !important;
                line-height: 1 !important;
                margin-bottom: 1px !important;
                color: #000000 !important;
            }

            .slip-container.paper-a5 .info-value {
                font-size: 10px !important;
                line-height: 1.1 !important;
                color: #000000 !important;
            }

            .slip-container.paper-a5 .dept-header {
                background-color: #1e293b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin-top: 3px !important;
                margin-bottom: 1px !important;
                padding: 1.5px 5px !important;
                font-size: 10px !important;
                line-height: 1.15 !important;
                page-break-after: avoid !important;
            }

            .slip-container.paper-a5 .dept-count {
                font-size: 9px !important;
            }

            .slip-container.paper-a5 .table-kitchen {
                border: 1px solid #000000 !important;
                margin-bottom: 0 !important;
                page-break-inside: auto !important;
            }

            .slip-container.paper-a5 .table-kitchen tr {
                page-break-inside: avoid !important;
            }

            .slip-container.paper-a5 .table-kitchen th {
                background-color: #f1f5f9 !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 1.5px 4px !important;
                font-size: 9px !important;
                line-height: 1.1 !important;
                border: 1px solid #000000 !important;
            }

            .slip-container.paper-a5 .table-kitchen td {
                padding: 1.5px 4px !important;
                font-size: 10px !important;
                color: #000000 !important;
                line-height: 1.15 !important;
                border: 1px solid #cbd5e1 !important;
            }

            .slip-container.paper-a5 .dish-name-en {
                font-size: 10.5px !important;
                font-weight: 700 !important;
                color: #000000 !important;
            }

            .slip-container.paper-a5 .dish-name-ur {
                font-size: 11px !important;
                font-weight: 700 !important;
                color: #000000 !important;
            }

            .slip-container.paper-a5 .instruction-cell {
                font-size: 9.5px !important;
                font-weight: 600 !important;
                color: #000000 !important;
            }

            .slip-container.paper-a5 .instructions-box {
                padding: 2.5px 6px !important;
                margin-top: 3px !important;
                border: 1px dashed #000000 !important;
                font-size: 9px !important;
                line-height: 1.2 !important;
            }

            .slip-container.paper-a5 .print-footer {
                margin-top: 3px !important;
                padding-top: 2px !important;
                font-size: 8px !important;
                line-height: 1.15 !important;
                border-top: 1px solid #000000 !important;
            }

            /* A4 Print Overrides */
            .slip-container.paper-a4 {
                font-size: 12px !important;
                line-height: 1.3 !important;
            }

            .slip-container.paper-a4 .table-kitchen tr {
                page-break-inside: avoid !important;
            }

            .slip-container.paper-a4 .dept-header {
                background-color: #1e293b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                page-break-after: avoid !important;
            }

            .slip-container.paper-a4 .table-kitchen th {
                background-color: #f1f5f9 !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 4px 6px !important;
                font-size: 12px !important;
                border: 1px solid #000000 !important;
            }

            .slip-container.paper-a4 .table-kitchen td {
                padding: 4px 6px !important;
                font-size: 12.5px !important;
                color: #000000 !important;
                border: 1px solid #cbd5e1 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar (Hidden during printing) -->
    <div class="container no-print mt-2 mb-2" style="max-width: 860px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center bg-white p-2 rounded shadow-sm border">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-secondary fs-12 text-uppercase">Page Format:</span>
                <div class="btn-group btn-group-sm" role="group" aria-label="Paper Size">
                    <button type="button" id="btn-paper-a5" onclick="switchPaperSize('a5')" class="btn {{ $activePaper === 'a5' ? 'btn-primary' : 'btn-outline-secondary' }} px-3 fw-bold">
                        <i class="fas fa-file-alt me-1"></i> A5 (1 Page / 20 Dishes)
                    </button>
                    <button type="button" id="btn-paper-a4" onclick="switchPaperSize('a4')" class="btn {{ $activePaper === 'a4' ? 'btn-primary' : 'btn-outline-secondary' }} px-3 fw-bold">
                        <i class="fas fa-file me-1"></i> A4 Standard
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 mt-1 mt-md-0">
                <button onclick="window.print()" class="btn btn-success btn-sm px-3 fw-bold shadow-sm">
                    <i class="fas fa-print me-1"></i> Print Kitchen Slip
                </button>
                <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fas fa-times me-1"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- Main Kitchen Menu Slip Printable Container -->
    <div class="slip-container {{ $activePaper === 'a4' ? 'paper-a4' : 'paper-a5' }}">

        <!-- Top Header -->
        <div class="header-border d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                @if(!empty($marquee->logo))
                    @php
                        $marqueeLogoUrl = Str::startsWith($marquee->logo, ['http://', 'https://']) 
                            ? $marquee->logo 
                            : (Str::startsWith($marquee->logo, 'storage/') ? asset($marquee->logo) : asset('storage/' . $marquee->logo));
                    @endphp
                    <img src="{{ $marqueeLogoUrl }}" alt="Logo" class="header-logo me-2">
                @endif
                <div>
                    <h3 class="marquee-title text-uppercase">{{ $marquee->name ?? 'Marquee CMS' }}</h3>
                    @if($branch)
                        <div class="branch-meta text-primary fw-bold text-uppercase">{{ $branch->name }} @if($branch->is_head_office)(Head Office)@endif</div>
                        <div class="branch-meta">{{ $branch->address ? $branch->address . ', ' : '' }}{{ $branch->city ?? ($marquee->city ?? '') }}</div>
                        @if($branch->phone || ($marquee->phone ?? null))
                            <div class="branch-meta"><i class="fas fa-phone me-1"></i> {{ $branch->phone ?: $marquee->phone }} @if($branch->branch_manager) | Mgr: {{ $branch->branch_manager }} @endif</div>
                        @endif
                    @else
                        <div class="branch-meta fw-semibold">{{ $marquee->address ?? 'Main Branch' }} — {{ $marquee->city ?? 'Pakistan' }}</div>
                        <div class="branch-meta"><i class="fas fa-phone me-1"></i> {{ $marquee->phone ?? '' }}</div>
                    @endif
                </div>
            </div>
            <div class="text-end">
                <div class="d-flex align-items-center justify-content-end gap-1 mb-1">
                    <span class="version-badge">VERSION V{{ $booking->kitchen_print_version }}</span>
                </div>
                <h4 class="slip-title text-primary">KITCHEN MENU SLIP</h4>
                <div class="urdu-font slip-subtitle-ur">کچن مینو آرڈر سلپ</div>
            </div>
        </div>

        <!-- Booking Operational Information Grid -->
        <div class="info-card">
            <div class="row g-1">
                <!-- Booking Number -->
                <div class="col-3">
                    <div class="info-label">
                        @if($lang === 'english') Booking # @elseif($lang === 'urdu') بکنگ نمبر @else Booking # / بکنگ نمبر @endif
                    </div>
                    <div class="info-value text-primary font-monospace">{{ $booking->booking_number }}</div>
                </div>

                <!-- Customer Name -->
                <div class="col-3">
                    <div class="info-label">
                        @if($lang === 'english') Customer Name @elseif($lang === 'urdu') گاہک کا نام @else Customer Name / گاہک کا نام @endif
                    </div>
                    <div class="info-value text-truncate" title="{{ $booking->customer->full_name ?? '—' }}">
                        {{ $booking->customer->full_name ?? '—' }}
                    </div>
                </div>

                <!-- Event Date -->
                <div class="col-3">
                    <div class="info-label">
                        @if($lang === 'english') Event Date @elseif($lang === 'urdu') تقریب کی تاریخ @else Event Date / تاریخ @endif
                    </div>
                    <div class="info-value text-danger">
                        <i class="fas fa-calendar-alt me-1"></i>{{ $booking->booking_date->format('D, d M Y') }}
                    </div>
                </div>

                <!-- Confirmed Headcount -->
                <div class="col-3 text-end">
                    <div class="info-label">
                        @if($lang === 'english') Confirmed Guests @elseif($lang === 'urdu') کل مہمان @else Confirmed Guests / مہمان @endif
                    </div>
                    <div class="info-value text-success">
                        <i class="fas fa-users me-1"></i>{{ number_format($booking->effective_guest_count) }} Persons
                    </div>
                </div>

                <!-- Event Type -->
                <div class="col-3">
                    <div class="info-label">
                        @if($lang === 'english') Event Type @elseif($lang === 'urdu') تقریب کی قسم @else Event Type / تقریب @endif
                    </div>
                    <div class="info-value">{{ $booking->eventType->event_type_name ?? 'Banquet' }}</div>
                </div>

                <!-- Venue / Hall -->
                <div class="col-3">
                    <div class="info-label">
                        @if($lang === 'english') Hall / Venue @elseif($lang === 'urdu') ہال / وینیو @else Hall / Venue / ہال @endif
                    </div>
                    <div class="info-value">
                        @if($booking->halls->isNotEmpty())
                            {{ $booking->halls->pluck('hall_name')->implode(', ') }}
                        @else
                            {{ $booking->hall->hall_name ?? 'Main Hall' }}
                        @endif
                        @if($branch)
                            <span class="text-muted fw-normal" style="font-size: 8.5px;">({{ $branch->name }})</span>
                        @endif
                    </div>
                </div>

                <!-- Shift Slot -->
                <div class="col-3">
                    <div class="info-label">
                        @if($lang === 'english') Shift Slot @elseif($lang === 'urdu') شفٹ سلاٹ @else Shift Slot / شفٹ سلاٹ @endif
                    </div>
                    <div class="info-value">
                        <span class="badge bg-secondary text-white info-value-badge">
                            <i class="fas fa-layer-group me-1"></i>{{ $booking->slot->slot_name ?? 'Custom Shift' }}
                        </span>
                    </div>
                </div>

                <!-- Shift / Timings -->
                <div class="col-3 text-end">
                    <div class="info-label">
                        @if($lang === 'english') Event Timings @elseif($lang === 'urdu') اوقات @else Event Timings / وقت @endif
                    </div>
                    <div class="info-value font-monospace text-dark">
                        <i class="far fa-clock me-1 text-primary"></i>{{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Operational Department-Wise Menu Tables -->
        @forelse($groupedMenuItems as $deptName => $deptData)
            <div class="dept-header">
                <div>
                    @if($lang === 'english' || $lang === 'bilingual')
                        <span><i class="fas fa-utensils me-1"></i>{{ strtoupper($deptData['title_en']) }}</span>
                    @endif
                    @if($lang === 'bilingual' && !empty($deptData['title_ur']))
                        <span class="ms-1">/ {{ $deptData['title_ur'] }}</span>
                    @endif
                    @if($lang === 'urdu')
                        <span class="urdu-font fw-bold">{{ $deptData['title_ur'] ?: $deptData['title_en'] }}</span>
                    @endif
                </div>
                <div class="dept-count">
                    {{ count($deptData['items']) }} {{ count($deptData['items']) === 1 ? 'Dish' : 'Dishes' }}
                </div>
            </div>

            <table class="table table-bordered table-sm table-kitchen align-middle">
                {{--<thead>
                    <tr>
                        <th style="width: 7%;" class="text-center">#</th>
                        <th style="width: 53%;">
                            @if($lang === 'english') Dish / Item Name @endif
                            @if($lang === 'urdu') <span class="urdu-font">ڈش / مینو ائٹم</span> @endif
                            @if($lang === 'bilingual') Dish Name / ڈش کا نام @endif
                        </th>
                        <th style="width: 40%;">
                            @if($lang === 'english') Serving Notes / Instructions @endif
                            @if($lang === 'urdu') <span class="urdu-font">خصوصی ہدایت</span> @endif
                            @if($lang === 'bilingual') Instructions / خصوصی ہدایت @endif
                        </th>
                    </tr>
                </thead>--}}
                <tbody>
                    @foreach($deptData['items'] as $index => $item)
                        @php
                            $instructionNote = trim($item->pivot->custom_note ?? '');
                            if (strcasecmp($instructionNote, 'Standard Preparation') === 0) {
                                $instructionNote = '';
                            }
                        @endphp
                        <tr>
                            <td class="text-center fw-bold text-secondary">{{ $index + 1 }}</td>
                            <td>
                                @if($lang === 'english')
                                    <span class="dish-name-en">{{ $item->item_name }}</span>
                                @elseif($lang === 'urdu')
                                    <span class="urdu-font dish-name-ur">{{ $item->urdu_name ?: $item->item_name }}</span>
                                @else
                                    <span class="dish-name-en">{{ $item->item_name }}</span>
                                    @if(!empty($item->urdu_name))
                                        <span class="urdu-font dish-name-ur text-secondary">({{ $item->urdu_name }})</span>
                                    @endif
                                @endif
                            </td>
                            <td class="instruction-cell">
                                @if(!empty($instructionNote))
                                    <span>{{ $instructionNote }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <div class="text-center py-3 border rounded text-muted">
                <i class="fas fa-exclamation-circle me-1"></i> No finalized menu items attached to this booking.
            </div>
        @endforelse

        <!-- Special Kitchen Instructions -->
        @if(!empty($booking->kitchen_special_instructions) || !empty($booking->special_instructions))
            <div class="instructions-box">
                <div class="fw-bold text-dark mb-0">
                    <i class="fas fa-exclamation-triangle text-warning me-1"></i> SPECIAL KITCHEN INSTRUCTIONS / خصوصی کچن ہدایات:
                </div>
                <div class="text-dark fw-bold">
                    {{ $booking->kitchen_special_instructions ?? $booking->special_instructions }}
                </div>
            </div>
        @endif

        <!-- Footer Audit Trail (Excludes All Financials) -->
        <div class="print-footer d-flex justify-content-between align-items-center">
            <div>
                <div><strong>Printed On:</strong> {{ now()->format('d-M-Y h:i A') }}</div>
                <div><strong>Printed By:</strong> {{ auth()->user()->name ?? 'System User' }} (Role: {{ auth()->user()->role->name ?? 'Staff' }})</div>
            </div>
            <div class="text-center border px-2 py-0 rounded bg-light">
                <div class="fw-bold text-danger">KITCHEN COPY</div>
                <div class="urdu-font text-secondary" style="font-size: 8.5px;">کچن کاپی</div>
            </div>
            <div class="text-end">
                <div><strong>System Reference:</strong> BK-SLIP-V{{ $booking->kitchen_print_version }}</div>
                <div>MarqueeCMS Enterprise SaaS</div>
            </div>
        </div>

    </div>

    <!-- Client-side Paper Size Switcher -->
    <script>
        function switchPaperSize(size) {
            const container = document.querySelector('.slip-container');
            const styleEl = document.getElementById('print-page-style');
            const btnA5 = document.getElementById('btn-paper-a5');
            const btnA4 = document.getElementById('btn-paper-a4');

            if (size === 'a5') {
                container.classList.remove('paper-a4');
                container.classList.add('paper-a5');
                styleEl.innerHTML = '@page { size: A5 portrait; margin: 3mm 4mm 3mm 4mm; }';
                btnA5.classList.add('btn-primary');
                btnA5.classList.remove('btn-outline-secondary');
                btnA4.classList.add('btn-outline-secondary');
                btnA4.classList.remove('btn-primary');
                try { localStorage.setItem('kitchen_slip_paper_size', 'a5'); } catch(e){}
            } else {
                container.classList.remove('paper-a5');
                container.classList.add('paper-a4');
                styleEl.innerHTML = '@page { size: A4 portrait; margin: 8mm 10mm 8mm 10mm; }';
                btnA4.classList.add('btn-primary');
                btnA4.classList.remove('btn-outline-secondary');
                btnA5.classList.add('btn-outline-secondary');
                btnA5.classList.remove('btn-primary');
                try { localStorage.setItem('kitchen_slip_paper_size', 'a4'); } catch(e){}
            }
        }

        // Initialize from localStorage if URL parameter wasn't explicitly supplied
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('paper')) {
                try {
                    const saved = localStorage.getItem('kitchen_slip_paper_size');
                    if (saved === 'a4' || saved === 'a5') {
                        switchPaperSize(saved);
                    }
                } catch(e){}
            }
        });
    </script>

</body>
</html>
