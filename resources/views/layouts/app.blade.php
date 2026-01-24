<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Secret Drop') }}</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#8b5cf6">
    <meta name="description" content="{{ __('messages.app_description') }}">

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

    <footer class="fixed bottom-4 left-0 right-0 text-center text-sm text-gray-400 dark:text-slate-600 transition-colors">
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
