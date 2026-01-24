@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <div class="max-w-xl mx-auto px-4 py-16 sm:py-24">
        {{-- Header --}}
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 mb-6 shadow-lg shadow-violet-500/25">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h1 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">
                Secret Drop
            </h1>
            <p class="mt-3 text-slate-400 max-w-sm mx-auto">
                Partagez des informations sensibles en toute sécurité. Chiffrement de bout en bout.
            </p>
        </div>

        {{-- Form card --}}
        <div
            x-data="secretForm()"
            x-show="!shareUrl"
            class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 sm:p-8 shadow-2xl"
        >
            <form @submit.prevent="handleSubmit" class="space-y-5">
                {{-- Secret textarea --}}
                <div>
                    <label for="secret" class="block text-sm font-medium text-slate-300 mb-2">
                        Votre secret
                    </label>
                    <textarea
                        id="secret"
                        x-model="secret"
                        rows="5"
                        required
                        placeholder="Entrez votre message confidentiel..."
                        class="w-full px-4 py-3 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition resize-none"
                    ></textarea>
                </div>

                {{-- Options grid --}}
                <div class="grid grid-cols-2 gap-4">
                    {{-- Expiration --}}
                    <div>
                        <label for="expiration" class="block text-sm font-medium text-slate-300 mb-2">
                            Expire dans
                        </label>
                        <select
                            id="expiration"
                            x-model="expiration"
                            class="w-full px-4 py-2.5 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                        >
                            <option value="1h">1 heure</option>
                            <option value="1d">1 jour</option>
                            <option value="7d" selected>7 jours</option>
                            <option value="30d">30 jours</option>
                        </select>
                    </div>

                    {{-- Max views --}}
                    <div>
                        <label for="maxViews" class="block text-sm font-medium text-slate-300 mb-2">
                            Lectures max
                        </label>
                        <input
                            id="maxViews"
                            type="number"
                            x-model="maxViews"
                            min="1"
                            max="100"
                            placeholder="Illimité"
                            class="w-full px-4 py-2.5 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                        >
                    </div>
                </div>

                {{-- Usage unique toggle --}}
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <input type="checkbox" x-model="usageUnique" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-700 rounded-full peer-checked:bg-violet-600 transition"></div>
                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow transition peer-checked:translate-x-5"></div>
                    </div>
                    <span class="text-sm text-slate-300 group-hover:text-white transition">
                        Détruire après lecture
                    </span>
                </label>

                {{-- Collapsible options --}}
                <div class="pt-2">
                    <button
                        type="button"
                        @click="showAdvanced = !showAdvanced"
                        class="flex items-center gap-2 text-sm text-slate-400 hover:text-white transition"
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
                            <label for="passphrase" class="block text-sm font-medium text-slate-300 mb-2">
                                Passphrase
                            </label>
                            <div class="relative">
                                <input
                                    id="passphrase"
                                    :type="showPassphrase ? 'text' : 'password'"
                                    x-model="passphrase"
                                    placeholder="Protection supplémentaire"
                                    class="w-full px-4 py-2.5 pr-12 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                                >
                                <button
                                    type="button"
                                    @click="showPassphrase = !showPassphrase"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition"
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
                            <p class="mt-1.5 text-xs text-slate-500">
                                Le destinataire devra saisir cette passphrase
                            </p>
                        </div>

                        {{-- Creator email --}}
                        <div>
                            <label for="creatorEmail" class="block text-sm font-medium text-slate-300 mb-2">
                                Votre email
                            </label>
                            <input
                                id="creatorEmail"
                                type="email"
                                x-model="creatorEmail"
                                placeholder="pour gérer votre secret"
                                class="w-full px-4 py-2.5 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition"
                            >
                            <p class="mt-1.5 text-xs text-slate-500">
                                Recevez un lien pour suivre et révoquer votre secret
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Error message --}}
                <div x-show="error" x-cloak class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                    <p class="text-sm text-red-400" x-text="error"></p>
                </div>

                {{-- Submit button --}}
                <button
                    type="submit"
                    :disabled="isSubmitting || !secret.trim()"
                    class="w-full py-3 px-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg shadow-violet-500/25 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none transition-all"
                >
                    <span x-show="!isSubmitting">Chiffrer et créer le lien</span>
                    <span x-show="isSubmitting" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Chiffrement...
                    </span>
                </button>
            </form>
        </div>

        {{-- Success card --}}
        <div
            x-data="secretForm()"
            x-show="shareUrl"
            x-cloak
            class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 sm:p-8 shadow-2xl"
        >
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-500/10 mb-4">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-white">Secret créé</h2>
                <p class="mt-1 text-sm text-slate-400">Partagez ce lien avec votre destinataire</p>
            </div>

            <div class="relative">
                <input
                    type="text"
                    readonly
                    :value="shareUrl"
                    class="w-full px-4 py-3 pr-24 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white text-sm font-mono"
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

            <div class="mt-4 p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl">
                <p class="text-xs text-amber-400">
                    <strong>Important :</strong> Ce lien contient la clé de déchiffrement. Ne le partagez qu'avec le destinataire.
                </p>
            </div>

            <button
                type="button"
                @click="reset()"
                class="w-full mt-6 py-2.5 text-sm text-slate-400 hover:text-white border border-slate-600/50 hover:border-slate-500 rounded-xl transition"
            >
                Créer un nouveau secret
            </button>
        </div>

        {{-- Footer --}}
        <p class="mt-8 text-center text-xs text-slate-500">
            Chiffrement AES-256-GCM dans votre navigateur. Le serveur ne voit jamais vos données en clair.
        </p>
    </div>
</div>

<script @nonce>
function secretForm() {
    return {
        secret: '',
        expiration: '7d',
        usageUnique: true,
        maxViews: null,
        passphrase: '',
        showPassphrase: false,
        showAdvanced: false,
        creatorEmail: '',
        isSubmitting: false,
        error: null,
        shareUrl: null,
        adminUrl: null,
        copied: false,

        async handleSubmit() {
            this.error = null;
            this.isSubmitting = true;

            try {
                // TODO: Implement encryption and API call
                console.log('Form submitted', {
                    secret: this.secret,
                    expiration: this.expiration,
                    usageUnique: this.usageUnique,
                    maxViews: this.maxViews,
                    passphrase: this.passphrase,
                    creatorEmail: this.creatorEmail
                });

                this.error = 'Fonctionnalité en cours de développement';
            } catch (e) {
                this.error = e.message || 'Une erreur est survenue';
            } finally {
                this.isSubmitting = false;
            }
        },

        async copyToClipboard() {
            try {
                await navigator.clipboard.writeText(this.shareUrl);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch (e) {
                this.error = 'Impossible de copier dans le presse-papier';
            }
        },

        reset() {
            this.secret = '';
            this.expiration = '7d';
            this.usageUnique = true;
            this.maxViews = null;
            this.passphrase = '';
            this.creatorEmail = '';
            this.showAdvanced = false;
            this.shareUrl = null;
            this.adminUrl = null;
            this.error = null;
        }
    };
}
</script>
@endsection
