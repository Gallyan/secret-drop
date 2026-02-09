@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.view_secret_title'))

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-2xl">
        <div
            x-data="secretViewer" data-token="{{ $token }}"
            class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden transition-colors"
        >
            <div class="p-8 lg:p-12">
                {{-- Loading state --}}
                <div x-show="isLoading" class="text-center py-8" role="status">
                    <svg class="animate-spin h-10 w-10 mx-auto text-violet-500" aria-hidden="true" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-4 text-gray-600 dark:text-slate-400 transition-colors">{{ __('messages.loading_secret') }}</p>
                </div>

                {{-- Error announcer for screen readers --}}
                <div aria-live="assertive" aria-atomic="true" class="sr-only" x-text="errorMessage()"></div>

                {{-- Not found error --}}
                <div x-show="isLoadErrorType('not_found')" x-cloak class="text-center" role="alert">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-red-100 dark:bg-red-500/10 mb-6 transition-colors" aria-hidden="true">
                        <svg class="w-7 h-7 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2 transition-colors">
                        {{ __('messages.error_not_found') }}
                    </h1>
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors" x-text="loadErrorMessage()"></p>
                    <a
                        href="{{ route('home') }}"
                        class="inline-block py-2.5 px-6 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 transition-all cursor-pointer"
                    >
                        {{ __('messages.btn_create_new') }}
                    </a>
                </div>

                {{-- Unavailable error --}}
                <div x-show="isLoadErrorType('unavailable')" x-cloak class="text-center" role="alert">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-500/10 mb-6 transition-colors" aria-hidden="true">
                        <svg class="w-7 h-7 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2 transition-colors">
                        {{ __('messages.error_unavailable') }}
                    </h1>
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors" x-text="loadErrorMessage()"></p>
                    <a
                        href="{{ route('home') }}"
                        class="inline-block py-2.5 px-6 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 transition-all cursor-pointer"
                    >
                        {{ __('messages.btn_create_new') }}
                    </a>
                </div>

                {{-- Generic error --}}
                <div x-show="isLoadErrorType('error')" x-cloak class="text-center" role="alert">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-red-100 dark:bg-red-500/10 mb-6 transition-colors" aria-hidden="true">
                        <svg class="w-7 h-7 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2 transition-colors">
                        {{ __('messages.error_generic') }}
                    </h1>
                    <p class="text-gray-600 dark:text-slate-400 mb-6 transition-colors" x-text="loadErrorMessage()"></p>
                    <button
                        type="button"
                        @click="reload()"
                        class="inline-block py-2.5 px-6 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 transition-all cursor-pointer"
                    >
                        {{ __('messages.btn_retry') }}
                    </button>
                </div>

                {{-- Secret content --}}
                <div x-show="!isLoading && !loadError" x-cloak>
                    {{-- Header --}}
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 mb-6 shadow-lg shadow-violet-500/25" aria-hidden="true">
                            <template x-if="type === 'text'">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </template>
                            <template x-if="type === 'file'">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </template>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">
                            <span x-text="secretTypeTitle()"></span>
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-slate-400 transition-colors" x-show="!decrypted && !error">
                            <span x-text="encryptedDescription()"></span>
                        </p>
                        <div x-show="type === 'file' && !decrypted && !error" class="mt-3 text-sm text-gray-500 dark:text-slate-500">
                            <span class="text-gray-600 dark:text-slate-400">{{ __('messages.file_encrypted_info') }}</span>
                        </div>
                    </div>

                    {{-- Confirmation step for last read --}}
                    <div x-show="awaitingConfirmation && !decrypted" x-cloak class="space-y-6">
                        <div class="p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                            <div class="flex gap-3">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-amber-800 dark:text-amber-300">
                                        {{ __('messages.last_read_warning_title') }}
                                    </p>
                                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                                        {{ __('messages.last_read_warning_text') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="confirmAndDecrypt()"
                            class="w-full py-3 px-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 transition-all cursor-pointer"
                        >
                            {{ __('messages.btn_reveal_secret') }}
                        </button>
                    </div>

                    {{-- Manual key input (split mode) --}}
                    <div x-show="needsManualKey && !decrypted && !error && !awaitingConfirmation" x-cloak class="space-y-4">
                        <p class="text-sm text-gray-700 dark:text-slate-300 text-center transition-colors">
                            {{ __('messages.enter_key_manually') }}
                        </p>
                        {{-- Last read warning in manual key form --}}
                        <div x-show="willBeDestroyed" class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                <strong>{{ __('messages.label_important') }}</strong> {{ __('messages.last_read_warning_short') }}
                            </p>
                        </div>
                        <form @submit.prevent="submitManualKey()" class="space-y-4">
                            <div>
                                <label for="manualKey" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                    {{ __('messages.share_key_label') }}
                                </label>
                                <input
                                    id="manualKey"
                                    type="text"
                                    x-model="manualKey"
                                    required
                                    autofocus
                                    autocomplete="off"
                                    placeholder="{{ __('messages.key_placeholder') }}"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                >
                            </div>
                            <button
                                type="submit"
                                :disabled="!manualKey.trim()"
                                class="w-full py-3 px-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all cursor-pointer"
                            >
                                {{ __('messages.btn_unlock') }}
                            </button>
                        </form>
                    </div>

                    {{-- Passphrase input --}}
                    <div x-show="needsPassphrase && !needsManualKey && !decrypted && !error && !awaitingConfirmation" x-cloak class="space-y-4">
                        <p class="text-sm text-gray-700 dark:text-slate-300 text-center transition-colors">
                            {{ __('messages.passphrase_protected') }}
                        </p>
                        {{-- Last read warning in passphrase form --}}
                        <div x-show="willBeDestroyed" class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                <strong>{{ __('messages.label_important') }}</strong> {{ __('messages.last_read_warning_short') }}
                            </p>
                        </div>
                        <form @submit.prevent="decrypt()" class="space-y-4">
                            <div>
                                <label for="passphrase" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                    {{ __('messages.passphrase') }}
                                </label>
                                <input
                                    id="passphrase"
                                    type="password"
                                    x-model="passphrase"
                                    required
                                    autofocus
                                    placeholder="{{ __('messages.passphrase_input_placeholder') }}"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                >
                            </div>
                            <button
                                type="submit"
                                :disabled="isDecrypting || !passphrase.trim()"
                                class="w-full py-3 px-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all cursor-pointer"
                            >
                                <span x-show="!isDecrypting">{{ __('messages.btn_decrypt') }}</span>
                                <span x-show="isDecrypting" role="status" class="inline-flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ __('messages.btn_decrypting') }}
                                </span>
                            </button>
                        </form>
                    </div>

                    {{-- Loading state for decryption --}}
                    <div x-show="isDecrypting && !needsPassphrase" x-cloak class="text-center py-8" role="status">
                        <svg class="animate-spin h-8 w-8 mx-auto text-violet-500" aria-hidden="true" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-4 text-gray-600 dark:text-slate-400 transition-colors">
                            <span x-text="decryptingText()"></span>
                        </p>
                    </div>

                    {{-- Decrypted text content --}}
                    <div x-show="decrypted && type === 'text'" x-cloak class="space-y-6">
                        <div class="relative">
                            <pre
                                x-text="plaintext"
                                class="w-full p-4 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white text-sm font-mono whitespace-pre-wrap break-words max-h-96 overflow-auto transition-colors"
                            ></pre>
                            <button
                                type="button"
                                @click="copyToClipboard()"
                                :aria-label="copied ? '{{ __('messages.btn_copied') }}' : '{{ __('messages.btn_copy') }}'"
                                class="absolute top-3 end-3 px-3 py-1.5 bg-gray-200 dark:bg-slate-700/50 hover:bg-gray-300 dark:hover:bg-slate-600/50 text-gray-700 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white text-sm font-medium rounded-lg transition cursor-pointer"
                            >
                                <span x-show="!copied">{{ __('messages.btn_copy') }}</span>
                                <span x-show="copied">{{ __('messages.btn_copied') }}</span>
                            </button>
                        </div>

                        <div x-show="willBeDestroyed" class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                <strong>{{ __('messages.label_note') }}</strong> {{ __('messages.note_destroyed_text') }}
                            </p>
                        </div>
                    </div>

                    {{-- Decrypted file content --}}
                    <div x-show="decrypted && type === 'file'" x-cloak class="space-y-6">
                        <div class="p-6 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-center transition-colors">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-500/10 mb-4" aria-hidden="true">
                                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-gray-900 dark:text-white font-medium mb-1">{{ __('messages.file_decrypted') }}</p>
                            <p class="text-sm text-gray-500 dark:text-slate-400" x-text="filename"></p>
                        </div>

                        <div x-show="willBeDestroyed" class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                <strong>{{ __('messages.label_note') }}</strong> {{ __('messages.note_destroyed_file') }}
                            </p>
                        </div>
                    </div>

                    {{-- Decryption error --}}
                    <div x-show="error" x-cloak class="space-y-4">
                        <div role="alert" class="p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl transition-colors">
                            <p class="text-sm text-red-600 dark:text-red-400" x-text="error"></p>
                        </div>
                        <button
                            x-show="needsPassphrase || needsManualKey"
                            type="button"
                            @click="clearRetryError()"
                            class="w-full py-2.5 text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-slate-600/50 hover:border-gray-400 dark:hover:border-slate-500 rounded-xl transition cursor-pointer"
                        >
                            {{ __('messages.btn_retry') }}
                        </button>
                    </div>

                    {{-- Create new --}}
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-slate-700/50 text-center transition-colors">
                        <a
                            href="{{ route('home') }}"
                            class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition"
                        >
                            {{ __('messages.btn_create_new') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
