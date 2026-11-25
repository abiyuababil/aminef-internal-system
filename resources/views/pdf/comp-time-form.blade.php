<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        .header { background: #072a5a; color: #fff; padding: 14px 18px; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 17px; }
        .header p { margin: 3px 0 0; font-size: 11px; color: #bae6fd; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.info td { border: 1px solid #cbd5e1; padding: 8px 10px; }
        table.info td.lbl { background: #f8fafc; font-weight: bold; width: 32%; }
        table.sigs { width: 100%; margin-top: 28px; border-collapse: collapse; }
        table.sigs td { width: 50%; text-align: center; vertical-align: top; padding: 10px; }
        .sig-box { height: 75px; margin: 10px 0; }
        .sig-box img { max-height: 70px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AMINEF &mdash; COMP-TIME APPROVAL FORM</h1>
        <p>Fulbright Indonesia &bull; Human Resources Department</p>
    </div>

    <table class="info">
        <tr><td class="lbl">Request ID</td><td>#CT-{{ str_pad((string) $compTime->id, 5, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><td class="lbl">Employee Name</td><td>{{ $compTime->user->name }}</td></tr>
        <tr><td class="lbl">Department / Position</td><td>{{ $compTime->user->department ?? '-' }} / {{ $compTime->user->position ?? '-' }}</td></tr>
        <tr><td class="lbl">Overtime Work Date</td><td>{{ $compTime->work_date->format('d M Y') }}</td></tr>
        <tr><td class="lbl">Comp-Time Days Earned</td><td>{{ $compTime->days_requested }} day(s)</td></tr>
        <tr><td class="lbl">Reason / Task</td><td>{{ $compTime->reason }}</td></tr>
        <tr><td class="lbl">Status</td><td><strong>APPROVED</strong> ({{ $compTime->approved_at?->format('d M Y H:i') }})</td></tr>
    </table>

    <table class="sigs">
        <tr>
            <td>
                <div><strong>Requested By (Staff)</strong></div>
                <div class="sig-box">
                    @if($staffSigDataUri)
                        <img src="{{ $staffSigDataUri }}" alt="Staff Signature">
                    @endif
                </div>
                <div><u>{{ $compTime->user->name }}</u></div>
            </td>
            <td>
                <div><strong>Approved By (Supervisor)</strong></div>
                <div class="sig-box">
                    @if($supervisorSigDataUri)
                        <img src="{{ $supervisorSigDataUri }}" alt="Supervisor Signature">
                    @endif
                </div>
                <div><u>{{ $compTime->supervisor?->name ?? 'Supervisor' }}</u></div>
            </td>
        </tr>
    </table>
</body>
</html>
