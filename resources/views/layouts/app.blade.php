<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') - {{ config('app.name') }}@else{{ config('app.name') }} - {{ __('messages.app_description') }}@endif</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#8b5cf6">
    <meta name="description" content="@yield('description', __('messages.app_description'))">

    {{-- SEO: noindex for sensitive pages --}}
    @if(View::hasSection('noindex'))
    <meta name="robots" content="noindex, nofollow">
    @endif

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Alternate languages --}}
    <link rel="alternate" hreflang="fr" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="en" href="{{ url()->current() }}">
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@hasSection('title')@yield('title') - {{ config('app.name') }}@else{{ config('app.name') }}@endif">
    <meta property="og:description" content="@yield('description', __('messages.app_description'))">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@hasSection('title')@yield('title') - {{ config('app.name') }}@else{{ config('app.name') }}@endif">
    <meta name="twitter:description" content="@yield('description', __('messages.app_description'))">

    {{-- Schema.org JSON-LD (only on homepage) --}}
    @if(request()->routeIs('home'))
    <script type="application/ld+json" nonce="@nonce">
    {
        "@@context": "https://schema.org",
        "@@type": "WebApplication",
        "name": "{{ config('app.name') }}",
        "description": "{{ __('messages.app_description') }}",
        "url": "{{ config('app.url') }}",
        "applicationCategory": "SecurityApplication",
        "operatingSystem": "Any",
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

    <script nonce="@nonce">
        window.translations = @json(__('messages'));
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
    <div class="fixed top-4 right-4 z-50">
        <x-theme-toggle />
    </div>

    <main>
        @yield('content')
    </main>

    <footer class="fixed bottom-4 left-0 right-0 z-10 text-center text-sm text-gray-400 dark:text-slate-600 transition-colors">
        <div class="flex items-center justify-center gap-2">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
            <span>·</span>
            <a href="{{ route('admin.index') }}" class="hover:text-gray-600 dark:hover:text-slate-400 transition-colors">
                {{ __('messages.footer_manage') }}
            </a>
            <span>·</span>
            <a href="{{ route('legal') }}" class="hover:text-gray-600 dark:hover:text-slate-400 transition-colors">
                {{ __('messages.footer_legal') }}
            </a>
        </div>
    </footer>
</body>
</html>
