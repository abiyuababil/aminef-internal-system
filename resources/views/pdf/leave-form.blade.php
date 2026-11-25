<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 26px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; line-height: 1.4; }
        .form-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 12px;
        }
        table.leave-form {
            width: 100%;
            border-collapse: collapse;
        }
        table.leave-form td, table.leave-form th {
            border: 1px solid #111827;
            padding: 6px 8px;
            vertical-align: top;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .checkbox-list { line-height: 1.75; font-size: 10.5px; }
        .cb {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #111827;
            text-align: center;
            line-height: 10px;
            font-size: 9px;
            font-weight: bold;
            margin-right: 4px;
            vertical-align: middle;
        }
        .blank-row td { height: 18px; }
        .underline-val { border-bottom: 1px solid #111827; padding: 0 10px 1px; display: inline-block; min-width: 165px; }
        .small-cert { font-size: 9.5px; line-height: 1.45; margin-top: 8px; text-align: justify; }
        table.sig-layout { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.sig-layout td { border: none; padding: 0; vertical-align: bottom; }
        .sig-line-cell {
            border-bottom: 1px solid #111827 !important;
            text-align: center;
            height: 54px;
            padding-bottom: 2px !important;
        }
        .sig-img { max-height: 48px; max-width: 160px; }
    </style>
</head>
<body>
    @php
        $ranges = $leave->date_ranges;
        if (empty($ranges) || ! is_array($ranges)) {
            $ranges = [[
                'start_date' => $leave->start_date->toDateString(),
                'end_date' => $leave->end_date->toDateString(),
                'start_time' => '08:00',
                'end_time' => '16:00',
                'days' => (float) $leave->total_days,
            ]];
        }
        $maxRows = max(7, count($ranges));
        $logoFile = public_path('images/logo-aminef-color.png');
        $logoDataUri = file_exists($logoFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile)) : null;
        $cornerFile = public_path('images/pdf-corner-right.png');
        $cornerDataUri = file_exists($cornerFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($cornerFile)) : null;
    @endphp

    @if($cornerDataUri)
        <div style="position:fixed;top:-26px;right:-32px;z-index:-1;">
            <img src="{{ $cornerDataUri }}" alt="" style="width:190px;height:auto;">
        </div>
    @endif

    <div style="height:105px;">
        @if($logoDataUri)
            <div style="text-align:left;padding-top:10px;">
                <img src="{{ $logoDataUri }}" alt="AMINEF" style="height:44px;width:auto;">
            </div>
        @endif
    </div>
    <div class="form-title">Request For Leave or Approved Absence Form</div>

    <table class="leave-form">
        <tr>
            <td colspan="6"><strong>Name :</strong> {{ $leave->user->name }}</td>
        </tr>
        <tr>
            <td rowspan="2" style="width:32%;" class="bold">
                Type of Leave /Absence<br>
                <span style="font-weight:normal;font-size:9.5px;">(Check appropriate box(es) below)</span>
            </td>
            <td colspan="2" class="center bold">DATE:</td>
            <td colspan="2" class="center bold">TIME:</td>
            <td rowspan="2" class="center bold" style="width:14%;">TOTAL DAYS/<br>HOURS:</td>
        </tr>
        <tr>
            <td class="center bold" style="width:14%;">FROM:</td>
            <td class="center bold" style="width:14%;">TO:</td>
            <td class="center bold" style="width:13%;">FROM:</td>
            <td class="center bold" style="width:13%;">TO:</td>
        </tr>

        @for($r = 0; $r < $maxRows; $r++)
            @php $row = $ranges[$r] ?? null; @endphp
            <tr class="{{ $row ? '' : 'blank-row' }}">
                @if($r === 0)
                    <td rowspan="{{ $maxRows }}" class="checkbox-list">
                        <div><span class="cb">{{ $leave->type === 'annual' ? 'x' : '' }}</span> Annual Leave</div>
                        <div><span class="cb">{{ $leave->type === 'comp_time' ? 'x' : '' }}</span> Compensatory Time Off</div>
                        <div><span class="cb">{{ $leave->type === 'leave_with_pay' ? 'x' : '' }}</span> Leave With Pay</div>
                        <div><span class="cb">{{ $leave->type === 'leave_without_pay' ? 'x' : '' }}</span> Leave Without Pay</div>
                        <div><span class="cb">{{ $leave->type === 'sick' ? 'x' : '' }}</span> Sick Leave</div>
                    </td>
                @endif

                @if($row)
                    <td class="center">{{ \Carbon\Carbon::parse($row['start_date'])->format('M/d/Y') }}</td>
                    <td class="center">{{ \Carbon\Carbon::parse($row['end_date'])->format('M/d/Y') }}</td>
                    <td class="center">{{ str_replace(':', ' : ', $row['start_time'] ?? '08:00') }}</td>
                    <td class="center">{{ str_replace(':', ' : ', $row['end_time'] ?? '16:00') }}</td>
                    <td class="center bold">{{ rtrim(rtrim(number_format((float) ($row['days'] ?? $leave->total_days), 1), '0'), '.') }} {{ (float) ($row['days'] ?? $leave->total_days) == 1.0 ? 'Day' : 'Days' }}</td>
                @else
                    <td></td><td></td><td></td><td></td><td></td>
                @endif
            </tr>
        @endfor

        <tr>
            <td colspan="6">
                <strong>Purpose:</strong>
                &nbsp; <span class="cb">{{ $leave->purpose === 'medical' ? 'x' : '' }}</span> Medical/Dental/Optical examination or Treatment of requesting employee
                &nbsp;&nbsp; <span class="cb">{{ ($leave->purpose ?? 'other') === 'other' ? 'x' : '' }}</span> Other
                &nbsp;&nbsp; <span class="cb">{{ $leave->purpose === 'maternity' ? 'x' : '' }}</span> Maternity Leave
                <div style="margin-top:4px;font-size:10px;color:#374151;">
                    <strong>Remarks:</strong> {{ $leave->reason }}
                </div>
            </td>
        </tr>

        <tr>
            <td colspan="6" style="padding:10px 10px 12px;">
                <div>
                    In my absence the following individual has been designated as being responsible for my duties:
                    <span class="underline-val">{{ $leave->pic_handover ?: '-' }}</span>
                    <span style="font-size:9.5px;">(employee full name)</span>
                </div>

                <div class="small-cert">
                    <strong>Certification:</strong> I hereby request leave/approved absence from duty as indicated above and certify that such leave/absence is request for the purpose(s) indicated. I understand that I must comply with employing procedures for requesting leave/absence (and provide additional documentation, including medical certification, if required) and that falsification of information on this form may be grounds for disciplinary action, including removal.
                </div>

                <table class="sig-layout">
                    <tr>
                        <td style="width:120px;">Employee Signature:</td>
                        <td class="sig-line-cell" style="width:215px;">
                            @if($staffSigDataUri)
                                <img src="{{ $staffSigDataUri }}" alt="Employee Signature" class="sig-img"><br>
                            @endif
                            <span style="font-size:10px;font-weight:bold;">{{ $leave->user->name }}@if($leave->user->position) ({{ $leave->user->position }})@endif</span>
                        </td>
                        <td style="width:26px;"></td>
                        <td style="width:38px;">Date:</td>
                        <td class="sig-line-cell" style="width:130px;height:auto;padding-bottom:2px !important;">
                            {{ $leave->created_at->format('F d, Y') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="6" style="padding:10px 10px 12px;">
                <table class="sig-layout" style="margin-top:2px;">
                    <tr>
                        <td style="width:120px;">Approved by:</td>
                        <td class="sig-line-cell" style="width:215px;">
                            @if($supervisorSigDataUri)
                                <img src="{{ $supervisorSigDataUri }}" alt="Supervisor Signature" class="sig-img"><br>
                            @endif
                            <span style="font-size:10px;font-weight:bold;">{{ $leave->supervisor?->name ?? 'Supervisor' }}@if($leave->supervisor?->position) ({{ $leave->supervisor->position }})@endif</span>
                        </td>
                        <td style="width:26px;"></td>
                        <td style="width:38px;">Date:</td>
                        <td class="sig-line-cell" style="width:130px;height:auto;padding-bottom:2px !important;">
                            {{ $leave->approved_at?->format('F d, Y') ?? '-' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
