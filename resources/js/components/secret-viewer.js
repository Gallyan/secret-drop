export default function secretViewer(ciphertext, cipherMeta, willBeDestroyed) {
    return {
        ciphertext,
        cipherMeta,
        willBeDestroyed,
        passphrase: '',
        plaintext: '',
        isDecrypting: false,
        decrypted: false,
        error: null,
        copied: false,
        needsPassphrase: false,
        keyMaterial: null,
        version: 1,

        init() {
            this.parseFragment();

            if (!this.needsPassphrase) {
                this.decrypt();
            }
        },

        parseFragment() {
            const fragment = window.location.hash.substring(1);

            if (!fragment) {
                this.error = 'Clé de déchiffrement manquante dans l\'URL';
                return;
            }

            try {
                const parsed = window.SecretCrypto.parseKeyFragment(fragment);
                this.keyMaterial = parsed.keyMaterial;
                this.needsPassphrase = parsed.hasPassphrase;
                this.version = parsed.version;
            } catch (e) {
                this.error = 'Fragment de clé invalide';
            }
        },

        async decrypt() {
            if (this.error && !this.needsPassphrase) {
                return;
            }

            this.error = null;
            this.isDecrypting = true;

            try {
                if (!window.SecretCrypto?.isCryptoAvailable()) {
                    throw new Error('Votre navigateur ne supporte pas le déchiffrement sécurisé');
                }

                const salt = this.cipherMeta.salt || null;
                const passphrase = this.needsPassphrase ? this.passphrase : null;

                if (this.needsPassphrase && !passphrase?.trim()) {
                    throw new Error('La passphrase est requise');
                }

                this.plaintext = await window.SecretCrypto.decryptSecret(
                    this.ciphertext,
                    this.cipherMeta.iv,
                    this.keyMaterial,
                    salt,
                    passphrase,
                    this.version
                );

                this.decrypted = true;
            } catch (e) {
                console.error('Decryption error:', e);

                if (e.name === 'OperationError') {
                    this.error = this.needsPassphrase
                        ? 'Passphrase incorrecte ou données corrompues'
                        : 'Échec du déchiffrement. La clé est peut-être incorrecte.';
                } else {
                    this.error = e.message || 'Une erreur est survenue lors du déchiffrement';
                }
            } finally {
                this.isDecrypting = false;
            }
        },

        async copyToClipboard() {
            try {
                await navigator.clipboard.writeText(this.plaintext);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch (e) {
                this.error = 'Impossible de copier dans le presse-papier';
            }
        }
    };
}
