<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, @yield('gradient-start') 0%, @yield('gradient-end') 100%);
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 32px;
        }
        .content p {
            margin: 0 0 16px;
            color: #4b5563;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, @yield('gradient-start') 0%, @yield('gradient-end') 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 600;
            margin: 16px 0;
        }
        .button:hover {
            opacity: 0.9;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            margin: 24px 0;
            border-radius: 0 8px 8px 0;
        }
        .warning p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }
        .footer {
            padding: 24px 32px;
            background-color: #f9fafb;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 0;
            color: #9ca3af;
            font-size: 12px;
        }
        .url-box {
            background-color: #f3f4f6;
            border-radius: 8px;
            padding: 12px;
            margin: 16px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
            color: #6b7280;
        }
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1f2937;
            }
            .container {
                background-color: #1f2937;
                border: 1px solid #374151;
            }
            .content p {
                color: #d1d5db;
            }
            .warning {
                background-color: #451a03;
                border-left-color: #d97706;
            }
            .warning p {
                color: #fde68a;
            }
            .url-box {
                background-color: #111827;
                color: #9ca3af;
            }
            .url-box a {
                color: #9ca3af;
            }
            .footer {
                background-color: #111827;
                border-top-color: #374151;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
            @yield('badge')
        </div>

        <div class="content">
            <p>@yield('intro')</p>

            <p style="text-align: center;">
                <a href="{{ $verifyUrl }}" class="button">@yield('button-text')</a>
            </p>

            <div class="warning">
                <p>{{ __('messages.email_magic_link_warning') }}</p>
            </div>

            <p style="font-size: 14px; color: #6b7280;">{{ __('messages.email_link_label') }}</p>
            <div class="url-box"><a href="{{ $verifyUrl }}" style="color: #6b7280; text-decoration: none;">{{ $verifyUrl }}</a></div>
        </div>

        <div class="footer">
            <p>{{ __('messages.email_footer', ['app' => config('app.name')]) }}</p>
        </div>
    </div>
</body>
</html>
