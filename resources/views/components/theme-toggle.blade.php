<div
    x-data="{
        dark: localStorage.getItem('theme') !== 'light',
        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', this.dark);
        }
    }"
    class="relative"
>
    <button
        @click="toggle()"
        type="button"
        class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm border border-gray-200 dark:border-slate-700/50 hover:border-violet-500/50 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-all shadow-sm cursor-pointer"
        :aria-label="dark ? 'Activer le mode clair' : 'Activer le mode sombre'"
    >
        <template x-if="dark">
            <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
            </svg>
        </template>
        <template x-if="!dark">
            <svg class="w-5 h-5 text-violet-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
            </svg>
        </template>
    </button>
</div>
