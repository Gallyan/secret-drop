<x-modal-overlay show="showRevokeModal" close="closeRevokeModal()" role="dialog" aria-modal="true" aria-labelledby="revoke-modal-title" aria-describedby="revoke-modal-description">
    <div class="flex items-center justify-center min-h-full p-4" @click.self="closeRevokeModal()">
        <div
            x-show="showRevokeModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-trap.noscroll="showRevokeModal"
            @click.stop
            class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6 border border-gray-200 dark:border-slate-700/50"
        >
            <div class="flex items-center gap-4 mb-4">
                <div class="logo-icon flex items-center justify-center w-12 h-12 rounded-2xl bg-linear-to-br from-red-500/0 to-rose-600 shrink-0" style="--accent-rgb: 239, 68, 68">
                    <x-icon.warning class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h3 id="revoke-modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('messages.admin_revoke') }}</h3>
                    <p id="revoke-modal-description" class="text-sm text-gray-500 dark:text-slate-400">{{ __('messages.admin_revoke_confirm') }}</p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button
                    @click="closeRevokeModal()"
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 rounded-lg transition cursor-pointer"
                >
                    {{ __('messages.btn_cancel') }}
                </button>
                <button
                    @click="confirmRevoke()"
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-500 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800 cursor-pointer"
                >
                    {{ __('messages.admin_revoke') }}
                </button>
            </div>
        </div>
    </div>
</x-modal-overlay>
