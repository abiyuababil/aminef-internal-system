<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; color: #072a5a; margin-bottom: 4px; }
        p { margin: 0 0 14px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; border: 1px solid #cbd5e1; padding: 7px; text-align: left; }
        td { border: 1px solid #cbd5e1; padding: 7px; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h1>AMINEF &mdash; Annual Leave &amp; Comp-Time Recap ({{ $year }})</h1>
    <p>Generated on {{ now()->format('d M Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Department</th>
                <th class="num">Annual Used</th>
                <th class="num">Annual Remaining</th>
                <th class="num">Comp-Time Days</th>
                <th class="num">Sick Days</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $row)
                <tr>
                    <td>{{ $row['user']->name }}</td>
                    <td>{{ $row['user']->department ?? '-' }}</td>
                    <td class="num">{{ $row['annual_used'] }}</td>
                    <td class="num"><strong>{{ $row['user']->annual_leave_balance }}</strong></td>
                    <td class="num">{{ $row['comp_used'] }}</td>
                    <td class="num">{{ $row['sick_used'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
