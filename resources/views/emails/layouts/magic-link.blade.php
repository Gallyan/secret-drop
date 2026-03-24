<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #f3f4f6;
            margin: 0;
            padding: 32px 16px;
        }
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        .accent-line {
            height: 3px;
            background: linear-gradient(90deg, transparent, #8b5cf6, #6366f1, #8b5cf6, transparent);
        }
        .header {
            padding: 32px 32px 24px;
            text-align: center;
        }
        .logo-icon {
            display: inline-block;
            width: 48px;
            height: 48px;
            border-radius: 14px;
            margin-bottom: 16px;
        }
        .header h1 {
            color: #111827;
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .badge {
            display: inline-block;
            background: linear-gradient(135deg, @yield('gradient-start', '#8b5cf6'), @yield('gradient-end', '#6366f1'));
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            margin: 0 32px;
        }
        .content {
            padding: 28px 32px 32px;
        }
        .content p {
            margin: 0 0 16px;
            color: #4b5563;
            font-size: 15px;
        }
        .button-wrapper {
            text-align: center;
            margin: 24px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, @yield('gradient-start', '#8b5cf6'), @yield('gradient-end', '#6366f1'));
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: -0.01em;
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
        .url-section {
            margin-top: 24px;
        }
        .url-label {
            font-size: 13px;
            color: #9ca3af;
            margin: 0 0 8px;
        }
        .url-box {
            background-color: #f9fafb;
            border: 1px solid #f3f4f6;
            border-radius: 10px;
            padding: 12px 16px;
            word-break: break-all;
            font-family: ui-monospace, 'SF Mono', SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
        }
        .url-box a {
            color: #6b7280;
            text-decoration: none;
        }
        .footer {
            padding: 20px 32px;
            text-align: center;
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
            margin-top: 4px;
        }
        .footer-brand a {
            color: #8b5cf6;
            text-decoration: none;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background-color: #0f172a;
            }
            .container {
                background-color: #1e293b;
                border-color: #334155;
            }
            .header h1 {
                color: #f1f5f9;
            }
            .divider {
                background: linear-gradient(90deg, transparent, #334155, transparent);
            }
            .content p {
                color: #cbd5e1;
            }
            .warning {
                background-color: #422006;
                border-left-color: #ca8a04;
            }
            .warning p {
                color: #fef08a !important;
            }
            .url-box {
                background-color: #0f172a;
                border-color: #1e293b;
            }
            .url-box a {
                color: #94a3b8;
            }
            .footer {
                border-top-color: #334155;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="accent-line"></div>

            <div class="header">
                {{-- Lock icon matching favicon --}}
                <img src="{{ asset('favicon.png') }}" alt="" width="48" height="48" class="logo-icon" style="width:48px;height:48px;border-radius:14px;">
                <h1>{{ config('app.name') }}</h1>
                @yield('badge')
            </div>

            <div class="divider"></div>

            <div class="content">
                <p>@yield('intro')</p>

                <div class="button-wrapper">
                    <a href="{{ $verifyUrl }}" class="button">@yield('button-text')</a>
                </div>

                <div class="warning">
                    <p>{{ __('messages.email_magic_link_warning') }}</p>
                </div>

                <div class="url-section">
                    <p class="url-label">{{ __('messages.email_link_label') }}</p>
                    <div class="url-box">
                        <a href="{{ $verifyUrl }}">{{ $verifyUrl }}</a>
                    </div>
                </div>
            </div>

            <div class="footer">
                <p>{{ __('messages.email_footer', ['app' => config('app.name')]) }}</p>
                <p class="footer-brand">&copy; {{ date('Y') }} <a href="{{ url('/') }}">Perceptron Systems</a></p>
            </div>
        </div>
    </div>
</body>
</html>
