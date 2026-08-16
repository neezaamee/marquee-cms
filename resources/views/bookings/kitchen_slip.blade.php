<!DOCTYPE html>
<html lang="{{ $lang === 'urdu' ? 'ur' : 'en' }}" dir="{{ $lang === 'urdu' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Menu Slip — {{ $booking->booking_number }} (V{{ $booking->kitchen_print_version }})</title>

    <!-- Google Fonts for English & Urdu Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Noto+Nastaliq+Urdu:wght@400;700&family=Noto+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', 'Noto Sans Arabic', sans-serif;
            background-color: #f8f9fa;
            color: #1a1a1a;
            font-size: 11px;
            line-height: 1.25;
        }

        .urdu-font {
            font-family: 'Noto Nastaliq Urdu', 'Noto Sans Arabic', serif;
            line-height: 1.35;
        }

        .slip-container {
            max-width: 800px;
            margin: 5px auto;
            background: #ffffff;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid #e0e0e0;
        }

        .header-border {
            border-bottom: 2px solid #2b3445;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .dept-header {
            background-color: #2b3445;
            color: #ffffff;
            padding: 4px 10px;
            font-weight: 700;
            border-radius: 4px;
            margin-top: 10px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-card {
            background-color: #f4f6f9;
            border: 1px solid #e1e6ed;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
        }

        .info-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 700;
            margin-bottom: 1px;
        }

        .info-value {
            font-size: 12px;
            font-weight: 700;
            color: #1a202c;
        }

        .version-badge {
            background-color: #dc3545;
            color: #ffffff;
            font-weight: 800;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 20px;
        }

        .table-kitchen {
            border: 1px solid #dee2e6;
            margin-bottom: 0;
        }

        .table-kitchen th {
            background-color: #edf2f7;
            color: #2d3748;
            font-weight: 700;
            border-bottom: 2px solid #cbd5e0;
            padding: 3px 6px !important;
            font-size: 10px;
        }

        .table-kitchen td {
            padding: 3px 6px !important;
            font-size: 10px;
        }

        .instructions-box {
            background-color: #fff8e6;
            border: 1.5px dashed #f6ad55;
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 10px;
        }

        .print-footer {
            border-top: 1px solid #e2e8f0;
            margin-top: 15px;
            padding-top: 8px;
            font-size: 9px;
            color: #718096;
        }

        @media print {
            body {
                background-color: #ffffff;
                color: #000000;
                font-size: 9px;
            }
            .slip-container {
                box-shadow: none;
                border: none;
                padding: 0;
                margin: 0;
                width: 100%;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .dept-header {
                background-color: #1a202c !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin-top: 6px !important;
                margin-bottom: 3px !important;
                padding: 3px 8px !important;
            }
            .version-badge {
                background-color: #dc3545 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .table-kitchen th, .table-kitchen td {
                padding: 2px 4px !important;
                font-size: 9px !important;
            }
            .info-card {
                padding: 4px 8px !important;
                margin-bottom: 6px !important;
            }
            .header-border {
                padding-bottom: 3px !important;
                margin-bottom: 6px !important;
            }
            .print-footer {
                margin-top: 8px !important;
                padding-top: 4px !important;
            }
            .instructions-box {
                padding: 6px 10px !important;
                margin-top: 6px !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar (Hidden during printing) -->
    <div class="container no-print mt-3 mb-2 max-w-800 text-end" style="max-width: 800px;">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-3 fw-bold me-2">
            <i class="fas fa-print me-1"></i> Print Kitchen Slip
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
            <i class="fas fa-times me-1"></i> Close Window
        </button>
    </div>

    <!-- Main Kitchen Menu Slip Printable Container -->
    <div class="slip-container">

        <!-- Top Header -->
        <div class="header-border d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                @if(!empty($marquee->logo))
                    <img src="{{ Storage::url($marquee->logo) }}" alt="Logo" style="height: 60px; width: auto;" class="me-3">
                @endif
                <div>
                    <h3 class="fw-bold mb-0 text-uppercase">{{ $marquee->name ?? 'Marquee CMS' }}</h3>
                    <div class="text-secondary fw-semibold fs-12">{{ $branch->name ?? 'Main Branch' }} — {{ $marquee->city ?? 'Pakistan' }}</div>
                    <div class="text-muted fs-12"><i class="fas fa-phone me-1"></i> {{ $marquee->phone ?? '' }}</div>
                </div>
            </div>
            <div class="text-end">
                <div class="d-flex align-items-center justify-content-end gap-2 mb-1">
                    <span class="version-badge">VERSION V{{ $booking->kitchen_print_version }}</span>
                </div>
                <h4 class="fw-extrabold text-primary mb-0">KITCHEN MENU SLIP</h4>
                <div class="urdu-font fs-12 fw-bold text-secondary">کچن مینو آرڈر سلپ</div>
            </div>
        </div>

        <!-- Booking Operational Information Grid -->
        <div class="info-card">
            <div class="row g-3">
                <!-- Booking Number -->
                <div class="col-3">
                    <div class="info-label">Booking # / بکنگ نمبر</div>
                    <div class="info-value text-primary font-monospace">{{ $booking->booking_number }}</div>
                </div>

                <!-- Customer Name -->
                <div class="col-5">
                    <div class="info-label">Customer Name / گاہک کا نام</div>
                    <div class="info-value">
                        {{ $booking->customer->full_name ?? '—' }}
                    </div>
                </div>

                <!-- Event Date -->
                <div class="col-4 text-end">
                    <div class="info-label">Event Date / تاریخ</div>
                    <div class="info-value text-danger">
                        <i class="fas fa-calendar-alt me-1"></i>{{ $booking->booking_date->format('D, d M Y') }}
                    </div>
                </div>

                <!-- Event Type -->
                <div class="col-3">
                    <div class="info-label">Event Type / تقریب</div>
                    <div class="info-value">{{ $booking->eventType->event_type_name ?? 'Banquet' }}</div>
                </div>

                <!-- Venue / Hall -->
                <div class="col-3">
                    <div class="info-label">Hall / Venue / ہال</div>
                    <div class="info-value">{{ $booking->hall->hall_name ?? 'Main Hall' }}</div>
                </div>

                <!-- Shift / Timings -->
                <div class="col-3">
                    <div class="info-label">Event Shift / وقت</div>
                    <div class="info-value font-monospace">
                        {{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }}
                    </div>
                </div>

                <!-- Confirmed Headcount -->
                <div class="col-3 text-end">
                    <div class="info-label">Confirmed Guests / مہمان</div>
                    <div class="info-value text-success fs-13">
                        <i class="fas fa-users me-1"></i>{{ number_format($booking->effective_guest_count) }} Persons
                    </div>
                </div>
            </div>
        </div>

        <!-- Operational Department-Wise Menu Tables -->
        @forelse($groupedMenuItems as $deptName => $deptData)
            <div class="dept-header">
                <div>
                    @if($lang === 'english' || $lang === 'bilingual')
                        <span><i class="fas fa-utensils me-2"></i>{{ strtoupper($deptData['title_en']) }}</span>
                    @endif
                    @if($lang === 'bilingual' && !empty($deptData['title_ur']))
                        <span class="ms-2">/ {{ $deptData['title_ur'] }}</span>
                    @endif
                    @if($lang === 'urdu')
                        <span class="urdu-font fs-12">{{ $deptData['title_ur'] ?: $deptData['title_en'] }}</span>
                    @endif
                </div>
                <div class="fs-12 font-monospace fw-normal opacity-75">
                    {{ count($deptData['items']) }} {{ count($deptData['items']) === 1 ? 'Dish' : 'Dishes' }}
                </div>
            </div>

            <table class="table table-bordered table-sm table-kitchen align-middle">
                <thead>
                    <tr>
                        <th style="width: 8%;" class="text-center">#</th>
                        <th style="width: 52%;">
                            @if($lang === 'english') Dish / Item Name @endif
                            @if($lang === 'urdu') <span class="urdu-font">ڈش / مینو ائٹم</span> @endif
                            @if($lang === 'bilingual') Dish Name / ڈش کا نام @endif
                        </th>
                        <th style="width: 40%;">
                            @if($lang === 'english') Serving Notes @endif
                            @if($lang === 'urdu') <span class="urdu-font">خصوصی ہدایت</span> @endif
                            @if($lang === 'bilingual') Instructions / ہدایت @endif
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deptData['items'] as $index => $item)
                        @php
                            $qty = $booking->effective_guest_count;
                            $unit = $item->unit ?: 'Servings';
                        @endphp
                        <tr>
                            <td class="text-center fw-bold text-secondary">{{ $index + 1 }}</td>
                            <td class="fw-bold">
                                @if($lang === 'english')
                                    {{ $item->item_name }}
                                @elseif($lang === 'urdu')
                                    <span class="urdu-font fs-12">{{ $item->urdu_name ?: $item->item_name }}</span>
                                @else
                                    <div>{{ $item->item_name }}</div>
                                    @if(!empty($item->urdu_name))
                                        <div class="urdu-font text-secondary fs-12 mt-n1">{{ $item->urdu_name }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="fs-12 text-600">
                                {{ $item->pivot->custom_note ?? 'Standard Preparation' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <div class="text-center py-4 border rounded text-muted">
                <i class="fas fa-exclamation-circle me-1"></i> No finalized menu items attached to this booking.
            </div>
        @endforelse

        <!-- Special Kitchen Instructions -->
        @if(!empty($booking->kitchen_special_instructions) || !empty($booking->special_instructions))
            <div class="instructions-box">
                <div class="fw-bold text-dark mb-1">
                    <i class="fas fa-exclamation-triangle text-warning me-1"></i> SPECIAL KITCHEN INSTRUCTIONS / خصوصی کچن ہدایات:
                </div>
                <div class="fs-12 text-800 fw-semi-bold">
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
            <div class="text-center border px-3 py-1 rounded bg-light">
                <div class="fw-bold text-danger fs-12">KITCHEN COPY</div>
                <div class="urdu-font fs-11 text-secondary">کچن کاپی</div>
            </div>
            <div class="text-end">
                <div><strong>System Reference:</strong> BK-SLIP-V{{ $booking->kitchen_print_version }}</div>
                <div>MarqueeCMS Enterprise SaaS</div>
            </div>
        </div>

    </div>

</body>
</html>
