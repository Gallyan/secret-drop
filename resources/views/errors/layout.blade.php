@extends('layouts.app')

@section('content')
@php
    $errColor = trim($__env->yieldContent('color', 'violet'));
    $errTo = match($errColor) {
        'red' => 'rose',
        'amber' => 'orange',
        'violet' => 'indigo',
        default => 'slate',
    };
    $accentRgb = match($errColor) {
        'amber' => '217, 119, 6',
        'red' => '239, 68, 68',
        'emerald' => '16, 185, 129',
        default => '139, 92, 246',
    };
@endphp
<div class="flex-1 flex items-center justify-center p-4 transition-colors" style="--accent-rgb: {{ $accentRgb }}">
    <div class="w-full max-w-md">
        <div class="card-accent bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden transition-colors">
            <div class="p-8 lg:p-12 text-center">
                <div class="logo-icon inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br from-{{ $errColor }}-500/70 to-{{ $errTo }}-600 mb-6 transition-colors" aria-hidden="true">
                    @yield('icon')
                </div>

                <p class="text-7xl font-bold text-gray-200 dark:text-slate-700 mb-4 transition-colors" aria-hidden="true">
                    @yield('code')
                </p>

                <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-3 transition-colors">
                    @yield('title')
                </h1>

                <p class="text-gray-600 dark:text-slate-400 mb-8 transition-colors">
                    @yield('message')
                </p>

                <x-btn-primary :href="route('home')" class="gap-2">
                    <x-icon.home class="w-5 h-5" />
                    {{ __('messages.error_back_home') }}
                </x-btn-primary>
            </div>
        </div>
    </div>
</div>
@endsection
