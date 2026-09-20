<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.email_storage_quota_title') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #f3f4f6;
            margin: 0;
            padding: 16px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }
        .accent-line {
            height: 3px;
            background: linear-gradient(90deg, transparent, {{ $gradientStart }}, {{ $gradientEnd }}, {{ $gradientStart }}, transparent);
        }
        .header {
            background: linear-gradient(180deg, {{ $headerBgStart }}, {{ $headerBgEnd }});
            padding: 36px 32px 28px;
            text-align: center;
        }
        .logo-icon {
            display: inline-block;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            margin-bottom: 16px;
        }
        .header h1 {
            color: #111827;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.03em;
        }
        .header-sub {
            color: #6b7280;
            font-size: 14px;
            margin: 6px 0 0;
        }
        .badge {
            display: inline-block;
            background: linear-gradient(135deg, {{ $gradientStart }}, {{ $gradientEnd }});
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 32px;
        }
        .content p {
            margin: 0 0 16px;
            color: #4b5563;
            font-size: 15px;
        }
        .gauge {
            text-align: center;
            margin: 8px 0;
        }
        .gauge-value {
            display: block;
            font-size: 40px;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: {{ $gradientEnd }};
        }
        .gauge-label {
            display: block;
            color: #6b7280;
            font-size: 13px;
            margin-top: 2px;
        }
        .figures {
            display: table;
            width: 100%;
            margin: 20px 0 0;
            border-spacing: 8px 0;
        }
        .figure {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 12px 4px;
            background-color: #f9fafb;
            border-radius: 10px;
            font-size: 12px;
            color: #6b7280;
            vertical-align: middle;
        }
        .figure strong {
            display: block;
            color: #111827;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .warning {
            background-color: #fefce8;
            border-left: 3px solid #eab308;
            padding: 12px 16px;
            margin: 24px 0 0;
            border-radius: 0 10px 10px 0;
        }
        .warning p {
            margin: 0 !important;
            color: #854d0e !important;
            font-size: 13px !important;
        }
        .footer {
            padding: 24px 32px;
            text-align: center;
            background-color: #f9fafb;
            border-top: 1px solid #f3f4f6;
        }
        .footer p {
            margin: 0;
            color: #9ca3af;
            font-size: 12px;
            line-height: 1.5;
        }
        .footer-brand {
            color: #9ca3af;
            font-size: 11px;
            margin-top: 6px;
        }
        .footer-brand a {
            color: {{ $gradientStart }};
            text-decoration: none;
            font-weight: 500;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background-color: #0f172a;
            }
            .container {
                background-color: #1e293b;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
            }
            .header {
                background: linear-gradient(180deg, {{ $headerBgDarkStart }}, {{ $headerBgDarkEnd }});
            }
            .header h1 {
                color: #f1f5f9;
            }
            .header-sub {
                color: #94a3b8;
            }
            .content p {
                color: #cbd5e1;
            }
            .gauge-value {
                color: {{ $gaugeDarkColor }};
            }
            .gauge-label {
                color: #94a3b8;
            }
            .figure {
                background-color: #0f172a;
                color: #94a3b8;
            }
            .figure strong {
                color: #f1f5f9;
            }
            .warning {
                background-color: #422006;
                border-left-color: #ca8a04;
            }
            .warning p {
                color: #fef08a !important;
            }
            .footer {
                background-color: #162031;
                border-top-color: #334155;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="accent-line"></div>

        <div class="header">
            <img src="{{ asset('icon-192-amber.png') }}" alt="" width="56" height="56" class="logo-icon" style="width:56px;height:56px;border-radius:16px;">
            <h1>{{ config('app.name') }}</h1>
            <p class="header-sub">{{ __('messages.email_storage_quota_title') }}</p>
            <span class="badge">{{ $levelLabel }}</span>
        </div>

        <div class="content">
            <div class="gauge">
                <span class="gauge-value">{{ $percent }}%</span>
                <span class="gauge-label">{{ __('messages.email_storage_quota_gauge_label') }}</span>
            </div>

            <div class="figures">
                <div class="figure">
                    <strong>{{ $used }}</strong>
                    {{ __('messages.email_storage_quota_used_label') }}
                </div>
                <div class="figure">
                    <strong>{{ $total }}</strong>
                    {{ __('messages.email_storage_quota_total_label') }}
                </div>
            </div>

            <div class="warning">
                <p>{{ __('messages.email_storage_quota_consequence') }}</p>
            </div>

            <p style="margin-top:24px;">{{ __('messages.email_storage_quota_action') }}</p>
        </div>

        <div class="footer">
            <p>{{ __('messages.email_footer', ['app' => config('app.name')]) }}</p>
            <p class="footer-brand">&copy; 2026 <a href="{{ url('/') }}">{{ config('app.name') }}</a></p>
        </div>
    </div>
</body>
</html>
