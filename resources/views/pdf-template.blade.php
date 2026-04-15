<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $request->ref_number }} — STRG Request</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', 'Liberation Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            padding: 20px;
            line-height: 1.4;
        }

        @page {
            margin: 20mm;
            size: A4;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #1F3864;
            padding-bottom: 12px;
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .header .logo-text {
            font-size: 18px;
            font-weight: bold;
            color: #1F3864;
            letter-spacing: 1px;
            font-family: 'Georgia', serif;
        }
        .header .sub {
            font-size: 10px;
            color: #555;
            margin-top: 3px;
            font-style: italic;
        }
        .header h1 {
            font-size: 14px;
            margin-top: 8px;
            color: #1F3864;
            font-weight: bold;
        }

        .ref-badge {
            display: inline-block;
            background: #1F3864;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin: 8px 0;
            font-family: 'Courier New', monospace;
        }

        .section {
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .section-title {
            background: #1F3864;
            color: white;
            padding: 6px 12px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 6px;
            border-radius: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.info-table td {
            padding: 6px 10px;
            border: 1px solid #ccc;
            vertical-align: top;
            font-size: 10px;
        }
        table.info-table td.label {
            background: #EEF2FF;
            font-weight: bold;
            width: 28%;
            white-space: nowrap;
            color: #1F3864;
        }
        table.info-table td.value {
            width: 22%;
            word-wrap: break-word;
        }

        table.vot-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.vot-table th {
            background: #1F3864;
            color: white;
            padding: 6px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.vot-table td {
            padding: 6px 10px;
            border: 1px solid #ccc;
            font-size: 10px;
        }
        table.vot-table tr:nth-child(even) td { background: #F0F4FF; }
        table.vot-table td.amount {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        table.vot-table tfoot td {
            font-weight: bold;
            background: #DCE6F1;
            font-size: 11px;
        }

        .description-box {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 60px;
            background: #FAFAFA;
            font-size: 10px;
            line-height: 1.5;
            page-break-inside: avoid;
        }

        /* ── Signature section ─────────────────────────── */
        .signature-section {
            margin-top: 20px;
            border-top: 2px solid #1F3864;
            padding-top: 16px;
            page-break-inside: avoid;
        }

        /* Shared two-column grid */
        .sig-row {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .sig-cell {
            display: table-cell;
            width: 50%;
            padding: 0 8px;
            vertical-align: top;
        }
        .sig-cell:first-child { padding-left: 0; }
        .sig-cell:last-child  { padding-right: 0; }

        /* Single-column (full-width) for dean in 3-sig */
        .sig-row-single {
            display: block;
            width: 50%;
            margin-top: 16px;
        }

        .sig-role-label {
            font-size: 9px;
            font-weight: bold;
            color: #1F3864;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }
        .sig-box {
            border: 1px solid #bbb;
            height: 75px;
            background: #FAFAFA;
            text-align: center;
            position: relative;
        }
        .sig-box img.sig-img {
            max-width: 100%;
            max-height: 65px;
            margin-top: 4px;
        }
        .sig-box .sig-placeholder {
            color: #bbb;
            font-size: 9px;
            line-height: 75px;
        }

        /* Underline rows below the box */
        .sig-info {
            margin-top: 6px;
            font-size: 9px;
            color: #333;
        }
        .sig-info .sig-line {
            border-bottom: 1px solid #555;
            padding-bottom: 2px;
            margin-bottom: 4px;
            min-height: 14px;
        }
        .sig-info .sig-field-label {
            font-size: 8px;
            color: #888;
        }
        /* ─────────────────────────────────────────────── */

        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            page-break-inside: avoid;
        }

        @media print {
            body { padding: 0; }
            .section { page-break-inside: avoid; }
            .signature-section { page-break-inside: avoid; }
            .footer { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="logo-text">UNIVERSITI KUALA LUMPUR (UniKL)</div>
        <div class="sub">Student Travel Research Grant (STRG) System</div>
        <h1>STRG REQUEST FORM</h1>
        <div class="ref-badge">{{ $request->ref_number }}</div>
        <div style="font-size:10px; color:#555; margin-top:4px;">
            Submitted: {{ $request->submitted_at?->format('d F Y, H:i:s') ?? $request->created_at->format('d F Y, H:i:s') }}
            &nbsp;|&nbsp;
            Status: <strong>{{ $request->statusLabel() }}</strong>
        </div>
    </div>

    <!-- Applicant Information -->
    <div class="section">
        <div class="section-title">Applicant Information</div>
        <table class="info-table">
            <tr>
                <td class="label">Full Name</td>
                <td class="value">{{ $request->user->name }}</td>
                <td class="label">Staff ID</td>
                <td class="value">{{ $request->submitter_staff_id ?? $request->user->staff_id ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td class="value">{{ $request->user->email }}</td>
                <td class="label">Phone</td>
                <td class="value">{{ $request->submitter_phone ?? $request->user->phone ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Designation</td>
                <td class="value">{{ $request->submitter_designation ?? $request->user->designation ?? '—' }}</td>
                <td class="label">Employee Level</td>
                <td class="value">{{ $request->submitter_employee_level ?? $request->user->employee_level ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Department</td>
                <td class="value" colspan="3">{{ $request->submitter_department ?? $request->user->department ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <!-- Request Details -->
    <div class="section">
        <div class="section-title">Request Details</div>
        <table class="info-table">
            <tr>
                <td class="label">Request Type</td>
                <td class="value">{{ $request->requestType->name }}</td>
                <td class="label">Priority</td>
                <td class="value">{{ $request->priorityLabel() }}</td>
            </tr>
            <tr>
                <td class="label">Deadline</td>
                <td class="value">{{ $request->deadline?->format('d F Y') ?? 'None specified' }}</td>
                <td class="label">Revision No.</td>
                <td class="value">{{ $request->revision_count > 0 ? '#' . $request->revision_count : 'Original' }}</td>
            </tr>
        </table>
    </div>

    <!-- Dynamic Request Type Fields -->
    @if($request->requestType->field_schema && !empty($request->payload['dynamic_fields']))
        <div class="section">
            <div class="section-title">{{ $request->requestType->name }} Details</div>
            <table class="info-table">
                @foreach($request->requestType->field_schema as $field)
                    @php
                        $fieldValue = $request->payload['dynamic_fields'][$field['name']] ?? null;
                        $displayValue = '';
                        if ($fieldValue !== null) {
                            switch ($field['type']) {
                                case 'textarea':
                                    $displayValue = nl2br(e($fieldValue));
                                    break;
                                case 'date':
                                    $displayValue = \Carbon\Carbon::parse($fieldValue)->format('d F Y');
                                    break;
                                case 'number':
                                    $displayValue = 'RM ' . number_format($fieldValue, 2);
                                    break;
                                default:
                                    $displayValue = e($fieldValue);
                            }
                        }
                    @endphp
                    <tr>
                        <td class="label">{{ $field['label'] }}</td>
                        <td class="value" colspan="3">
                            @if($fieldValue !== null)
                                {!! $displayValue !!}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <!-- Justification / Description -->
    <div class="section">
        <div class="section-title">Justification / Description</div>
        <div class="description-box">{{ $request->payload['description'] ?? 'No description provided.' }}</div>
    </div>

    <!-- VOT Breakdown -->
    <div class="section">
        <div class="section-title">Budget / VOT Breakdown</div>
        @php $votItems = $request->getVotItems(); @endphp
        @if(!empty($votItems))
            <table class="vot-table">
                <thead>
                    <tr>
                        <th style="width:15%">VOT Code</th>
                        <th style="width:55%">Description</th>
                        <th style="width:30%; text-align:right">Amount (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($votItems as $item)
                        <tr>
                            <td>{{ $item['vot_code'] ?? '—' }}</td>
                            <td>{{ $item['description'] ?? '—' }}</td>
                            <td class="amount">{{ number_format((float)($item['amount'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align:right; padding-right:12px; font-weight:bold;">TOTAL</td>
                        <td class="amount">RM {{ number_format((float)$request->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @else
            <p style="padding:8px; color:#888; font-style:italic;">No VOT items recorded.</p>
        @endif
    </div>

    <!-- Return / Rejection Reason -->
    @if($request->rejection_reason)
    <div class="section">
        <div class="section-title" style="background:#991B1B;">Return / Rejection Reason</div>
        <div class="description-box" style="border-color:#FCA5A5; background:#FFF5F5;">{{ $request->rejection_reason }}</div>
    </div>
    @endif

    <!-- ══════════════════════════════════════════════
         DECLARATION & SIGNATURES
         ══════════════════════════════════════════════ -->
    <div class="signature-section">
        <div class="section-title">Declaration &amp; Signatures</div>
        <p style="font-size:10px; color:#444; margin: 8px 0 4px;">
            I hereby declare that the information provided in this form is true and accurate to the best of my knowledge.
        </p>

        @php
            /**
             * Per-role lookup table.
             * Add any new role here if it needs a custom name/date/designation source.
             * The PDF will render whatever roles are in $signatureSlots — no further
             * changes needed in this view when adding request types.
             */
            $slotData = [
                'applicant' => [
                    'image'       => $request->getSignatureImageForRole('applicant'),
                    'name'        => $request->user->name,
                    'designation' => $request->submitter_designation ?? $request->user->designation ?? '',
                    'date'        => $request->signed_at?->format('d/m/Y'),
                    'pending'     => 'No signature on file',
                ],
                'staff1' => [
                    'image'       => $request->getSignatureImageForRole('staff1'),
                    'name'        => $request->verifiedBy?->name ?? '',
                    'designation' => $request->verifiedBy?->designation ?? '',
                    'date'        => $request->verified_at?->format('d/m/Y'),
                    'pending'     => 'Pending',
                ],
                'staff2' => [
                    'image'       => $request->getSignatureImageForRole('staff2'),
                    'name'        => $request->recommendedBy?->name ?? '',
                    'designation' => $request->recommendedBy?->designation ?? '',
                    'date'        => $request->getSignedAtForRole('staff2')?->format('d/m/Y'),
                    'pending'     => 'Pending',
                ],
                'dean' => [
                    'image'       => $request->getSignatureImageForRole('dean'),
                    'name'        => $request->deanApprovedBy?->name ?? '',
                    'designation' => $request->deanApprovedBy?->designation ?? 'Dean',
                    'date'        => $request->getSignedAtForRole('dean')?->format('d/m/Y'),
                    'pending'     => 'Pending',
                ],
            ];

            // Render slots in rows of 2 (left + right columns)
            $rows = array_chunk($signatureSlots, 2);
        @endphp

        @foreach($rows as $row)
        <div class="sig-row">
            @foreach($row as $slot)
                @php
                    $data  = $slotData[$slot['role']] ?? [
                        // Unknown role — pull from signatures table generically
                        'image'       => $request->getSignatureImageForRole($slot['role']),
                        'name'        => '',
                        'designation' => '',
                        'date'        => $request->getSignedAtForRole($slot['role'])?->format('d/m/Y'),
                        'pending'     => 'Pending',
                    ];
                @endphp
                <div class="sig-cell">
                    <div class="sig-role-label">{{ $slot['label'] }}</div>
                    <div class="sig-box">
                        @if(!empty($data['image']))
                            <img src="{{ $data['image'] }}" class="sig-img" alt="{{ $slot['label'] }} Signature"/>
                        @else
                            <span class="sig-placeholder">{{ $data['pending'] }}</span>
                        @endif
                    </div>
                    <div class="sig-info">
                        <div class="sig-line">{{ $data['name'] }}</div>
                        <div class="sig-field-label">Name</div>
                        <div class="sig-line" style="margin-top:6px;">{{ $data['designation'] }}</div>
                        <div class="sig-field-label">Designation</div>
                        <div class="sig-line" style="margin-top:6px;">{{ $data['date'] ?? '' }}</div>
                        <div class="sig-field-label">Date</div>
                    </div>
                </div>
            @endforeach

            {{-- If this row has only 1 slot, add an empty spacer cell to keep layout --}}
            @if(count($row) === 1)
                <div class="sig-cell"></div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Footer -->
    <div class="footer">
        Generated by UniKL STRG Portal &nbsp;|&nbsp; {{ now()->format('d F Y, H:i:s') }}
        &nbsp;|&nbsp; Reference: {{ $request->ref_number }}
        &nbsp;|&nbsp; This is a system-generated document.
    </div>
</body>
</html>
