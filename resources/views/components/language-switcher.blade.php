@php
    $currentLocale = app()->getLocale();
    $urls = locale_switcher_urls();
    $nativeNames = \App\Support\LocaleConfig::NATIVE_NAMES;
@endphp

<div
    x-data="languageSwitcher"
    data-current-locale="{{ $currentLocale }}"
    class="relative"
    @keydown.escape.window="close()"
>
    <button
        @click="toggle()"
        type="button"
        class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm border border-gray-200 dark:border-slate-700/50 hover:border-violet-500/50 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-all shadow-sm cursor-pointer text-sm font-semibold text-gray-700 dark:text-slate-300"
        aria-label="{{ __('messages.a11y_language_selector') }}"
        :aria-expanded="open"
    >
        {{ strtoupper($currentLocale) }}
    </button>

    <nav
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="close()"
        class="absolute end-0 top-12 w-48 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700/50 shadow-lg backdrop-blur-sm overflow-hidden z-50"
        aria-label="{{ __('messages.a11y_language_selector') }}"
        style="display: none;"
    >
        <ul class="py-1 max-h-80 overflow-y-auto">
            @foreach($urls as $locale => $url)
                <li>
                    @if($locale === $currentLocale)
                        <span
                            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/20 border-s-2 border-violet-500"
                            lang="{{ $locale }}"
                            aria-current="true"
                            @if($locale === 'ar') dir="rtl" @endif
                        >
                            <span class="uppercase w-7 text-xs font-bold" aria-hidden="true">{{ $locale }}</span>
                            <span>{{ $nativeNames[$locale] }}</span>
                        </span>
                    @else
                        <a
                            href="{{ $url }}"
                            hreflang="{{ $locale }}"
                            lang="{{ $locale }}"
                            class="flex items-center gap-2 w-full px-3 py-2 text-sm text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors border-s-2 border-transparent"
                            @if($locale === 'ar') dir="rtl" @endif
                        >
                            <span class="uppercase w-7 text-xs font-bold" aria-hidden="true">{{ $locale }}</span>
                            <span>{{ $nativeNames[$locale] }}</span>
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
</div>
