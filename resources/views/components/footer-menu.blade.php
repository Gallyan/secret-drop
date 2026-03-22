<div x-data="footerMenu" class="relative">
    {{-- Bento button --}}
    <button
        @click="toggle()"
        type="button"
        class="flex items-center justify-center w-10 h-10 rounded-xl cursor-pointer select-none
               bg-linear-to-b from-violet-500 to-indigo-600 dark:from-violet-600 dark:to-indigo-800
               shadow-[0_4px_14px_rgba(0,0,0,0.18)] transition-shadow duration-300
               hover:shadow-[0_6px_20px_rgba(0,0,0,0.25)]"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="{{ __('messages.a11y_footer_nav') }}"
    >
        {{-- 2x2 dot grid --}}
        <div class="grid grid-cols-2 gap-1.5" aria-hidden="true">
            <span class="w-1.5 h-1.5 rounded-full bg-white/90"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-white/90"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-white/90"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-white/90"></span>
        </div>
    </button>

    {{-- Menu panel (opens upward) --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150 motion-reduce:duration-0"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100 motion-reduce:duration-0"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        @keydown.escape.window="close()"
        class="absolute bottom-full mb-3 start-0 z-50 w-56
               bg-white dark:bg-slate-800 rounded-xl shadow-2xl
               border border-gray-200 dark:border-slate-700/50
               overflow-hidden"
        role="menu"
        aria-label="{{ __('messages.a11y_footer_nav') }}"
    >
        <nav class="py-1.5" x-trap="open">
            <a href="{{ route('home') }}" role="menuitem"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-violet-50 dark:hover:bg-violet-500/10 transition-colors">
                <svg class="w-4 h-4 text-violet-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                {{ __('messages.btn_create_new') }}
            </a>
            <a href="{{ localized_route('how-it-works') }}" role="menuitem"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-violet-50 dark:hover:bg-violet-500/10 transition-colors">
                <svg class="w-4 h-4 text-violet-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ __('messages.footer_how_it_works') }}
            </a>
            <a href="{{ localized_route('use-cases') }}" role="menuitem"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-violet-50 dark:hover:bg-violet-500/10 transition-colors">
                <svg class="w-4 h-4 text-violet-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                {{ __('messages.footer_use_cases') }}
            </a>
            <a href="{{ route('admin.index') }}" role="menuitem"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-violet-50 dark:hover:bg-violet-500/10 transition-colors">
                <svg class="w-4 h-4 text-violet-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                {{ __('messages.footer_manage') }}
            </a>
            <a href="{{ localized_route('legal') }}" role="menuitem"
               class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 dark:text-slate-300 hover:bg-violet-50 dark:hover:bg-violet-500/10 transition-colors">
                <svg class="w-4 h-4 text-violet-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __('messages.footer_legal') }}
            </a>
        </nav>
    </div>
</div>
