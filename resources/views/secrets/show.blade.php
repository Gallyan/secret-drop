@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 flex items-center justify-center p-4 transition-colors">
    <div class="w-full max-w-2xl">
        <div
            x-data="secretViewer(@js($ciphertext), @js($cipherMeta), @js($willBeDestroyed))"
            class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-xl border border-gray-200 dark:border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden transition-colors"
        >
            <div class="p-8 lg:p-12">
                {{-- Header --}}
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 mb-6 shadow-lg shadow-violet-500/25">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">
                        Message secret
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-slate-400 transition-colors" x-show="!decrypted && !error">
                        Ce message a été chiffré de bout en bout
                    </p>
                </div>

                {{-- Passphrase input --}}
                <div x-show="needsPassphrase && !decrypted && !error" x-cloak class="space-y-4">
                    <p class="text-sm text-gray-700 dark:text-slate-300 text-center transition-colors">
                        Ce secret est protégé par une passphrase.
                    </p>
                    <form @submit.prevent="decrypt()" class="space-y-4">
                        <div>
                            <label for="passphrase" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                Passphrase
                            </label>
                            <input
                                id="passphrase"
                                type="password"
                                x-model="passphrase"
                                required
                                autofocus
                                placeholder="Entrez la passphrase"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                            >
                        </div>
                        <button
                            type="submit"
                            :disabled="isDecrypting || !passphrase.trim()"
                            class="w-full py-3 px-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                        >
                            <span x-show="!isDecrypting">Déchiffrer</span>
                            <span x-show="isDecrypting" class="inline-flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Déchiffrement...
                            </span>
                        </button>
                    </form>
                </div>

                {{-- Loading state --}}
                <div x-show="isDecrypting && !needsPassphrase" x-cloak class="text-center py-8">
                    <svg class="animate-spin h-8 w-8 mx-auto text-violet-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-4 text-gray-600 dark:text-slate-400 transition-colors">Déchiffrement en cours...</p>
                </div>

                {{-- Decrypted content --}}
                <div x-show="decrypted" x-cloak class="space-y-6">
                    <div class="relative">
                        <pre
                            x-text="plaintext"
                            class="w-full p-4 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white text-sm font-mono whitespace-pre-wrap break-words max-h-96 overflow-auto transition-colors"
                        ></pre>
                        <button
                            type="button"
                            @click="copyToClipboard()"
                            class="absolute top-3 right-3 px-3 py-1.5 bg-gray-200 dark:bg-slate-700/50 hover:bg-gray-300 dark:hover:bg-slate-600/50 text-gray-700 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white text-sm font-medium rounded-lg transition"
                        >
                            <span x-show="!copied">Copier</span>
                            <span x-show="copied">Copié !</span>
                        </button>
                    </div>

                    <div x-show="willBeDestroyed" class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                        <p class="text-xs text-amber-700 dark:text-amber-400">
                            <strong>Note :</strong> Ce secret a été configuré pour être détruit après lecture. Il n'est plus accessible.
                        </p>
                    </div>
                </div>

                {{-- Error --}}
                <div x-show="error" x-cloak class="space-y-4">
                    <div class="p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl transition-colors">
                        <p class="text-sm text-red-600 dark:text-red-400" x-text="error"></p>
                    </div>
                    <button
                        x-show="needsPassphrase"
                        type="button"
                        @click="error = null"
                        class="w-full py-2.5 text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-slate-600/50 hover:border-gray-400 dark:hover:border-slate-500 rounded-xl transition"
                    >
                        Réessayer
                    </button>
                </div>

                {{-- Create new --}}
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-slate-700/50 text-center transition-colors">
                    <a
                        href="{{ route('secrets.create') }}"
                        class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition"
                    >
                        Créer un nouveau secret
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
