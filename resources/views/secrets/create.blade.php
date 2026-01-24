@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-gray-100 to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 flex items-center justify-center md:p-4 transition-colors">
    <div class="w-full max-w-5xl">
        <div
            x-data="secretForm()"
            class="md:bg-white/80 md:dark:bg-slate-800/50 md:backdrop-blur-xl md:border md:border-gray-200 md:dark:border-slate-700/50 md:rounded-2xl md:shadow-2xl overflow-hidden transition-colors"
        >
            <div class="grid lg:grid-cols-2">
                {{-- Left: Branding & Info --}}
                <div class="p-6 md:p-8 lg:p-12 flex flex-col justify-center md:bg-gradient-to-br md:from-violet-600/5 md:to-indigo-600/5 md:dark:from-violet-600/10 md:dark:to-indigo-600/10 md:border-b lg:border-b-0 lg:border-r md:border-gray-200 md:dark:border-slate-700/50 transition-colors">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 shadow-lg shadow-violet-500/25 shrink-0">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight transition-colors">
                            Secret Drop
                        </h1>
                    </div>

                    <p class="text-gray-600 dark:text-slate-400 mb-8 transition-colors">
                        Partagez des informations sensibles en toute sécurité avec un chiffrement de bout en bout.
                    </p>

                    <ul class="space-y-3 text-sm">
                        <li class="flex items-center gap-3 text-gray-700 dark:text-slate-300 transition-colors">
                            <svg class="w-5 h-5 text-emerald-500 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Chiffrement AES-256-GCM dans votre navigateur
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-slate-300 transition-colors">
                            <svg class="w-5 h-5 text-emerald-500 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Le serveur ne voit jamais vos données en clair
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-slate-300 transition-colors">
                            <svg class="w-5 h-5 text-emerald-500 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Auto-destruction après lecture
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-slate-300 transition-colors">
                            <svg class="w-5 h-5 text-emerald-500 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Expiration automatique configurable
                        </li>
                    </ul>
                </div>

                {{-- Right: Form --}}
                <div class="p-6 md:p-8 lg:p-12">
                    {{-- Form --}}
                    <form x-show="!shareUrl" @submit.prevent="handleSubmit" class="space-y-5">
                        {{-- Mode tabs --}}
                        <div class="flex rounded-xl bg-gray-100 dark:bg-slate-900/50 p-1">
                            <button
                                type="button"
                                @click="setMode('text')"
                                :class="mode === 'text' ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'"
                                class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-lg text-sm font-medium transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Texte
                            </button>
                            <button
                                type="button"
                                @click="setMode('file')"
                                :class="mode === 'file' ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200'"
                                class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-lg text-sm font-medium transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Fichier
                            </button>
                        </div>

                        {{-- Text mode: Secret textarea --}}
                        <div x-show="mode === 'text'">
                            <label for="secret" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                Votre secret
                            </label>
                            <textarea
                                id="secret"
                                x-model="secret"
                                placeholder="Entrez votre message confidentiel..."
                                class="w-full h-[118px] px-4 py-3 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition resize-none"
                            ></textarea>
                        </div>

                        {{-- File mode: Drag & drop zone --}}
                        <div x-show="mode === 'file'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                Votre fichier
                            </label>

                            {{-- Drop zone (when no file selected) --}}
                            <div
                                x-show="!file"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleFileDrop($event)"
                                :class="isDragging ? 'border-violet-500 bg-violet-50 dark:bg-violet-500/10' : 'border-gray-300 dark:border-slate-600/50 hover:border-gray-400 dark:hover:border-slate-500'"
                                class="relative flex flex-col items-center justify-center h-[118px] border border-dashed rounded-xl cursor-pointer transition"
                            >
                                <input
                                    type="file"
                                    @change="handleFileSelect($event)"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                >
                                <svg class="w-8 h-8 mb-2 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-sm text-gray-600 dark:text-slate-400">
                                    <span class="font-medium text-violet-600 dark:text-violet-400">Cliquez pour choisir</span> ou glissez un fichier
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-slate-500">
                                    Maximum 100 Mo
                                </p>
                            </div>

                            {{-- File preview (when file selected) --}}
                            <div
                                x-show="file"
                                class="flex items-center gap-4 p-4 h-[118px] bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl"
                            >
                                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-violet-100 dark:bg-violet-500/10 shrink-0">
                                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="file?.name"></p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400" x-text="formatFileSize(file?.size)"></p>
                                </div>
                                <button
                                    type="button"
                                    @click="file = null"
                                    class="p-2 text-gray-400 hover:text-red-500 dark:text-slate-400 dark:hover:text-red-400 transition"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Options grid --}}
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Expiration --}}
                            <div>
                                <label for="expiration" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                    Expire dans
                                </label>
                                <select
                                    id="expiration"
                                    x-model="expiration"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                >
                                    <option value="1h">1 heure</option>
                                    <option value="1d">1 jour</option>
                                    <option value="7d">7 jours</option>
                                    <option value="30d">30 jours</option>
                                </select>
                            </div>

                            {{-- Max views --}}
                            <div>
                                <label for="maxViews" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                    Lectures max
                                </label>
                                <input
                                    id="maxViews"
                                    type="number"
                                    x-model="maxViews"
                                    min="1"
                                    max="100"
                                    placeholder="Illimité"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                >
                            </div>
                        </div>

                        {{-- Usage unique toggle --}}
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" x-model="usageUnique" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-300 dark:bg-slate-700 rounded-full peer-checked:bg-violet-600 transition"></div>
                                <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition peer-checked:translate-x-5"></div>
                            </div>
                            <span class="text-sm text-gray-600 dark:text-slate-300 group-hover:text-gray-900 dark:group-hover:text-white transition">
                                Détruire après lecture
                            </span>
                        </label>

                        {{-- Collapsible options --}}
                        <div>
                            <button
                                type="button"
                                @click="showAdvanced = !showAdvanced"
                                class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition"
                            >
                                <svg
                                    class="w-4 h-4 transition-transform"
                                    :class="{ 'rotate-90': showAdvanced }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Options avancées
                            </button>

                            <div x-show="showAdvanced" x-collapse class="mt-4 space-y-4">
                                {{-- Passphrase --}}
                                <div>
                                    <label for="passphrase" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                        Passphrase
                                    </label>
                                    <div class="relative">
                                        <input
                                            id="passphrase"
                                            :type="showPassphrase ? 'text' : 'password'"
                                            x-model="passphrase"
                                            placeholder="Protection supplémentaire"
                                            class="w-full px-4 py-2.5 pr-12 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                        >
                                        <button
                                            type="button"
                                            @click="showPassphrase = !showPassphrase"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-slate-400 hover:text-gray-700 dark:hover:text-white transition"
                                        >
                                            <svg x-show="!showPassphrase" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg x-show="showPassphrase" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Creator email --}}
                                <div>
                                    <label for="creatorEmail" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2 transition-colors">
                                        Votre email
                                    </label>
                                    <input
                                        id="creatorEmail"
                                        type="email"
                                        x-model="creatorEmail"
                                        placeholder="pour gérer votre secret"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                    >
                                </div>
                            </div>
                        </div>

                        {{-- Error message --}}
                        <div x-show="error" x-cloak class="p-4 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-xl transition-colors">
                            <p class="text-sm text-red-600 dark:text-red-400" x-text="error"></p>
                        </div>

                        {{-- Submit button --}}
                        <button
                            type="submit"
                            :disabled="isSubmitting || (mode === 'text' && !secret.trim()) || (mode === 'file' && !file)"
                            class="w-full py-3 px-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none transition-all"
                        >
                            <span x-show="!isSubmitting">Chiffrer et créer le lien</span>
                            <span x-show="isSubmitting" class="inline-flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="mode === 'file' ? 'Chiffrement et upload...' : 'Chiffrement...'"></span>
                            </span>
                        </button>
                    </form>

                    {{-- Success state --}}
                    <div x-show="shareUrl" x-cloak class="space-y-6">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-500/10 mb-4 transition-colors">
                                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white transition-colors">Secret créé</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400 transition-colors">Partagez ce lien avec votre destinataire</p>
                        </div>

                        <div class="relative">
                            <input
                                type="text"
                                readonly
                                :value="shareUrl"
                                class="w-full px-4 py-3 pr-24 bg-gray-50 dark:bg-slate-900/50 border border-gray-300 dark:border-slate-600/50 rounded-xl text-gray-900 dark:text-white text-sm font-mono transition-colors"
                            >
                            <button
                                type="button"
                                @click="copyToClipboard()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium rounded-lg transition"
                            >
                                <span x-show="!copied">Copier</span>
                                <span x-show="copied">Copié !</span>
                            </button>
                        </div>

                        <div class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl transition-colors">
                            <p class="text-xs text-amber-700 dark:text-amber-400" x-show="!passphraseUsed">
                                <strong>Important :</strong> Ce lien contient la clé de déchiffrement. Ne le partagez qu'avec le destinataire.
                            </p>
                            <p class="text-xs text-amber-700 dark:text-amber-400" x-show="passphraseUsed">
                                <strong>Important :</strong> Le destinataire devra entrer la passphrase pour déchiffrer le secret.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="reset()"
                            class="w-full py-2.5 text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-slate-600/50 hover:border-gray-400 dark:hover:border-slate-500 rounded-xl transition"
                        >
                            Créer un nouveau secret
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
