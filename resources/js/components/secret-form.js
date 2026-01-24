export default function secretForm() {
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
        passphraseUsed: false,
        copied: false,

        async handleSubmit() {
            this.error = null;
            this.isSubmitting = true;

            try {
                if (!window.SecretCrypto?.isCryptoAvailable()) {
                    throw new Error('Votre navigateur ne supporte pas le chiffrement sécurisé');
                }

                const passphrase = this.passphrase?.trim() || null;
                const encrypted = await window.SecretCrypto.encryptSecret(this.secret, passphrase);

                const cipherMeta = {
                    alg: 'AES-256-GCM',
                    iv: encrypted.iv,
                    version: encrypted.version
                };

                if (encrypted.salt) {
                    cipherMeta.salt = encrypted.salt;
                    cipherMeta.kdf = 'PBKDF2-SHA256-200k';
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                const response = await fetch('/api/secrets', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        type: 'text',
                        ciphertext: encrypted.ciphertext,
                        cipher_meta: cipherMeta,
                        expiration: this.expiration,
                        usage_unique: this.usageUnique,
                        max_views: this.maxViews || null,
                        creator_email: this.creatorEmail?.trim() || null
                    })
                });

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Erreur lors de la création du secret');
                }

                const data = await response.json();

                const hasPassphrase = !!passphrase;
                const keyFragment = window.SecretCrypto.buildKeyFragment(
                    encrypted.keyMaterial,
                    hasPassphrase,
                    encrypted.version
                );

                this.shareUrl = `${window.location.origin}/s/${data.token}#${keyFragment}`;
                this.passphraseUsed = hasPassphrase;
            } catch (e) {
                console.error('Encryption error:', e);
                this.error = e.message || 'Une erreur est survenue lors du chiffrement';
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
            this.passphraseUsed = false;
            this.error = null;
        }
    };
}
