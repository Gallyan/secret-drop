@props(['formAction', 'token', 'color' => 'violet', 'iconName' => 'icon.shield-check'])

@php
    $accentRgb = match($color) {
        'amber' => '217, 119, 6',
        'emerald' => '16, 185, 129',
        default => '139, 92, 246',
    };
@endphp

<div class="flex-1 flex items-center justify-center p-4 transition-colors" style="--accent-rgb: {{ $accentRgb }}">
    <div class="w-full max-w-md">
        <x-card class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-{{ $color }}-100 dark:bg-{{ $color }}-500/10 mb-4" aria-hidden="true">
                <x-dynamic-component :component="$iconName" class="w-7 h-7 text-{{ $color }}-600 dark:text-{{ $color }}-300" />
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.verify_confirm_title') }}</h1>
            <p class="text-gray-600 dark:text-slate-400 mb-6">{{ __('messages.verify_confirm_description') }}</p>

            <form action="{{ $formAction }}" method="POST">
                @csrf
                <x-btn-primary type="submit" :color="$color" class="w-full">
                    {{ __('messages.verify_confirm_button') }}
                </x-btn-primary>
            </form>

            <div class="mt-6">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-{{ $color }}-600 dark:hover:text-{{ $color }}-400 transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </x-card>
    </div>
</div>
