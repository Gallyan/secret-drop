<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') - {{ config('app.name') }}@else{{ config('app.name') }} - {{ __('messages.app_description') }}@endif</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#8b5cf6">

    {{-- Fonts --}}
    <meta name="description" content="@yield('description', __('messages.app_description'))">

    {{-- SEO: noindex for sensitive pages --}}
    @if(View::hasSection('noindex') || request()->is('s/*', 'admin/*', 'superadmin/*'))
    <meta name="robots" content="noindex, nofollow">
    @endif

    {{-- Canonical URL, Open Graph, Twitter Card - disabled for sensitive pages --}}
    @unless(request()->is('s/*', 'admin/*', 'superadmin/*'))
    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Alternate languages --}}
    <link rel="alternate" hreflang="fr" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="en" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="es" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="it" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="de" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="pt" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="nl" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="pl" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="ja" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="ko" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="ar" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@hasSection('title')@yield('title') - {{ config('app.name') }}@else{{ config('app.name') }}@endif">
    <meta property="og:description" content="@yield('description', __('messages.app_description'))">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:image" content="{{ url('/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@hasSection('title')@yield('title') - {{ config('app.name') }}@else{{ config('app.name') }}@endif">
    <meta name="twitter:description" content="@yield('description', __('messages.app_description'))">
    <meta name="twitter:image" content="{{ url('/og-image.png') }}">
    @endunless

    {{-- Schema.org JSON-LD (only on homepage) --}}
    @if(request()->routeIs('home'))
    <script type="application/ld+json" nonce="@nonce">
    {
        "@@context": "https://schema.org",
        "@@type": "WebApplication",
        "name": "{{ config('app.name') }}",
        "description": "{{ __('messages.app_description') }}",
        "url": "{{ url('/') }}",
        "applicationCategory": "SecurityApplication",
        "applicationSubCategory": "File Sharing, Encryption",
        "operatingSystem": "Any",
        "browserRequirements": "Requires JavaScript, Web Crypto API",
        "inLanguage": ["en", "fr", "de", "es", "it", "pt", "nl", "pl", "ja", "ko", "ar"],
        "isAccessibleForFree": true,
        "offers": {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "EUR"
        },
        "featureList": [
            "{{ __('messages.feature_encryption') }}",
            "{{ __('messages.feature_zero_knowledge') }}",
            "{{ __('messages.feature_auto_destroy') }}",
            "{{ __('messages.feature_expiration') }}"
        ]
    }
    </script>
    @endif

    @php
        $jsTranslations = array_intersect_key(__('messages'), array_flip([
            'btn_encrypting',
            'btn_encrypting_upload',
            'captcha_invalid',
            'crypto_clipboard_failed',
            'crypto_creation_error',
            'crypto_decryption_error',
            'crypto_decryption_failed',
            'crypto_enter_secret',
            'crypto_file_download_failed',
            'crypto_fragment_invalid',
            'crypto_not_supported',
            'crypto_passphrase_incorrect',
            'crypto_passphrase_required',
            'crypto_select_file',
            'decrypting_file',
            'decrypting_message',
            'encrypted_end_to_end_file',
            'encrypted_end_to_end_message',
            'error_connection',
            'error_loading',
            'file_too_large',
            'qr_generation_failed',
            'secret_expired',
            'secret_file',
            'secret_max_views',
            'secret_message',
            'secret_not_exist',
            'secret_revoked',
            'secret_unavailable_generic',
            'unit_bytes',
            'unit_kilobytes',
            'unit_megabytes',
            'a11y_show_passphrase',
            'a11y_hide_passphrase',
        ]));
    @endphp
    <script nonce="@nonce">
        {{-- Only expose translations needed by JS --}}
        window.translations = @json($jsTranslations);
    </script>
    <script nonce="@nonce">
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'light') {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased min-h-screen">
    <div class="fixed top-4 end-4 z-50">
        <x-theme-toggle />
    </div>

    <main>
        @yield('content')
    </main>

    <footer class="fixed bottom-0 inset-x-0 z-30 py-3 text-center text-sm text-gray-500 dark:text-slate-400 transition-colors backdrop-blur-sm bg-white/30 dark:bg-slate-900/30">
        <nav aria-label="{{ __('messages.a11y_footer_nav') }}" class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 px-4">
            <span>&copy; {{ date('Y') }} <a href="{{ route('home') }}" class="hover:text-gray-700 dark:hover:text-slate-200 transition-colors">{{ config('app.name') }}</a></span>
            <span aria-hidden="true">·</span>
            <a href="{{ route('how-it-works') }}" class="hover:text-gray-700 dark:hover:text-slate-200 transition-colors">
                {{ __('messages.footer_how_it_works') }}
            </a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('use-cases') }}" class="hover:text-gray-700 dark:hover:text-slate-200 transition-colors">
                {{ __('messages.footer_use_cases') }}
            </a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('admin.index') }}" class="hover:text-gray-700 dark:hover:text-slate-200 transition-colors">
                {{ __('messages.footer_manage') }}
            </a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('legal') }}" class="hover:text-gray-700 dark:hover:text-slate-200 transition-colors">
                {{ __('messages.footer_legal') }}
            </a>
        </nav>
    </footer>
</body>
</html>
