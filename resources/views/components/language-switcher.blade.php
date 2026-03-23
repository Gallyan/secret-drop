@php
    $currentLocale = app()->getLocale();
    $urls = locale_switcher_urls();
    $nativeNames = \App\Support\LocaleConfig::NATIVE_NAMES;
    $flags = \App\Support\LocaleConfig::FLAGS;
@endphp

<div
    x-data="languageSwitcher"
    @keydown.window="handleGlobalKeydown($event)"
>
    {{-- Globe button --}}
    <button
        @click="togglePalette()"
        type="button"
        class="relative flex items-center gap-2 h-10 sm:h-8 pl-1.5 pr-2.5 sm:pl-1 sm:pr-2 rounded-xl cursor-pointer select-none
               bg-linear-to-b from-violet-500 to-indigo-600 dark:from-violet-600 dark:to-indigo-800
               shadow-[0_4px_14px_rgba(0,0,0,0.18)] transition-shadow duration-300
               hover:shadow-[0_6px_20px_rgba(0,0,0,0.25)]"
        aria-label="{{ __('messages.a11y_language_selector') }}"
        :aria-expanded="isOpen"
        aria-haspopup="dialog"
    >
        <div class="relative" aria-hidden="true">
            {{-- Globe --}}
            <div class="w-7 h-7 sm:w-5 sm:h-5 rounded-full bg-[radial-gradient(circle_at_30%_30%,#4fc3f7,#0288d1_50%,#01579b)]
                        shadow-[inset_-3px_-3px_6px_rgba(0,0,0,0.3),inset_2px_2px_4px_rgba(255,255,255,0.2)]
                        relative overflow-hidden">
                <div class="absolute top-1 left-1.5 w-2 h-1.5 rounded-[40%] bg-emerald-400/70 -rotate-15"></div>
                <div class="absolute top-3 left-0.75 w-1.5 h-2 rounded-[30%] bg-emerald-400/60 rotate-10"></div>
                <div class="absolute top-2 left-3.5 w-1.75 h-2.5 rounded-[35%] bg-emerald-400/60 -rotate-5"></div>
                <div class="absolute inset-0 rounded-full border border-white/10"></div>
                <div class="absolute top-0 bottom-0 left-1/2 w-px bg-white/10"></div>
                <div class="absolute left-0 right-0 top-1/2 h-px bg-white/10"></div>
            </div>
            {{-- Current locale badge --}}
            <span class="absolute -bottom-0.5 -right-0.5 bg-white dark:bg-slate-800 text-[8px] font-bold text-slate-700 dark:text-slate-200 rounded px-0.5 leading-tight shadow-sm border border-slate-200 dark:border-slate-600">
                {{ strtoupper($currentLocale) }}
            </span>
        </div>
    </button>

    {{-- Overlay (teleported to body to escape footer stacking context) --}}
    <template x-teleport="body">
        <div
            x-show="isOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-150 motion-reduce:duration-0"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100 motion-reduce:duration-0"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closePalette()"
            class="fixed inset-0 z-100"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('messages.a11y_language_selector') }}"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50"></div>

            {{-- Panel --}}
            <div class="relative z-10 mx-auto max-w-sm mt-[20vh] px-4">
                <div
                    @click.stop
                    @keydown="handleKeydown($event)"
                    class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl rounded-xl shadow-2xl overflow-hidden border border-gray-200 dark:border-slate-700/50"
                >
                    <div x-ref="results" id="lang-listbox" role="listbox" aria-label="{{ __('messages.a11y_language_selector') }}" class="flex flex-col">
                        @foreach($urls as $locale => $url)
                            <a
                                href="{{ $url }}"
                                hreflang="{{ $locale }}"
                                lang="{{ $locale }}"
                                id="lang-option-{{ $locale }}"
                                role="option"
                                data-locale="{{ $locale }}"
                                @mouseenter="highlightItem($el)"
                                class="flex items-center gap-2.5 w-full px-4 py-2 text-sm text-slate-700 dark:text-slate-300 transition-colors focus:outline-none"
                                @if($locale === $currentLocale) aria-current="true" @endif
                                @if($locale === 'ar') dir="rtl" @endif
                            >
                                <span aria-hidden="true">{{ $flags[$locale] ?? '' }}</span>
                                <span class="flex-1">{{ $nativeNames[$locale] }}</span>
                                @if($locale === $currentLocale)
                                    <span class="text-violet-500" aria-hidden="true">✓</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
