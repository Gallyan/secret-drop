import QRCode from 'qrcode';

function t(key) {
    return window.translations?.[key] || key;
}

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
        splitMode: false,

        // State
        isSubmitting: false,
        error: null,
        shareUrl: null,
        shareKey: null,
        passphraseUsed: false,
        copied: false,
        keyCopied: false,
        showQrCode: false,
        qrCodeDataUrl: null,

        getPassphraseStrength() {
            const passphrase = this.passphrase;
            if (!passphrase) return 0;

            let score = 0;
            const len = passphrase.length;

            // Length scoring (main factor)
            if (len >= 8) score += 20;
            if (len >= 12) score += 20;
            if (len >= 16) score += 15;
            if (len >= 20) score += 15;

            // Character variety
            if (/[a-z]/.test(passphrase)) score += 7;
            if (/[A-Z]/.test(passphrase)) score += 8;
            if (/[0-9]/.test(passphrase)) score += 7;
            if (/[^a-zA-Z0-9]/.test(passphrase)) score += 8;

            return Math.min(100, score);
        },

        getPassphraseStrengthColor() {
            const strength = this.getPassphraseStrength();
            if (strength < 25) return 'bg-red-500';
            if (strength < 50) return 'bg-orange-500';
            if (strength < 75) return 'bg-yellow-500';
            return 'bg-green-500';
        },

        // Captcha state
        captchaRequired: false,
        captchaToken: null,
        captchaChallenge: null,
        captchaAnswer: '',
        pendingEncrypted: null,
        pendingPassphrase: null,

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
                this.error = t('file_too_large');
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
                    throw new Error(t('crypto_not_supported'));
                }

                const passphrase = this.passphrase?.trim() || null;

                if (this.mode === 'text') {
                    await this.submitText(passphrase);
                } else {
                    await this.submitFile(passphrase);
                }
            } catch (e) {
                console.error('Encryption error:', e);
                this.error = e.message || t('crypto_creation_error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async submitText(passphrase) {
            if (!this.secret.trim()) {
                throw new Error(t('crypto_enter_secret'));
            }

            const encrypted = await window.SecretCrypto.encryptSecret(this.secret, passphrase);

            const cipherMeta = {
                alg: 'AES-256-GCM',
                iv: encrypted.iv,
                version: encrypted.version,
                has_passphrase: !!encrypted.salt
            };

            if (encrypted.salt) {
                cipherMeta.salt = encrypted.salt;
                cipherMeta.iv2 = encrypted.iv2;
                cipherMeta.kdf = 'PBKDF2-SHA256-600k';
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const payload = {
                type: 'text',
                ciphertext: encrypted.ciphertext,
                cipher_meta: cipherMeta,
                expiration: this.expiration,
                usage_unique: this.usageUnique,
                max_views: this.maxViews || null,
                creator_email: this.creatorEmail?.trim() || null,
                split_mode: this.splitMode
            };

            // Add captcha if required
            if (this.captchaToken && this.captchaAnswer) {
                payload.captcha_token = this.captchaToken;
                payload.captcha_answer = this.captchaAnswer;
            }

            const response = await fetch('/api/secrets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok) {
                if (response.status === 429 && data.captcha_required) {
                    this.captchaRequired = true;
                    this.captchaToken = data.captcha_token;
                    this.captchaChallenge = data.captcha_challenge;
                    this.captchaAnswer = '';
                    this.pendingEncrypted = encrypted;
                    this.pendingPassphrase = passphrase;
                    return;
                }
                throw new Error(data.message || t('crypto_creation_error'));
            }

            this.clearCaptcha();
            this.buildShareUrl(data.token, encrypted.keyMaterial, !!passphrase, encrypted.version);
        },

        async submitFile(passphrase) {
            if (!this.file) {
                throw new Error(t('crypto_select_file'));
            }

            const encrypted = await window.SecretCrypto.encryptFile(this.file, passphrase);

            const cipherMeta = {
                alg: 'AES-256-GCM',
                iv: encrypted.iv,
                version: encrypted.version,
                has_passphrase: !!encrypted.salt
            };

            if (encrypted.salt) {
                cipherMeta.salt = encrypted.salt;
                cipherMeta.iv2 = encrypted.iv2;
                cipherMeta.kdf = 'PBKDF2-SHA256-600k';
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
            if (this.splitMode) {
                formData.append('split_mode', '1');
            }

            // Add captcha if required
            if (this.captchaToken && this.captchaAnswer) {
                formData.append('captcha_token', this.captchaToken);
                formData.append('captcha_answer', this.captchaAnswer);
            }

            const response = await fetch('/api/secrets', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                if (response.status === 429 && data.captcha_required) {
                    this.captchaRequired = true;
                    this.captchaToken = data.captcha_token;
                    this.captchaChallenge = data.captcha_challenge;
                    this.captchaAnswer = '';
                    this.pendingEncrypted = encrypted;
                    this.pendingPassphrase = passphrase;
                    return;
                }
                throw new Error(data.message || t('crypto_creation_error'));
            }

            this.clearCaptcha();
            this.buildShareUrl(data.token, encrypted.keyMaterial, !!passphrase);
        },

        buildShareUrl(token, keyMaterial, hasPassphrase) {
            const keyFragment = window.SecretCrypto.buildKeyFragment(keyMaterial);

            if (this.splitMode) {
                this.shareUrl = `${window.location.origin}/s/${token}`;
                this.shareKey = keyFragment;
            } else {
                this.shareUrl = `${window.location.origin}/s/${token}#${keyFragment}`;
                this.shareKey = null;
            }

            this.passphraseUsed = hasPassphrase;
        },

        async copyToClipboard() {
            try {
                await navigator.clipboard.writeText(this.shareUrl);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch (e) {
                this.error = t('crypto_clipboard_failed');
            }
        },

        async copyKeyToClipboard() {
            try {
                await navigator.clipboard.writeText(this.shareKey);
                this.keyCopied = true;
                setTimeout(() => this.keyCopied = false, 2000);
            } catch (e) {
                this.error = t('crypto_clipboard_failed');
            }
        },

        async toggleQrCode() {
            if (this.showQrCode) {
                this.showQrCode = false;
                return;
            }

            if (!this.qrCodeDataUrl) {
                try {
                    // For split mode, generate QR for full URL (link + key)
                    const fullUrl = this.shareKey
                        ? `${this.shareUrl}#${this.shareKey}`
                        : this.shareUrl;

                    this.qrCodeDataUrl = await QRCode.toDataURL(fullUrl, {
                        width: 256,
                        margin: 2,
                        color: {
                            dark: '#1e1b4b',
                            light: '#ffffff'
                        },
                        errorCorrectionLevel: 'M'
                    });
                } catch (e) {
                    console.error('QR Code generation failed:', e);
                    this.error = t('qr_generation_failed');
                    return;
                }
            }

            this.showQrCode = true;
        },

        async downloadQrCode() {
            if (!this.qrCodeDataUrl) {
                return;
            }

            const link = document.createElement('a');
            link.download = 'secret-drop-qrcode.png';
            link.href = this.qrCodeDataUrl;
            link.click();
        },

        clearCaptcha() {
            this.captchaRequired = false;
            this.captchaToken = null;
            this.captchaChallenge = null;
            this.captchaAnswer = '';
            this.pendingEncrypted = null;
            this.pendingPassphrase = null;
        },

        async submitWithCaptcha() {
            if (!this.captchaAnswer.trim()) {
                this.error = t('captcha_invalid');
                return;
            }

            this.error = null;
            this.isSubmitting = true;

            try {
                if (this.mode === 'text') {
                    await this.submitText(this.pendingPassphrase);
                } else {
                    await this.submitFile(this.pendingPassphrase);
                }
            } catch (e) {
                console.error('Submission error:', e);
                this.error = e.message || t('crypto_creation_error');
            } finally {
                this.isSubmitting = false;
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
            this.splitMode = false;
            this.showAdvanced = false;
            this.shareUrl = null;
            this.shareKey = null;
            this.passphraseUsed = false;
            this.error = null;
            this.showQrCode = false;
            this.qrCodeDataUrl = null;
            this.clearCaptcha();
        }
    };
}
