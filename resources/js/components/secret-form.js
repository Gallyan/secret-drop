export default function secretForm() {
    return {
        // Mode: 'text' or 'file'
        mode: 'text',

        // Text mode
        secret: '',

        // File mode
        file: null,
        isDragging: false,

        // Common options
        expiration: '7d',
        usageUnique: true,
        maxViews: null,
        passphrase: '',
        showPassphrase: false,
        showAdvanced: false,
        creatorEmail: '',

        // State
        isSubmitting: false,
        error: null,
        shareUrl: null,
        passphraseUsed: false,
        copied: false,

        setMode(newMode) {
            this.mode = newMode;
            this.error = null;
            // Reset mode-specific defaults
            if (newMode === 'file') {
                this.usageUnique = false;
            } else {
                this.usageUnique = true;
            }
        },

        handleFileDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer?.files;
            if (files?.length > 0) {
                this.selectFile(files[0]);
            }
        },

        handleFileSelect(event) {
            const files = event.target?.files;
            if (files?.length > 0) {
                this.selectFile(files[0]);
            }
        },

        selectFile(file) {
            const maxSize = 100 * 1024 * 1024; // 100MB
            if (file.size > maxSize) {
                this.error = 'Le fichier est trop volumineux (max 100 Mo)';
                return;
            }
            this.file = file;
            this.error = null;
        },

        removeFile() {
            this.file = null;
            // Reset file input
            const input = document.getElementById('file-input');
            if (input) {
                input.value = '';
            }
        },

        formatFileSize(bytes) {
            if (bytes < 1024) {
                return bytes + ' o';
            }
            if (bytes < 1024 * 1024) {
                return (bytes / 1024).toFixed(1) + ' Ko';
            }
            return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
        },

        async handleSubmit() {
            this.error = null;
            this.isSubmitting = true;

            try {
                if (!window.SecretCrypto?.isCryptoAvailable()) {
                    throw new Error('Votre navigateur ne supporte pas le chiffrement sécurisé');
                }

                const passphrase = this.passphrase?.trim() || null;

                if (this.mode === 'text') {
                    await this.submitText(passphrase);
                } else {
                    await this.submitFile(passphrase);
                }
            } catch (e) {
                console.error('Encryption error:', e);
                this.error = e.message || 'Une erreur est survenue lors du chiffrement';
            } finally {
                this.isSubmitting = false;
            }
        },

        async submitText(passphrase) {
            if (!this.secret.trim()) {
                throw new Error('Veuillez entrer un secret');
            }

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
            this.buildShareUrl(data.token, encrypted.keyMaterial, !!passphrase, encrypted.version);
        },

        async submitFile(passphrase) {
            if (!this.file) {
                throw new Error('Veuillez sélectionner un fichier');
            }

            const encrypted = await window.SecretCrypto.encryptFile(this.file, passphrase);

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

            // Create FormData for multipart upload
            const formData = new FormData();
            formData.append('type', 'file');
            formData.append('encrypted_file', encrypted.encryptedBlob, 'encrypted');
            formData.append('cipher_meta', JSON.stringify(cipherMeta));
            formData.append('filename', encrypted.filename);
            formData.append('mime', encrypted.mime);
            formData.append('size', encrypted.size.toString());
            formData.append('expiration', this.expiration);
            formData.append('usage_unique', this.usageUnique ? '1' : '0');
            if (this.maxViews) {
                formData.append('max_views', this.maxViews.toString());
            }
            if (this.creatorEmail?.trim()) {
                formData.append('creator_email', this.creatorEmail.trim());
            }

            const response = await fetch('/api/secrets', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'Erreur lors de la création du secret');
            }

            const data = await response.json();
            this.buildShareUrl(data.token, encrypted.keyMaterial, !!passphrase, encrypted.version);
        },

        buildShareUrl(token, keyMaterial, hasPassphrase, version) {
            const keyFragment = window.SecretCrypto.buildKeyFragment(keyMaterial, hasPassphrase, version);
            this.shareUrl = `${window.location.origin}/s/${token}#${keyFragment}`;
            this.passphraseUsed = hasPassphrase;
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
            this.mode = 'text';
            this.secret = '';
            this.file = null;
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
