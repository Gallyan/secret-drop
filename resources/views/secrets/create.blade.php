@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ config('app.name') }}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Partagez un secret en toute sécurité. Le chiffrement se fait dans votre navigateur.
            </p>
        </div>

        <div
            x-data="secretForm()"
            class="bg-white dark:bg-gray-800 shadow rounded-lg p-6"
        >
            <form @submit.prevent="handleSubmit" class="space-y-6">
                {{-- Secret textarea --}}
                <div>
                    <label for="secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Votre secret
                    </label>
                    <textarea
                        id="secret"
                        x-model="secret"
                        rows="6"
                        required
                        placeholder="Entrez votre message secret ici..."
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Ce contenu sera chiffré avant d'être envoyé au serveur.
                    </p>
                </div>

                {{-- Expiration --}}
                <div>
                    <label for="expiration" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Expiration
                    </label>
                    <select
                        id="expiration"
                        x-model="expiration"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        <option value="1h">1 heure</option>
                        <option value="1d">1 jour</option>
                        <option value="7d" selected>7 jours</option>
                        <option value="30d">30 jours</option>
                    </select>
                </div>

                {{-- Options row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Usage unique --}}
                    <div class="flex items-center">
                        <input
                            id="usageUnique"
                            type="checkbox"
                            x-model="usageUnique"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <label for="usageUnique" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                            Usage unique (détruit après lecture)
                        </label>
                    </div>

                    {{-- Max views --}}
                    <div>
                        <label for="maxViews" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Nombre max de lectures
                        </label>
                        <input
                            id="maxViews"
                            type="number"
                            x-model="maxViews"
                            min="1"
                            max="100"
                            placeholder="Illimité"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                    </div>
                </div>

                {{-- Passphrase --}}
                <div>
                    <div class="flex items-center justify-between">
                        <label for="passphrase" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Passphrase (optionnelle)
                        </label>
                        <button
                            type="button"
                            @click="showPassphrase = !showPassphrase"
                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            <span x-show="!showPassphrase">Afficher</span>
                            <span x-show="showPassphrase">Masquer</span>
                        </button>
                    </div>
                    <input
                        id="passphrase"
                        :type="showPassphrase ? 'text' : 'password'"
                        x-model="passphrase"
                        placeholder="Protection supplémentaire"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Le destinataire devra entrer cette passphrase pour déchiffrer le secret.
                    </p>
                </div>

                {{-- Creator email --}}
                <div>
                    <label for="creatorEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Votre email (optionnel)
                    </label>
                    <input
                        id="creatorEmail"
                        type="email"
                        x-model="creatorEmail"
                        placeholder="pour gérer votre secret"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Recevez un lien d'administration pour suivre et révoquer votre secret.
                    </p>
                </div>

                {{-- Error message --}}
                <div x-show="error" x-cloak class="rounded-md bg-red-50 dark:bg-red-900/50 p-4">
                    <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
                </div>

                {{-- Submit button --}}
                <div>
                    <button
                        type="submit"
                        :disabled="isSubmitting || !secret"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!isSubmitting">Chiffrer et générer le lien</span>
                        <span x-show="isSubmitting" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Chiffrement en cours...
                        </span>
                    </button>
                </div>
            </form>

            {{-- Result section --}}
            <div x-show="shareUrl" x-cloak class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Votre lien de partage
                </h3>

                <div class="flex gap-2">
                    <input
                        type="text"
                        readonly
                        :value="shareUrl"
                        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white bg-gray-50 text-sm"
                    >
                    <button
                        type="button"
                        @click="copyToClipboard()"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <span x-show="!copied">Copier</span>
                        <span x-show="copied">Copié !</span>
                    </button>
                </div>

                <p class="mt-4 text-sm text-amber-600 dark:text-amber-400">
                    Attention : ce lien contient la clé de déchiffrement. Ne le partagez qu'avec le destinataire.
                </p>

                <div x-show="adminUrl" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Un email avec le lien d'administration a été envoyé à <span x-text="creatorEmail" class="font-medium"></span>.
                    </p>
                </div>

                <button
                    type="button"
                    @click="reset()"
                    class="mt-4 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    Créer un nouveau secret
                </button>
            </div>
        </div>

        <p class="mt-4 text-center text-xs text-gray-500 dark:text-gray-400">
            Le serveur ne voit jamais votre secret en clair. Tout le chiffrement se fait dans votre navigateur.
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
                // For now, just show a placeholder
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
            this.shareUrl = null;
            this.adminUrl = null;
            this.error = null;
        }
    };
}
</script>
@endsection
