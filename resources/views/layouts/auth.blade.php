<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login') — AMINEF Staff Portal</title>
    <link rel="stylesheet" href="{{ asset('css/aminef.css') }}">
    <style>
        body {
            background: #eef4fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .split-auth-card {
            width: 100%;
            max-width: 880px;
            background: #fff;
            border-radius: 10px;
            border: 1px solid #dbe5f0;
            box-shadow: 0 18px 42px rgba(0, 61, 165, 0.12);
            display: grid;
            grid-template-columns: 42% 58%;
            overflow: hidden;
        }
        .split-auth-left {
            background: linear-gradient(155deg, #004c8c 0%, #0077c8 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }
        .split-auth-logo-band {
            background: #ffffff;
            padding: 28px 26px 22px;
            border-bottom: 3px solid #00a9e0;
            text-align: center;
        }
        .split-auth-left-body {
            padding: 28px 30px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex: 1;
        }
        .split-auth-heading {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.3;
            margin: 22px 0 10px;
            color: #fff;
        }
        .split-auth-desc {
            font-size: 13px;
            line-height: 1.55;
            color: #dbeafe;
            margin: 0;
        }
        .split-auth-meta {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 11.5px;
            line-height: 1.6;
            color: #e0f2fe;
        }
        .split-auth-right {
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth-card-header {
            background: #f8fafc;
            padding: 18px 28px;
            border-bottom: 1px solid #e2e8f0;
        }
        .auth-card-header h1 { margin: 0; font-size: 17px; font-weight: 700; color: #0f172a; }
        .auth-card-header p { margin: 4px 0 0; font-size: 12.5px; color: #64748b; }
        .auth-card-body { padding: 26px 28px; }
        .btn-login-primary {
            width: 100%;
            background: #0077c8;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 10px 16px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-login-primary:hover { background: #005fa3; }
        .auth-footer {
            margin-top: 16px;
            font-size: 11.5px;
            color: #64748b;
            text-align: center;
        }
        @media (max-width: 768px) {
            .split-auth-card { grid-template-columns: 1fr; max-width: 440px; }
            .split-auth-logo-band { padding: 20px; }
            .split-auth-left-body { padding: 20px; }
            .split-auth-meta { display: none; }
            .auth-card-header, .auth-card-body { padding-left: 20px; padding-right: 20px; }
        }
    </style>
</head>
<body>
    <div class="split-auth-card">
        <div class="split-auth-left">
            <div class="split-auth-logo-band">
                <img src="{{ asset('images/logo-aminef-color.png') }}" alt="AMINEF Logo" style="height:68px;width:auto;object-fit:contain;display:block;margin:0 auto;">
            </div>
            <div class="split-auth-left-body">
                <div>
                    <h2 class="split-auth-heading" style="margin-top:8px;">AMINEF Staff Portal</h2>
                    <p class="split-auth-desc">
                        Manage leave requests, team availability calendar, supervisor approvals, and official absence records in one place.
                    </p>
                </div>
                <div class="split-auth-meta">
                    <div><strong>AMINEF Jakarta Office</strong></div>
                    <div>Intiland Tower, Lantai 11 &bull; Jl. Jend. Sudirman Kav. 32</div>
                    <div>Office Hours: Mon&ndash;Fri, 08:00&ndash;17:00 WIB</div>
                </div>
            </div>
        </div>
        <div class="split-auth-right">
            @yield('content')
        </div>
    </div>
    <div class="auth-footer">
        &copy; {{ now()->year }} AMINEF &bull; American Indonesian Exchange Foundation
    </div>
</body>
</html>
