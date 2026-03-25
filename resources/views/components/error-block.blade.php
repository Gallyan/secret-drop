@props(['color' => 'red', 'title', 'iconName' => 'icon.exclamation-circle'])

@php
    $gradTo = match($color) {
        'red' => 'rose',
        'amber' => 'orange',
        'violet' => 'indigo',
        default => 'slate',
    };
@endphp

<div {{ $attributes->merge(['class' => 'text-center', 'role' => 'alert']) }}>
    <div class="logo-icon inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-linear-to-br from-{{ $color }}-500/70 to-{{ $gradTo }}-600 mb-6 transition-colors" aria-hidden="true">
        <x-dynamic-component :component="$iconName" class="w-7 h-7 text-white" />
    </div>
    <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2 transition-colors">
        {{ $title }}
    </h1>
    {{ $slot }}
</div>
