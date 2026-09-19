import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import * as SecretCrypto from '../../resources/js/crypto.js';
import secretViewer from '../../resources/js/components/secret-viewer.js';

const KEY_MATERIAL = 'k'.repeat(43);
const FRAGMENT = `A${KEY_MATERIAL}`;
const PREVIOUS_FETCHES_TEMPLATE = 'Retrievals before you: :count';

/** Clés réellement consommées par le composant, valeurs sentinelles pour les assertions. */
const TRANSLATIONS = {
    crypto_not_supported: 'CRYPTO_NOT_SUPPORTED',
    crypto_passphrase_required: 'PASSPHRASE_REQUIRED',
    crypto_passphrase_incorrect: 'PASSPHRASE_INCORRECT',
    crypto_decryption_failed: 'DECRYPTION_FAILED',
    crypto_decryption_error: 'DECRYPTION_ERROR',
    crypto_file_download_failed: 'FILE_DOWNLOAD_FAILED',
    secret_not_exist: 'SECRET_NOT_EXIST',
    secret_unavailable_generic: 'SECRET_UNAVAILABLE',
    error_loading: 'ERROR_LOADING',
    error_connection: 'ERROR_CONNECTION',
};

function jsonResponse(body, { ok = true, status = 200, headers = {} } = {}) {
    return {
        ok,
        status,
        headers: { get: (name) => (name in headers ? headers[name] : null) },
        json: async () => body,
    };
}

function createViewer(overrides = {}) {
    const element = document.createElement('div');
    element.dataset.token = 'tok_abcdef';
    element.dataset.previousFetchesCount = PREVIOUS_FETCHES_TEMPLATE;
    document.body.appendChild(element);

    const viewer = secretViewer();
    viewer.$el = element;
    viewer.token = element.dataset.token;
    viewer.$nextTick = (callback) => {
        callback?.();
    };

    return Object.assign(viewer, overrides);
}

function setHash(hash) {
    window.location.hash = hash;
}

describe('secretViewer', () => {
    beforeEach(() => {
        window.translations = { ...TRANSLATIONS };
        window.SecretCrypto = { ...SecretCrypto };
        document.body.innerHTML = '';
        setHash('');
        global.fetch = vi.fn(async () => jsonResponse({}));
        Object.defineProperty(navigator, 'sendBeacon', {
            value: vi.fn(() => true),
            configurable: true,
            writable: true,
        });
    });

    afterEach(() => {
        vi.useRealTimers();
        delete window.translations;
        delete window.SecretCrypto;
    });

    describe('avertissement previous_fetches', () => {
        it('n’affiche rien quand aucune récupération n’a eu lieu', () => {
            const singleUse = createViewer({ singleUse: true, previousFetches: 0 });
            const multiView = createViewer({ singleUse: false, previousFetches: 0 });

            expect(singleUse.hasSuspiciousPreviousFetches()).toBe(false);
            expect(singleUse.hasNeutralPreviousFetches()).toBe(false);
            expect(multiView.hasSuspiciousPreviousFetches()).toBe(false);
            expect(multiView.hasNeutralPreviousFetches()).toBe(false);
        });

        it('affiche l’avertissement neutre pour un secret multi-lectures', () => {
            const viewer = createViewer({ singleUse: false, previousFetches: 2 });
            viewer.previousFetchesTemplate = PREVIOUS_FETCHES_TEMPLATE;

            expect(viewer.hasNeutralPreviousFetches()).toBe(true);
            expect(viewer.hasSuspiciousPreviousFetches()).toBe(false);
            expect(viewer.previousFetchesText()).toBe('Retrievals before you: 2');
        });

        it('affiche l’avertissement fort pour un secret à lecture unique', () => {
            const viewer = createViewer({ singleUse: true, previousFetches: 1 });
            viewer.previousFetchesTemplate = PREVIOUS_FETCHES_TEMPLATE;

            expect(viewer.hasSuspiciousPreviousFetches()).toBe(true);
            expect(viewer.hasNeutralPreviousFetches()).toBe(false);
            expect(viewer.previousFetchesText()).toBe('Retrievals before you: 1');
        });

        it('lit le compteur et le drapeau lecture unique depuis l’API', async () => {
            global.fetch = vi.fn(async () => jsonResponse({
                type: 'text',
                cipher_meta: { iv: 'iv' },
                ciphertext: 'ct',
                will_be_destroyed: false,
                single_use: true,
                previous_fetches: '3',
            }));
            setHash(`#${FRAGMENT}`);
            const viewer = createViewer();
            viewer.token = 'tok_abcdef';
            viewer.previousFetchesTemplate = PREVIOUS_FETCHES_TEMPLATE;

            await viewer.loadSecret();

            expect(viewer.previousFetches).toBe(3);
            expect(viewer.singleUse).toBe(true);
            expect(viewer.hasSuspiciousPreviousFetches()).toBe(true);
            expect(viewer.previousFetchesText()).toBe('Retrievals before you: 3');
        });

        it('retombe sur 0 quand le compteur est absent ou illisible', async () => {
            global.fetch = vi.fn(async () => jsonResponse({
                type: 'text',
                cipher_meta: { iv: 'iv' },
                ciphertext: 'ct',
                single_use: 1,
            }));
            setHash(`#${FRAGMENT}`);
            const viewer = createViewer();
            viewer.token = 'tok_abcdef';

            await viewer.loadSecret();

            expect(viewer.previousFetches).toBe(0);
            expect(viewer.singleUse).toBe(false);
            expect(viewer.hasSuspiciousPreviousFetches()).toBe(false);
        });

        it('relève le compteur avec l’en-tête du téléchargement, sans compter ses propres appels', () => {
            const viewer = createViewer({ singleUse: true, previousFetches: 0 });

            viewer.trackDownloadFetches('1');
            expect(viewer.previousFetches).toBe(1);

            viewer.trackDownloadFetches('2');
            expect(viewer.previousFetches).toBe(1);
        });

        it('ignore un en-tête absent', () => {
            const viewer = createViewer({ previousFetches: 4 });

            viewer.trackDownloadFetches(null);

            expect(viewer.previousFetches).toBe(4);
            expect(viewer.ownDownloads).toBe(1);
        });
    });

    describe('identifiant de lecture', () => {
        it('est un identifiant hexadécimal de 128 bits', () => {
            const viewer = createViewer();

            expect(viewer.readId).toMatch(/^[0-9a-f]{32}$/);
        });

        it('est tiré au sort une fois par chargement de page', () => {
            const ids = new Set([createViewer().readId, createViewer().readId, createViewer().readId]);

            expect(ids.size).toBe(3);
        });

        it('reste identique entre les tentatives de confirmRead', async () => {
            vi.useFakeTimers();
            global.fetch = vi.fn()
                .mockResolvedValueOnce(jsonResponse({}, { ok: false, status: 500 }))
                .mockRejectedValueOnce(new Error('network down'))
                .mockResolvedValueOnce(jsonResponse({ ok: true }));
            const viewer = createViewer();

            const pending = viewer.confirmRead();
            await vi.advanceTimersByTimeAsync(10000);
            await pending;

            expect(global.fetch).toHaveBeenCalledTimes(3);
            const bodies = global.fetch.mock.calls.map(call => JSON.parse(call[1].body));
            expect(bodies).toEqual([
                { read_id: viewer.readId },
                { read_id: viewer.readId },
                { read_id: viewer.readId },
            ]);
            expect(navigator.sendBeacon).not.toHaveBeenCalled();
        });
    });

    describe('confirmRead', () => {
        it('confirme la lecture en un seul appel quand le serveur répond OK', async () => {
            const viewer = createViewer();

            await viewer.confirmRead();

            expect(global.fetch).toHaveBeenCalledTimes(1);
            const [url, options] = global.fetch.mock.calls[0];
            expect(url).toBe('/api/secrets/tok_abcdef/read');
            expect(options.method).toBe('POST');
            expect(options.headers['Content-Type']).toBe('application/json');
            expect(JSON.parse(options.body)).toEqual({ read_id: viewer.readId });
            expect(navigator.sendBeacon).not.toHaveBeenCalled();
        });

        it('réessaie jusqu’à trois fois puis bascule sur sendBeacon avec le même identifiant', async () => {
            vi.useFakeTimers();
            global.fetch = vi.fn(async () => jsonResponse({}, { ok: false, status: 503 }));
            const viewer = createViewer();

            const pending = viewer.confirmRead();
            await vi.advanceTimersByTimeAsync(10000);
            await pending;

            expect(global.fetch).toHaveBeenCalledTimes(3);
            expect(navigator.sendBeacon).toHaveBeenCalledTimes(1);

            const [beaconUrl, blob] = navigator.sendBeacon.mock.calls[0];
            expect(beaconUrl).toBe('/api/secrets/tok_abcdef/read');
            expect(blob.type).toBe('application/json');
            expect(JSON.parse(await blob.text())).toEqual({ read_id: viewer.readId });
        });

        it('bascule aussi sur sendBeacon quand le réseau échoue à chaque tentative', async () => {
            vi.useFakeTimers();
            global.fetch = vi.fn(async () => {
                throw new Error('offline');
            });
            const viewer = createViewer();

            const pending = viewer.confirmRead();
            await vi.advanceTimersByTimeAsync(10000);
            await pending;

            expect(global.fetch).toHaveBeenCalledTimes(3);
            expect(navigator.sendBeacon).toHaveBeenCalledTimes(1);
        });
    });

    describe('récupération de la clé', () => {
        it('lit la clé depuis le fragment d’URL', async () => {
            setHash(`#${FRAGMENT}`);
            const viewer = createViewer({ cipherMeta: { iv: 'iv' } });

            viewer.parseFragment();

            expect(viewer.keyMaterial).toBe(KEY_MATERIAL);
            expect(viewer.version).toBe(1);
            expect(viewer.needsManualKey).toBe(false);
            expect(viewer.needsPassphrase).toBe(false);
        });

        it('demande la clé manuellement quand le fragment est absent', () => {
            setHash('');
            const viewer = createViewer();

            viewer.parseFragment();

            expect(viewer.needsManualKey).toBe(true);
            expect(viewer.keyMaterial).toBeNull();
        });

        it('signale une passphrase requise d’après les métadonnées serveur', () => {
            setHash(`#${FRAGMENT}`);
            const viewer = createViewer({ cipherMeta: { iv: 'iv', has_passphrase: true } });

            viewer.parseFragment();

            expect(viewer.needsPassphrase).toBe(true);
        });

        it('accepte une clé saisie manuellement avec des espaces autour', () => {
            const viewer = createViewer({ cipherMeta: { iv: 'iv' }, manualKey: `  ${FRAGMENT}  `, willBeDestroyed: true });

            viewer.submitManualKey();

            expect(viewer.error).toBeNull();
            expect(viewer.keyMaterial).toBe(KEY_MATERIAL);
            expect(viewer.awaitingConfirmation).toBe(true);
        });

        it('ignore une saisie manuelle vide', () => {
            const viewer = createViewer({ manualKey: '   ' });

            viewer.submitManualKey();

            expect(viewer.keyMaterial).toBeNull();
            expect(viewer.awaitingConfirmation).toBe(false);
        });

        it('remonte une erreur sur un fragment mal formé', () => {
            const viewer = createViewer({ manualKey: 'Zbadfragment' });

            viewer.submitManualKey();

            expect(viewer.error).toBe('Format de fragment invalide');
            expect(viewer.keyMaterial).toBeNull();
        });
    });

    describe('déchiffrement', () => {
        function textViewer(overrides = {}) {
            return createViewer({
                type: 'text',
                ciphertext: 'ct',
                cipherMeta: { iv: 'iv' },
                keyMaterial: KEY_MATERIAL,
                ...overrides,
            });
        }

        it('déchiffre puis confirme la lecture', async () => {
            vi.spyOn(window.SecretCrypto, 'decryptSecret').mockResolvedValue('le secret');
            const viewer = textViewer();

            await viewer.decrypt();

            expect(viewer.plaintext).toBe('le secret');
            expect(viewer.decrypted).toBe(true);
            expect(viewer.error).toBeNull();
            expect(global.fetch).toHaveBeenCalledWith('/api/secrets/tok_abcdef/read', expect.anything());
        });

        it('affiche une erreur de déchiffrement quand la clé est mauvaise', async () => {
            const failure = new Error('bad');
            failure.name = 'OperationError';
            vi.spyOn(window.SecretCrypto, 'decryptSecret').mockRejectedValue(failure);
            const viewer = textViewer();

            await viewer.decrypt();

            expect(viewer.error).toBe(TRANSLATIONS.crypto_decryption_failed);
            expect(viewer.decrypted).toBe(false);
            expect(viewer.isDecrypting).toBe(false);
        });

        it('distingue une passphrase incorrecte', async () => {
            const failure = new Error('bad');
            failure.name = 'OperationError';
            vi.spyOn(window.SecretCrypto, 'decryptSecret').mockRejectedValue(failure);
            const viewer = textViewer({
                needsPassphrase: true,
                passphrase: 'correct horse battery',
                cipherMeta: { iv: 'iv', salt: 'salt', iv2: 'iv2', has_passphrase: true },
            });

            await viewer.decrypt();

            expect(viewer.error).toBe(TRANSLATIONS.crypto_passphrase_incorrect);
        });

        it('trime la passphrase avant de la passer à decryptSecret', async () => {
            const decryptSecret = vi.spyOn(window.SecretCrypto, 'decryptSecret').mockResolvedValue('le secret');
            const viewer = textViewer({
                needsPassphrase: true,
                passphrase: '   correct horse battery   ',
                cipherMeta: { iv: 'iv', salt: 'salt', iv2: 'iv2', has_passphrase: true },
            });

            await viewer.decrypt();

            expect(decryptSecret).toHaveBeenCalledWith('ct', 'iv', KEY_MATERIAL, 'salt', 'iv2', 'correct horse battery', 1);
        });

        it('refuse une passphrase qui ne contient que des espaces', async () => {
            const decryptSecret = vi.spyOn(window.SecretCrypto, 'decryptSecret');
            const viewer = textViewer({
                needsPassphrase: true,
                passphrase: '      ',
                cipherMeta: { iv: 'iv', salt: 'salt', iv2: 'iv2', has_passphrase: true },
            });

            await viewer.decrypt();

            expect(decryptSecret).not.toHaveBeenCalled();
            expect(viewer.error).toBe(TRANSLATIONS.crypto_passphrase_required);
        });

        it('échoue proprement quand WebCrypto est indisponible', async () => {
            window.SecretCrypto.isCryptoAvailable = () => false;
            const viewer = textViewer();

            await viewer.decrypt();

            expect(viewer.error).toBe(TRANSLATIONS.crypto_not_supported);
            expect(viewer.decrypted).toBe(false);
        });
    });

    describe('téléchargement de fichier', () => {
        const fileBytes = new Uint8Array([1, 2, 3, 4]);

        function fileViewer(overrides = {}) {
            return createViewer({
                type: 'file',
                cipherMeta: { iv: 'iv' },
                keyMaterial: KEY_MATERIAL,
                ...overrides,
            });
        }

        function stubObjectUrls() {
            const createObjectURL = vi.fn(() => 'blob:mock-url');
            const revokeObjectURL = vi.fn();
            URL.createObjectURL = createObjectURL;
            URL.revokeObjectURL = revokeObjectURL;

            return { createObjectURL, revokeObjectURL };
        }

        it('reprend le nom et le type MIME depuis l’en-tête déchiffré', async () => {
            const { createObjectURL, revokeObjectURL } = stubObjectUrls();
            const clicks = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
            const appendChild = vi.spyOn(document.body, 'appendChild');

            global.fetch = vi.fn(async (url) => {
                if (url.endsWith('/download')) {
                    return {
                        ok: true,
                        status: 200,
                        headers: { get: () => null },
                        arrayBuffer: async () => fileBytes.buffer,
                    };
                }

                return jsonResponse({ ok: true });
            });
            vi.spyOn(window.SecretCrypto, 'decryptFile').mockResolvedValue({
                data: fileBytes.buffer,
                filename: 'rapport confidentiel.pdf',
                mime: 'application/pdf',
                size: 4,
            });

            const viewer = fileViewer();

            await viewer.decrypt();

            expect(viewer.error).toBeNull();
            expect(viewer.filename).toBe('rapport confidentiel.pdf');
            expect(viewer.mime).toBe('application/pdf');
            expect(viewer.size).toBe(4);

            expect(createObjectURL).toHaveBeenCalledTimes(1);
            const blob = createObjectURL.mock.calls[0][0];
            expect(blob).toBeInstanceOf(Blob);
            expect(blob.type).toBe('application/pdf');
            expect(revokeObjectURL).toHaveBeenCalledWith('blob:mock-url');

            const anchor = appendChild.mock.calls.map(call => call[0]).find(node => node.tagName === 'A');
            expect(anchor.download).toBe('rapport confidentiel.pdf');
            expect(anchor.getAttribute('href')).toBe('blob:mock-url');
            expect(clicks).toHaveBeenCalledTimes(1);
            expect(document.body.querySelector('a')).toBeNull();
        });

        it('remonte une erreur quand le téléchargement du chiffré échoue', async () => {
            stubObjectUrls();
            global.fetch = vi.fn(async () => jsonResponse({}, { ok: false, status: 404 }));
            const decryptFile = vi.spyOn(window.SecretCrypto, 'decryptFile');
            const viewer = fileViewer();

            await viewer.decrypt();

            expect(decryptFile).not.toHaveBeenCalled();
            expect(viewer.error).toBe(TRANSLATIONS.crypto_file_download_failed);
            expect(viewer.decrypted).toBe(false);
        });

        it('prend en compte l’en-tête X-Previous-Fetches du téléchargement', async () => {
            stubObjectUrls();
            vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
            global.fetch = vi.fn(async (url) => {
                if (url.endsWith('/download')) {
                    return {
                        ok: true,
                        status: 200,
                        headers: { get: (name) => (name === 'X-Previous-Fetches' ? '2' : null) },
                        arrayBuffer: async () => fileBytes.buffer,
                    };
                }

                return jsonResponse({ ok: true });
            });
            vi.spyOn(window.SecretCrypto, 'decryptFile').mockResolvedValue({
                data: fileBytes.buffer,
                filename: 'a.bin',
                mime: 'application/octet-stream',
                size: 4,
            });
            const viewer = fileViewer({ singleUse: true });

            await viewer.decrypt();

            expect(viewer.previousFetches).toBe(2);
            expect(viewer.hasSuspiciousPreviousFetches()).toBe(true);
        });
    });

    describe('chargement du secret', () => {
        it('distingue un secret inexistant d’un secret indisponible selon la présence de la clé', async () => {
            global.fetch = vi.fn(async () => jsonResponse({}, { ok: false, status: 404 }));

            setHash('');
            const withoutKey = createViewer({ token: 'tok_abcdef' });
            await withoutKey.loadSecret();

            expect(withoutKey.loadError).toEqual({ type: 'not_found', message: TRANSLATIONS.secret_not_exist });

            setHash(`#${FRAGMENT}`);
            const withKey = createViewer({ token: 'tok_abcdef' });
            await withKey.loadSecret();

            expect(withKey.loadError).toEqual({ type: 'unavailable', message: TRANSLATIONS.secret_unavailable_generic });
            expect(withKey.isUnavailable()).toBe(true);
        });

        it('exige une confirmation avant la dernière lecture', async () => {
            setHash(`#${FRAGMENT}`);
            global.fetch = vi.fn(async () => jsonResponse({
                type: 'text',
                cipher_meta: { iv: 'iv' },
                ciphertext: 'ct',
                will_be_destroyed: true,
                single_use: true,
                previous_fetches: 0,
            }));
            const decryptSecret = vi.spyOn(window.SecretCrypto, 'decryptSecret').mockResolvedValue('le secret');
            const viewer = createViewer();

            await viewer.init();

            expect(viewer.awaitingConfirmation).toBe(true);
            expect(decryptSecret).not.toHaveBeenCalled();

            await viewer.confirmAndDecrypt();

            expect(viewer.awaitingConfirmation).toBe(false);
            expect(decryptSecret).toHaveBeenCalledTimes(1);
        });

        it('signale une erreur réseau au chargement', async () => {
            global.fetch = vi.fn(async () => {
                throw new Error('offline');
            });
            const viewer = createViewer({ token: 'tok_abcdef' });

            await viewer.loadSecret();

            expect(viewer.loadError).toEqual({ type: 'error', message: TRANSLATIONS.error_connection });
            expect(viewer.isLoading).toBe(false);
            expect(viewer.isGenericError()).toBe(true);
        });
    });
});
