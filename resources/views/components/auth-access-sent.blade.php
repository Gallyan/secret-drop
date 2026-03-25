@props(['description', 'color' => 'violet'])

<div class="flex-1 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-md">
        <x-card class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-500/10 mb-4" aria-hidden="true">
                <x-icon.mail class="w-7 h-7 text-emerald-600 dark:text-emerald-300" />
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.admin_link_sent_title') }}</h1>
            <p class="text-gray-600 dark:text-slate-400 mb-6">{{ $description }}</p>

            <div class="p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl" role="alert">
                <p class="text-sm text-amber-700 dark:text-amber-300">{{ __('messages.admin_link_sent_warning') }}</p>
            </div>

            <div class="mt-6">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-{{ $color }}-600 dark:hover:text-{{ $color }}-400 transition">
                    {{ __('messages.admin_back_home') }}
                </a>
            </div>
        </x-card>
    </div>
</div>
