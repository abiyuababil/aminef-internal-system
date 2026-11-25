<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #1e293b; line-height: 1.6;">
    <div style="max-width:600px;margin:0 auto;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;">
        <div style="background:#081d38;color:#fff;padding:16px 20px;">
            <strong style="font-size:16px;">AMINEF &bull; Staff Portal</strong>
        </div>
        <div style="padding:20px;">
            <p>Dear {{ $recipientRoleLabel }},</p>
            <p>The following leave request has been <strong>APPROVED</strong>:</p>
            <table style="width:100%;border-collapse:collapse;font-size:13px;margin:14px 0;">
                <tr><td style="padding:6px;border:1px solid #e2e8f0;background:#f8fafc;width:35%;">Employee</td><td style="padding:6px;border:1px solid #e2e8f0;">{{ $leave->user->name }}</td></tr>
                <tr><td style="padding:6px;border:1px solid #e2e8f0;background:#f8fafc;">Leave Type</td><td style="padding:6px;border:1px solid #e2e8f0;">{{ $leave->typeLabel() }}</td></tr>
                <tr><td style="padding:6px;border:1px solid #e2e8f0;background:#f8fafc;">Dates</td><td style="padding:6px;border:1px solid #e2e8f0;">{{ $leave->start_date->format('d M Y') }} &ndash; {{ $leave->end_date->format('d M Y') }}</td></tr>
                <tr><td style="padding:6px;border:1px solid #e2e8f0;background:#f8fafc;">Working Days</td><td style="padding:6px;border:1px solid #e2e8f0;">{{ $leave->total_days }} day(s)</td></tr>
                <tr><td style="padding:6px;border:1px solid #e2e8f0;background:#f8fafc;">Approved By</td><td style="padding:6px;border:1px solid #e2e8f0;">{{ $leave->supervisor?->name ?? 'Supervisor' }}</td></tr>
            </table>
            <p>Attached to this email:</p>
            <ul>
                <li><strong>Outlook Calendar Invite (.ics)</strong> &mdash; open/accept to add directly to your Outlook Calendar.</li>
                <li><strong>Signed Leave Approval Form (.pdf)</strong> &mdash; official signed record.</li>
            </ul>
        </div>
    </div>
</body>
</html>
