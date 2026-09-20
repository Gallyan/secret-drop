import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import * as SecretCrypto from '../../resources/js/crypto.js';
import secretForm from '../../resources/js/components/secret-form.js';

const MAX_FILE_SIZE = 10 * 1024 * 1024;
const MAX_TEXT_CHARS = 50000;
const MIN_PASSPHRASE_LENGTH = 12;

/** Clés réellement consommées par le composant, valeurs sentinelles pour les assertions. */
const TRANSLATIONS = {
    crypto_passphrase_too_short: 'PASSPHRASE_TOO_SHORT',
    crypto_not_supported: 'CRYPTO_NOT_SUPPORTED',
    crypto_creation_error: 'CREATION_ERROR',
    crypto_enter_secret: 'ENTER_SECRET',
    crypto_select_file: 'SELECT_FILE',
    text_too_large: 'TEXT_TOO_LARGE',
    file_too_large: 'FILE_TOO_LARGE',
};

function jsonResponse(body, { ok = true, status = 200 } = {}) {
    return {
        ok,
        status,
        headers: { get: () => null },
        json: async () => body,
    };
}

function createForm(overrides = {}) {
    const form = secretForm();

    form.$el = document.createElement('div');
    form.$nextTick = (callback) => {
        callback?.();
    };

    return Object.assign(form, overrides);
}

function capturedBody(call) {
    return call[1].body;
}

describe('secretForm', () => {
    beforeEach(() => {
        window.translations = { ...TRANSLATIONS };
        window.SecretCrypto = { ...SecretCrypto };
        global.fetch = vi.fn(async () => jsonResponse({ token: 'tok_abcdef123456' }));
        document.head.innerHTML = '<meta name="csrf-token" content="csrf-value">';
        document.body.innerHTML = '';
    });

    afterEach(() => {
        delete window.translations;
        delete window.SecretCrypto;
    });

    describe('longueur minimale de la phrase secrète', () => {
        it('refuse une phrase secrète de 11 caractères sans appeler le serveur', async () => {
            const form = createForm({ secret: 'hello', passphrase: 'a'.repeat(MIN_PASSPHRASE_LENGTH - 1) });

            await form.handleSubmit();

            expect(global.fetch).not.toHaveBeenCalled();
            expect(form.error).toBe(TRANSLATIONS.crypto_passphrase_too_short);
            expect(form.isSubmitting).toBe(false);
            expect(form.shareUrl).toBeNull();
        });

        it('ouvre les options avancées pour montrer les critères quand elle est trop courte', async () => {
            const form = createForm({ secret: 'hello', passphrase: 'short' });

            expect(form.showAdvanced).toBe(false);

            await form.handleSubmit();

            expect(form.showAdvanced).toBe(true);
        });

        it('accepte exactement 12 caractères', async () => {
            const encryptSecret = vi.spyOn(window.SecretCrypto, 'encryptSecret')
                .mockResolvedValue({ ciphertext: 'ct', iv: 'iv', salt: 's', iv2: 'iv2', keyMaterial: 'k'.repeat(43), version: 1 });
            const form = createForm({ secret: 'hello', passphrase: 'a'.repeat(MIN_PASSPHRASE_LENGTH) });

            await form.handleSubmit();

            expect(form.error).toBeNull();
            expect(global.fetch).toHaveBeenCalledTimes(1);
            expect(encryptSecret).toHaveBeenCalledWith('hello', 'a'.repeat(MIN_PASSPHRASE_LENGTH));
        });

        it('autorise une phrase secrète vide et n’ajoute alors aucune couche', async () => {
            const encryptSecret = vi.spyOn(window.SecretCrypto, 'encryptSecret')
                .mockResolvedValue({ ciphertext: 'ct', iv: 'iv', salt: null, iv2: null, keyMaterial: 'k'.repeat(43), version: 1 });
            const form = createForm({ secret: 'hello', passphrase: '' });

            await form.handleSubmit();

            expect(form.error).toBeNull();
            expect(encryptSecret).toHaveBeenCalledWith('hello', null);
            expect(form.passphraseUsed).toBe(false);

            const payload = JSON.parse(capturedBody(global.fetch.mock.calls[0]));
            expect(payload.cipher_meta.has_passphrase).toBe(false);
            expect(payload.cipher_meta.salt).toBeUndefined();
        });

        it('traite une phrase secrète faite uniquement d’espaces comme vide', async () => {
            const encryptSecret = vi.spyOn(window.SecretCrypto, 'encryptSecret')
                .mockResolvedValue({ ciphertext: 'ct', iv: 'iv', salt: null, iv2: null, keyMaterial: 'k'.repeat(43), version: 1 });
            const form = createForm({ secret: 'hello', passphrase: '      ' });

            expect(form.isPassphraseTooShort()).toBe(false);

            await form.handleSubmit();

            expect(form.error).toBeNull();
            expect(encryptSecret).toHaveBeenCalledWith('hello', null);
        });

        it('isPassphraseTooShort ne se déclenche que sur une saisie non vide trop courte', () => {
            const form = createForm();

            form.passphrase = '';
            expect(form.isPassphraseTooShort()).toBe(false);

            form.passphrase = 'a'.repeat(MIN_PASSPHRASE_LENGTH - 1);
            expect(form.isPassphraseTooShort()).toBe(true);

            form.passphrase = 'a'.repeat(MIN_PASSPHRASE_LENGTH);
            expect(form.isPassphraseTooShort()).toBe(false);
            expect(form.hasMinLength()).toBe(true);
        });
    });

    describe('cohérence du trim de la phrase secrète', () => {
        it('ignore les espaces de bord dans les contrôles de longueur', () => {
            const form = createForm({ passphrase: `   ${'a'.repeat(MIN_PASSPHRASE_LENGTH)}   ` });

            expect(form.trimmedPassphrase()).toBe('a'.repeat(MIN_PASSPHRASE_LENGTH));
            expect(form.hasMinLength()).toBe(true);
            expect(form.isPassphraseTooShort()).toBe(false);
        });

        it('rejette une phrase secrète courte rembourrée d’espaces', async () => {
            const form = createForm({ secret: 'hello', passphrase: '     short     ' });

            expect(form.hasMinLength()).toBe(false);
            expect(form.isPassphraseTooShort()).toBe(true);

            await form.handleSubmit();

            expect(global.fetch).not.toHaveBeenCalled();
            expect(form.error).toBe(TRANSLATIONS.crypto_passphrase_too_short);
        });

        it('calcule la force sur la version trimmée', () => {
            const padded = createForm({ passphrase: '   Abcdef123456!   ' });
            const trimmed = createForm({ passphrase: 'Abcdef123456!' });

            expect(padded.getPassphraseStrength()).toBe(trimmed.getPassphraseStrength());
            expect(padded.getPassphraseStrengthClass()).toBe(trimmed.getPassphraseStrengthClass());
        });

        it('évalue les critères de composition sur la version trimmée', () => {
            const form = createForm({ passphrase: '   Abcdef123456!   ' });

            expect(form.hasLowercase()).toBe(true);
            expect(form.hasUppercase()).toBe(true);
            expect(form.hasDigit()).toBe(true);
            expect(form.hasSpecial()).toBe(true);
        });

        it('ne compte pas les espaces de bord comme caractère spécial', () => {
            const form = createForm({ passphrase: '  abcdefghijkl  ' });

            expect(form.hasSpecial()).toBe(false);
            expect(form.hasUppercase()).toBe(false);
            expect(form.hasDigit()).toBe(false);
        });

        it('chiffre avec la phrase secrète trimmée', async () => {
            const encryptSecret = vi.spyOn(window.SecretCrypto, 'encryptSecret')
                .mockResolvedValue({ ciphertext: 'ct', iv: 'iv', salt: 'salt', iv2: 'iv2', keyMaterial: 'k'.repeat(43), version: 1 });
            const form = createForm({ secret: 'hello', passphrase: '  correct horse battery  ' });

            await form.handleSubmit();

            expect(encryptSecret).toHaveBeenCalledWith('hello', 'correct horse battery');
        });

        it('chiffre le fichier avec la phrase secrète trimmée', async () => {
            const encryptFile = vi.spyOn(window.SecretCrypto, 'encryptFile')
                .mockResolvedValue({
                    encryptedBlob: new Blob(['x']),
                    iv: 'iv',
                    salt: 'salt',
                    iv2: 'iv2',
                    keyMaterial: 'k'.repeat(43),
                    version: 1,
                });
            const file = new File(['payload'], 'note.txt', { type: 'text/plain' });
            const form = createForm({ mode: 'file', file, passphrase: '  correct horse battery  ' });

            await form.handleSubmit();

            expect(encryptFile).toHaveBeenCalledWith(file, 'correct horse battery');
        });
    });

    describe('limites de taille et bascule de mode', () => {
        it('compte le secret en caractères, pas en octets UTF-8', () => {
            const form = createForm({ secret: 'éàü' });

            expect(form.secretLength()).toBe(3);
            expect(form.isSecretTooLong()).toBe(false);
        });

        it('accepte 50 000 caractères et refuse 50 001', () => {
            const form = createForm({ secret: 'a'.repeat(MAX_TEXT_CHARS) });
            expect(form.isSecretTooLong()).toBe(false);

            form.secret = 'a'.repeat(MAX_TEXT_CHARS + 1);
            expect(form.isSecretTooLong()).toBe(true);
        });

        it('refuse l’envoi d’un texte trop long sans appeler le serveur', async () => {
            const form = createForm({ secret: 'a'.repeat(MAX_TEXT_CHARS + 1) });

            await form.handleSubmit();

            expect(global.fetch).not.toHaveBeenCalled();
            expect(form.error).toBe(TRANSLATIONS.text_too_large);
        });

        it('accepte un texte multi-octets sous la limite en caractères', async () => {
            const form = createForm({ secret: 'é'.repeat(MAX_TEXT_CHARS / 2 + 1) });

            expect(form.secretLength()).toBeLessThan(MAX_TEXT_CHARS);

            await form.handleSubmit();

            expect(form.error).toBeNull();
            expect(global.fetch).toHaveBeenCalledTimes(1);
        });

        it('affiche le compteur en caractères', () => {
            const form = createForm({ secret: 'é'.repeat(1234) });

            expect(form.secretCounterLabel()).toBe(
                `${(1234).toLocaleString()} / ${MAX_TEXT_CHARS.toLocaleString()}`
            );
        });

        it('refuse un texte vide ou blanc', async () => {
            const form = createForm({ secret: '   ' });

            await form.handleSubmit();

            expect(global.fetch).not.toHaveBeenCalled();
            expect(form.error).toBe(TRANSLATIONS.crypto_enter_secret);
        });

        it('accepte un fichier de 10 Mo pile et refuse un octet de plus', () => {
            const form = createForm();

            form.selectFile({ name: 'ok.bin', size: MAX_FILE_SIZE, type: 'application/octet-stream' });
            expect(form.error).toBeNull();
            expect(form.file).not.toBeNull();

            form.selectFile({ name: 'too-big.bin', size: MAX_FILE_SIZE + 1, type: 'application/octet-stream' });
            expect(form.error).toBe(TRANSLATIONS.file_too_large);
            expect(form.file.name).toBe('ok.bin');
        });

        it('refuse l’envoi en mode fichier sans fichier sélectionné', async () => {
            const form = createForm({ mode: 'file', file: null });

            await form.handleSubmit();

            expect(global.fetch).not.toHaveBeenCalled();
            expect(form.error).toBe(TRANSLATIONS.crypto_select_file);
        });

        it('bascule de mode et remet l’erreur à zéro', () => {
            const form = createForm({ error: 'boom' });

            form.setModeFile();
            expect(form.mode).toBe('file');
            expect(form.error).toBeNull();

            form.error = 'boom again';
            form.setModeText();
            expect(form.mode).toBe('text');
            expect(form.error).toBeNull();
        });

        it('vide le champ fichier lors du retrait', () => {
            document.body.innerHTML = '<input type="file" id="file-input">';
            const form = createForm({ file: { name: 'a.bin', size: 10 } });

            expect(form.fileName()).toBe('a.bin');

            form.removeFile();

            expect(form.file).toBeNull();
            expect(document.getElementById('file-input').value).toBe('');
        });
    });

    describe('charge utile envoyée au serveur', () => {
        it('envoie uniquement le chiffré et les métadonnées, jamais le clair ni la clé', async () => {
            const plaintext = 'valeur ultra confidentielle';
            const form = createForm({
                secret: plaintext,
                expiration: '1h',
                maxViews: 3,
                creatorEmail: '  alice@example.test  ',
            });

            await form.handleSubmit();

            const rawBody = capturedBody(global.fetch.mock.calls[0]);
            const payload = JSON.parse(rawBody);

            expect(payload.type).toBe('text');
            expect(payload.expiration).toBe('1h');
            expect(payload.max_views).toBe(3);
            expect(payload.creator_email).toBe('alice@example.test');
            expect(payload.split_mode).toBe(false);
            expect(payload.cipher_meta).toEqual({
                alg: 'AES-256-GCM',
                iv: expect.any(String),
                version: 1,
                has_passphrase: false,
            });

            const keyMaterial = form.shareUrl.split('#')[1].substring(1);
            expect(keyMaterial).toHaveLength(43);
            expect(rawBody).not.toContain(plaintext);
            expect(rawBody).not.toContain(keyMaterial);
            expect(Object.keys(payload)).toEqual([
                'type', 'ciphertext', 'cipher_meta', 'expiration', 'max_views', 'creator_email', 'split_mode',
            ]);
        });

        it('normalise max_views et creator_email vides en null', async () => {
            const form = createForm({ secret: 'hello', maxViews: null, creatorEmail: '   ' });

            await form.handleSubmit();

            const payload = JSON.parse(capturedBody(global.fetch.mock.calls[0]));
            expect(payload.max_views).toBeNull();
            expect(payload.creator_email).toBeNull();
        });

        it('déclare la couche passphrase dans cipher_meta sans transmettre la phrase secrète', async () => {
            const passphrase = 'correct horse battery';
            const form = createForm({ secret: 'hello', passphrase, splitMode: true });

            await form.handleSubmit();

            const rawBody = capturedBody(global.fetch.mock.calls[0]);
            const payload = JSON.parse(rawBody);

            expect(payload.split_mode).toBe(true);
            expect(payload.cipher_meta.has_passphrase).toBe(true);
            expect(payload.cipher_meta.kdf).toBe('PBKDF2-SHA256-600k');
            expect(payload.cipher_meta.salt).toEqual(expect.any(String));
            expect(payload.cipher_meta.iv2).toEqual(expect.any(String));
            expect(rawBody).not.toContain(passphrase);
            expect(rawBody).not.toContain(form.shareKey.substring(1));
            expect(form.passphraseUsed).toBe(true);
        });

        it('envoie le fichier en multipart sans nom de fichier ni clé en clair', async () => {
            const form = createForm({
                mode: 'file',
                file: new File(['contenu du fichier'], 'facture-secrete.txt', { type: 'text/plain' }),
                splitMode: true,
                maxViews: 2,
                creatorEmail: ' bob@example.test ',
            });

            await form.handleSubmit();

            expect(form.error).toBeNull();

            const body = capturedBody(global.fetch.mock.calls[0]);
            expect(body).toBeInstanceOf(FormData);
            expect(body.get('type')).toBe('file');
            expect(body.get('split_mode')).toBe('1');
            expect(body.get('max_views')).toBe('2');
            expect(body.get('creator_email')).toBe('bob@example.test');
            expect(body.get('filename')).toBeNull();
            expect(body.get('mime')).toBeNull();

            const cipherMeta = JSON.parse(body.get('cipher_meta'));
            expect(cipherMeta.has_passphrase).toBe(false);
            expect(JSON.stringify(cipherMeta)).not.toContain(form.shareKey.substring(1));
        });

        it('remonte le message d’erreur du serveur', async () => {
            global.fetch = vi.fn(async () => jsonResponse({ message: 'Trop de secrets' }, { ok: false, status: 422 }));
            const form = createForm({ secret: 'hello' });

            await form.handleSubmit();

            expect(form.error).toBe('Trop de secrets');
            expect(form.shareUrl).toBeNull();
        });

        it('échoue proprement quand WebCrypto est indisponible', async () => {
            window.SecretCrypto.isCryptoAvailable = () => false;
            const form = createForm({ secret: 'hello' });

            await form.handleSubmit();

            expect(global.fetch).not.toHaveBeenCalled();
            expect(form.error).toBe(TRANSLATIONS.crypto_not_supported);
        });
    });

    describe('construction du lien de partage', () => {
        const keyMaterial = 'k'.repeat(43);

        it('place la clé dans le fragment hors mode séparé', () => {
            const form = createForm({ splitMode: false });

            form.buildShareUrl('tok123', keyMaterial, false);

            expect(form.shareUrl).toBe(`${window.location.origin}/s/tok123#A${keyMaterial}`);
            expect(form.shareKey).toBeNull();
            expect(form.passphraseUsed).toBe(false);
        });

        it('garde la clé hors de l’URL en mode séparé', () => {
            const form = createForm({ splitMode: true });

            form.buildShareUrl('tok123', keyMaterial, true);

            expect(form.shareUrl).toBe(`${window.location.origin}/s/tok123`);
            expect(form.shareUrl).not.toContain('#');
            expect(form.shareUrl).not.toContain(keyMaterial);
            expect(form.shareKey).toBe(`A${keyMaterial}`);
            expect(form.passphraseUsed).toBe(true);
        });

        it('réinitialise l’état sensible via reset()', () => {
            const form = createForm({ splitMode: true, passphrase: 'abcdefghijkl', secret: 'hello' });
            form.buildShareUrl('tok123', keyMaterial, true);

            form.reset();

            expect(form.shareUrl).toBeNull();
            expect(form.shareKey).toBeNull();
            expect(form.passphrase).toBe('');
            expect(form.secret).toBe('');
            expect(form.splitMode).toBe(false);
            expect(form.passphraseUsed).toBe(false);
        });
    });
});
