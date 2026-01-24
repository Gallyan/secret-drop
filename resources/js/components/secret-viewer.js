export default function secretViewer(token) {
    return {
        token,
        isLoading: true,
        loadError: null,

        // Data from API
        type: null,
        cipherMeta: null,
        willBeDestroyed: false,

        // Text mode
        ciphertext: null,
        plaintext: '',

        // File mode
        filename: null,
        mime: null,
        size: null,

        // Common
        passphrase: '',
        isDecrypting: false,
        decrypted: false,
        error: null,
        copied: false,
        needsPassphrase: false,
        keyMaterial: null,
        version: 1,

        async init() {
            await this.loadSecret();

            if (!this.loadError && !this.needsPassphrase && !this.error) {
                this.decrypt();
            }
        },

        async loadSecret() {
            try {
                const response = await fetch(`/api/secrets/${this.token}`);
                const data = await response.json();

                if (!response.ok) {
                    if (response.status === 404) {
                        this.loadError = {
                            type: 'not_found',
                            message: 'Ce secret n\'existe pas ou a peut-être été supprimé.',
                        };
                    } else if (response.status === 410) {
                        this.loadError = {
                            type: 'unavailable',
                            reason: data.reason || 'unknown',
                            message: this.getUnavailableMessage(data.reason),
                        };
                    } else {
                        this.loadError = {
                            type: 'error',
                            message: 'Une erreur est survenue lors du chargement du secret.',
                        };
                    }

                    return;
                }

                this.type = data.type;
                this.cipherMeta = data.cipher_meta;
                this.willBeDestroyed = data.will_be_destroyed;

                if (data.type === 'text') {
                    this.ciphertext = data.ciphertext;
                } else {
                    this.filename = data.filename;
                    this.mime = data.mime;
                    this.size = data.size;
                }

                this.parseFragment();
            } catch (e) {
                console.error('Load error:', e);
                this.loadError = {
                    type: 'error',
                    message: 'Impossible de charger le secret. Vérifiez votre connexion.',
                };
            } finally {
                this.isLoading = false;
            }
        },

        getUnavailableMessage(reason) {
            switch (reason) {
                case 'expired':
                    return 'Ce secret a expiré et n\'est plus accessible.';
                case 'revoked':
                    return 'Ce secret a été révoqué par son créateur.';
                case 'already_read':
                    return 'Ce secret à usage unique a déjà été lu et n\'est plus accessible.';
                case 'max_views':
                    return 'Ce secret a atteint son nombre maximum de lectures et n\'est plus accessible.';
                default:
                    return 'Ce secret n\'est plus accessible.';
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
                this.error = e.message || 'Fragment de clé invalide';
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

                if (this.type === 'text') {
                    await this.decryptText(salt, passphrase);
                } else {
                    await this.decryptFile(salt, passphrase);
                }

                this.decrypted = true;
                await this.confirmRead();
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

        async decryptText(salt, passphrase) {
            this.plaintext = await window.SecretCrypto.decryptSecret(
                this.ciphertext,
                this.cipherMeta.iv,
                this.keyMaterial,
                salt,
                passphrase,
                this.version
            );
        },

        async decryptFile(salt, passphrase) {
            const response = await fetch(`/s/${this.token}/download`);
            if (!response.ok) {
                throw new Error('Impossible de télécharger le fichier chiffré');
            }

            const encryptedData = await response.arrayBuffer();

            const decryptedData = await window.SecretCrypto.decryptFile(
                encryptedData,
                this.cipherMeta.iv,
                this.keyMaterial,
                salt,
                passphrase,
                this.version
            );

            const blob = new Blob([decryptedData], { type: this.mime });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = this.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            URL.revokeObjectURL(url);
        },

        downloadAgain() {
            if (this.type === 'file' && this.decrypted) {
                this.decrypted = false;
                this.decrypt();
            }
        },

        formatFileSize(bytes) {
            if (!bytes) {
                return '';
            }
            if (bytes < 1024) {
                return bytes + ' o';
            }
            if (bytes < 1024 * 1024) {
                return (bytes / 1024).toFixed(1) + ' Ko';
            }
            return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
        },

        async copyToClipboard() {
            try {
                await navigator.clipboard.writeText(this.plaintext);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch (e) {
                this.error = 'Impossible de copier dans le presse-papier';
            }
        },

        async confirmRead() {
            try {
                await fetch(`/api/secrets/${this.token}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
            } catch (e) {
                console.error('Failed to confirm read:', e);
            }
        }
    };
}
