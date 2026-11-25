<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #1e293b; line-height: 1.6;">
    <div style="max-width:600px;margin:0 auto;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;">
        <div style="background:#081d38;color:#fff;padding:16px 20px;">
            <strong style="font-size:16px;">AMINEF &bull; Staff Portal</strong>
        </div>
        <div style="padding:20px;">
            <p>A Comp-Time request has been <strong>APPROVED</strong>:</p>
            <table style="width:100%;border-collapse:collapse;font-size:13px;margin:14px 0;">
                <tr><td style="padding:6px;border:1px solid #e2e8f0;background:#f8fafc;width:35%;">Employee</td><td style="padding:6px;border:1px solid #e2e8f0;">{{ $compTime->user->name }}</td></tr>
                <tr><td style="padding:6px;border:1px solid #e2e8f0;background:#f8fafc;">Overtime Date</td><td style="padding:6px;border:1px solid #e2e8f0;">{{ $compTime->work_date->format('d M Y') }}</td></tr>
                <tr><td style="padding:6px;border:1px solid #e2e8f0;background:#f8fafc;">Days Credited</td><td style="padding:6px;border:1px solid #e2e8f0;">{{ $compTime->days_requested }} day(s)</td></tr>
                <tr><td style="padding:6px;border:1px solid #e2e8f0;background:#f8fafc;">Approved By</td><td style="padding:6px;border:1px solid #e2e8f0;">{{ $compTime->supervisor?->name ?? 'Supervisor' }}</td></tr>
            </table>
            <p>The signed Comp-Time approval PDF is attached for your records.</p>
        </div>
    </div>
</body>
</html>
