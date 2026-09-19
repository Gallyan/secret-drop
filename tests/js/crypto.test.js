import { afterEach, describe, expect, it, vi } from 'vitest';

import {
    base64UrlToBytes,
    buildKeyFragment,
    bytesToBase64Url,
    decryptFile,
    decryptSecret,
    encryptFile,
    encryptSecret,
    isCryptoAvailable,
    parseKeyFragment,
} from '../../resources/js/crypto.js';

const IV_LENGTH = 12;
const SALT_LENGTH = 16;
const KEY_FRAGMENT_LENGTH = 43;

/**
 * Flip one bit of a Base64URL payload and re-encode it.
 */
function tamperBase64Url(value, index = 0) {
    const bytes = base64UrlToBytes(value);
    bytes[index] ^= 0x01;

    return bytesToBase64Url(bytes);
}

/**
 * Encrypt arbitrary bytes with a fresh AES-GCM key, mimicking the single-layer
 * output of encryptFile so we can feed hand-crafted payloads to decryptFile.
 */
async function encryptRawPayload(payloadBytes) {
    const key = await crypto.subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt', 'decrypt']);
    const rawKey = new Uint8Array(await crypto.subtle.exportKey('raw', key));
    const iv = crypto.getRandomValues(new Uint8Array(IV_LENGTH));
    const encryptedData = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, payloadBytes);

    return { encryptedData, iv: bytesToBase64Url(iv), keyMaterial: bytesToBase64Url(rawKey) };
}

async function blobToArrayBuffer(blob) {
    return blob.arrayBuffer();
}

describe('base64url helpers', () => {
    it('round-trips arbitrary bytes', () => {
        const bytes = new Uint8Array([0, 1, 62, 63, 127, 128, 254, 255]);
        const encoded = bytesToBase64Url(bytes);

        expect(encoded).not.toMatch(/[+/=]/);
        expect(Array.from(base64UrlToBytes(encoded))).toEqual(Array.from(bytes));
    });

    it('encodes an empty array as an empty string', () => {
        expect(bytesToBase64Url(new Uint8Array(0))).toBe('');
        expect(base64UrlToBytes('').length).toBe(0);
    });

    it('produces URL-safe characters for bytes that map to + and /', () => {
        const encoded = bytesToBase64Url(new Uint8Array([251, 255, 190]));

        expect(encoded).toBe('-_--');
        expect(Array.from(base64UrlToBytes(encoded))).toEqual([251, 255, 190]);
    });
});

describe('encryptSecret / decryptSecret round-trip', () => {
    it('round-trips plain ASCII text', async () => {
        const encrypted = await encryptSecret('hello world');

        expect(encrypted.version).toBe(1);
        expect(encrypted.salt).toBeNull();
        expect(encrypted.iv2).toBeNull();

        const plaintext = await decryptSecret(encrypted.ciphertext, encrypted.iv, encrypted.keyMaterial);

        expect(plaintext).toBe('hello world');
    });

    it('round-trips unicode, emoji and combining characters', async () => {
        const secret = 'Élan vital — 日本語 🔐 é ✓ ‰';
        const encrypted = await encryptSecret(secret);

        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, encrypted.keyMaterial)).resolves.toBe(secret);
    });

    it('round-trips an empty string', async () => {
        const encrypted = await encryptSecret('');

        expect(encrypted.ciphertext.length).toBeGreaterThan(0);
        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, encrypted.keyMaterial)).resolves.toBe('');
    });

    it('round-trips a whitespace-only string', async () => {
        const encrypted = await encryptSecret('   \n\t ');

        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, encrypted.keyMaterial)).resolves.toBe('   \n\t ');
    });

    it('round-trips a large-ish payload', async () => {
        const secret = 'a'.repeat(120000);
        const encrypted = await encryptSecret(secret);
        const plaintext = await decryptSecret(encrypted.ciphertext, encrypted.iv, encrypted.keyMaterial);

        expect(plaintext.length).toBe(120000);
        expect(plaintext).toBe(secret);
    });

    it('produces an IV of 12 bytes and a 32-byte key', async () => {
        const encrypted = await encryptSecret('sized');

        expect(base64UrlToBytes(encrypted.iv).length).toBe(IV_LENGTH);
        expect(base64UrlToBytes(encrypted.keyMaterial).length).toBe(32);
        expect(encrypted.keyMaterial.length).toBe(KEY_FRAGMENT_LENGTH);
    });

    it('appends the 16-byte GCM tag to the ciphertext', async () => {
        const encrypted = await encryptSecret('1234567890');

        expect(base64UrlToBytes(encrypted.ciphertext).length).toBe(10 + 16);
    });

    it('rejects an empty key material', async () => {
        const encrypted = await encryptSecret('needs a key');

        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, '')).rejects.toThrow('Key material is required');
        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, null)).rejects.toThrow('Key material is required');
    });

    it('rejects an IV that does not decode to 12 bytes', async () => {
        const encrypted = await encryptSecret('bad iv');
        const shortIv = bytesToBase64Url(new Uint8Array(8));

        await expect(decryptSecret(encrypted.ciphertext, shortIv, encrypted.keyMaterial))
            .rejects.toThrow('Invalid IV length: expected 12, got 8');
    });
});

describe('key fragment', () => {
    it('builds a fragment prefixed with A', async () => {
        const encrypted = await encryptSecret('fragment');
        const fragment = buildKeyFragment(encrypted.keyMaterial);

        expect(fragment.charAt(0)).toBe('A');
        expect(fragment.length).toBe(1 + KEY_FRAGMENT_LENGTH);
        expect(fragment.slice(1)).toBe(encrypted.keyMaterial);
    });

    it('refuses to build a fragment without key material', () => {
        expect(() => buildKeyFragment('')).toThrow('Key material is required');
        expect(() => buildKeyFragment(null)).toThrow('Key material is required');
        expect(() => buildKeyFragment(undefined)).toThrow('Key material is required');
    });

    it('parses a fragment built by buildKeyFragment', async () => {
        const encrypted = await encryptSecret('parse me');
        const parsed = parseKeyFragment(buildKeyFragment(encrypted.keyMaterial));

        expect(parsed).toEqual({ keyMaterial: encrypted.keyMaterial, version: 1 });
    });

    it('decrypts using the key material recovered from a fragment', async () => {
        const encrypted = await encryptSecret('fragment round-trip');
        const { keyMaterial } = parseKeyFragment(buildKeyFragment(encrypted.keyMaterial));

        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, keyMaterial))
            .resolves.toBe('fragment round-trip');
    });

    it('rejects an empty or missing fragment', () => {
        expect(() => parseKeyFragment('')).toThrow('Fragment de clé manquant');
        expect(() => parseKeyFragment(null)).toThrow('Fragment de clé manquant');
        expect(() => parseKeyFragment(undefined)).toThrow('Fragment de clé manquant');
    });

    it('rejects an unknown prefix', () => {
        const key = 'a'.repeat(KEY_FRAGMENT_LENGTH);

        expect(() => parseKeyFragment(`B${key}`)).toThrow('Format de fragment invalide');
        expect(() => parseKeyFragment(`a${key}`)).toThrow('Format de fragment invalide');
        expect(() => parseKeyFragment(`#A${key}`)).toThrow('Format de fragment invalide');
    });

    it('rejects a key of the wrong length', () => {
        expect(() => parseKeyFragment('A')).toThrow('Clé manquante ou invalide dans le fragment');
        expect(() => parseKeyFragment(`A${'a'.repeat(42)}`)).toThrow('Clé manquante ou invalide dans le fragment');
        expect(() => parseKeyFragment(`A${'a'.repeat(44)}`)).toThrow('Clé manquante ou invalide dans le fragment');
    });
});

describe('passphrase layer', () => {
    it('adds a salt and a second IV when a passphrase is given', async () => {
        const encrypted = await encryptSecret('double layer', 'correct horse');

        expect(encrypted.salt).toBeTypeOf('string');
        expect(encrypted.iv2).toBeTypeOf('string');
        expect(base64UrlToBytes(encrypted.salt).length).toBe(SALT_LENGTH);
        expect(base64UrlToBytes(encrypted.iv2).length).toBe(IV_LENGTH);
        expect(encrypted.iv2).not.toBe(encrypted.iv);
    });

    it('adds a second GCM tag to the ciphertext', async () => {
        const withoutPassphrase = await encryptSecret('1234567890');
        const withPassphrase = await encryptSecret('1234567890', 'pass');

        expect(base64UrlToBytes(withPassphrase.ciphertext).length)
            .toBe(base64UrlToBytes(withoutPassphrase.ciphertext).length + 16);
    });

    it('decrypts with the right passphrase', async () => {
        const encrypted = await encryptSecret('double layer', 'correct horse');
        const plaintext = await decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            encrypted.salt,
            encrypted.iv2,
            'correct horse'
        );

        expect(plaintext).toBe('double layer');
    });

    it('rejects a wrong passphrase', async () => {
        const encrypted = await encryptSecret('double layer', 'correct horse');

        await expect(decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            encrypted.salt,
            encrypted.iv2,
            'wrong horse'
        )).rejects.toThrow();
    });

    it('rejects when the passphrase is omitted entirely', async () => {
        const encrypted = await encryptSecret('double layer', 'correct horse');

        await expect(decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            encrypted.salt,
            encrypted.iv2,
            null
        )).rejects.toThrow();
    });

    it('rejects when salt and iv2 are dropped but the passphrase is supplied', async () => {
        const encrypted = await encryptSecret('double layer', 'correct horse');

        await expect(decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            null,
            null,
            'correct horse'
        )).rejects.toThrow();
    });

    it('rejects a salt of the wrong length', async () => {
        const encrypted = await encryptSecret('double layer', 'correct horse');
        const shortSalt = bytesToBase64Url(new Uint8Array(8));

        await expect(decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            shortSalt,
            encrypted.iv2,
            'correct horse'
        )).rejects.toThrow('Invalid salt length: expected 16, got 8');
    });

    it('treats a whitespace-only passphrase as no passphrase at all', async () => {
        const encrypted = await encryptSecret('no real passphrase', '   ');

        expect(encrypted.salt).toBeNull();
        expect(encrypted.iv2).toBeNull();
        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, encrypted.keyMaterial))
            .resolves.toBe('no real passphrase');
    });

    it('derives the key from the untrimmed passphrase, so callers must trim first', async () => {
        const encrypted = await encryptSecret('spaced out', '  hunter2  ');

        expect(encrypted.salt).toBeTypeOf('string');

        await expect(decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            encrypted.salt,
            encrypted.iv2,
            'hunter2'
        )).rejects.toThrow();

        await expect(decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            encrypted.salt,
            encrypted.iv2,
            '  hunter2  '
        )).resolves.toBe('spaced out');
    });

    it('round-trips the trimmed passphrase the components actually pass in', async () => {
        const typed = '  correct horse  ';
        const trimmed = typed.trim();
        const encrypted = await encryptSecret('component contract', trimmed);

        await expect(decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            encrypted.salt,
            encrypted.iv2,
            trimmed
        )).resolves.toBe('component contract');
    });
});

describe('tampering and authentication', () => {
    it('rejects a flipped byte in the ciphertext', async () => {
        const encrypted = await encryptSecret('authenticated payload');
        const tampered = tamperBase64Url(encrypted.ciphertext, 0);

        expect(tampered).not.toBe(encrypted.ciphertext);
        await expect(decryptSecret(tampered, encrypted.iv, encrypted.keyMaterial)).rejects.toThrow();
    });

    it('rejects a flipped byte in the GCM tag', async () => {
        const encrypted = await encryptSecret('authenticated payload');
        const bytes = base64UrlToBytes(encrypted.ciphertext);
        const tampered = tamperBase64Url(encrypted.ciphertext, bytes.length - 1);

        await expect(decryptSecret(tampered, encrypted.iv, encrypted.keyMaterial)).rejects.toThrow();
    });

    it('rejects a truncated ciphertext', async () => {
        const encrypted = await encryptSecret('authenticated payload');
        const bytes = base64UrlToBytes(encrypted.ciphertext);
        const truncated = bytesToBase64Url(bytes.slice(0, bytes.length - 4));

        await expect(decryptSecret(truncated, encrypted.iv, encrypted.keyMaterial)).rejects.toThrow();
    });

    it('rejects a wrong key', async () => {
        const encrypted = await encryptSecret('authenticated payload');
        const other = await encryptSecret('another secret');

        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, other.keyMaterial)).rejects.toThrow();
    });

    it('rejects a key material with a flipped bit', async () => {
        const encrypted = await encryptSecret('authenticated payload');
        const tamperedKey = tamperBase64Url(encrypted.keyMaterial, 5);

        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, tamperedKey)).rejects.toThrow();
    });

    it('rejects a swapped IV', async () => {
        const encrypted = await encryptSecret('authenticated payload');
        const other = await encryptSecret('another secret');

        await expect(decryptSecret(encrypted.ciphertext, other.iv, encrypted.keyMaterial)).rejects.toThrow();
    });

    it('rejects a swapped iv2 on the passphrase layer', async () => {
        const encrypted = await encryptSecret('authenticated payload', 'pass');
        const other = await encryptSecret('another secret', 'pass');

        await expect(decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            encrypted.salt,
            other.iv2,
            'pass'
        )).rejects.toThrow();
    });

    it('rejects a swapped salt on the passphrase layer', async () => {
        const encrypted = await encryptSecret('authenticated payload', 'pass');
        const other = await encryptSecret('another secret', 'pass');

        await expect(decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            other.salt,
            encrypted.iv2,
            'pass'
        )).rejects.toThrow();
    });
});

describe('randomness', () => {
    it('never reuses an IV, a key or a ciphertext for the same plaintext', async () => {
        const runs = 20;
        const ivs = new Set();
        const keys = new Set();
        const ciphertexts = new Set();

        for (let i = 0; i < runs; i++) {
            const encrypted = await encryptSecret('identical plaintext');
            ivs.add(encrypted.iv);
            keys.add(encrypted.keyMaterial);
            ciphertexts.add(encrypted.ciphertext);
        }

        expect(ivs.size).toBe(runs);
        expect(keys.size).toBe(runs);
        expect(ciphertexts.size).toBe(runs);
    });

    it('generates a distinct salt and iv2 per passphrase encryption', async () => {
        const first = await encryptSecret('same', 'same passphrase');
        const second = await encryptSecret('same', 'same passphrase');

        expect(first.salt).not.toBe(second.salt);
        expect(first.iv2).not.toBe(second.iv2);
        expect(first.ciphertext).not.toBe(second.ciphertext);
    });
});

describe('crypto versions', () => {
    it('stamps version 1 on every payload', async () => {
        const text = await encryptSecret('versioned');
        const file = await encryptFile(new File([new Uint8Array([1, 2, 3])], 'v.txt', { type: 'text/plain' }));

        expect(text.version).toBe(1);
        expect(file.version).toBe(1);
    });

    it('decrypts by default with the current version', async () => {
        const encrypted = await encryptSecret('versioned');

        await expect(decryptSecret(
            encrypted.ciphertext,
            encrypted.iv,
            encrypted.keyMaterial,
            null,
            null,
            null,
            encrypted.version
        )).resolves.toBe('versioned');
    });

    it('rejects an unsupported version in decryptSecret', async () => {
        const encrypted = await encryptSecret('versioned');

        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, encrypted.keyMaterial, null, null, null, 2))
            .rejects.toThrow('Unsupported crypto version: 2');
        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, encrypted.keyMaterial, null, null, null, 0))
            .rejects.toThrow('Unsupported crypto version: 0');
        await expect(decryptSecret(encrypted.ciphertext, encrypted.iv, encrypted.keyMaterial, null, null, null, '1'))
            .rejects.toThrow('Unsupported crypto version: 1');
    });

    it('rejects an unsupported version in decryptFile', async () => {
        const file = new File([new Uint8Array([1, 2, 3])], 'v.txt', { type: 'text/plain' });
        const encrypted = await encryptFile(file);
        const data = await blobToArrayBuffer(encrypted.encryptedBlob);

        await expect(decryptFile(data, encrypted.iv, encrypted.keyMaterial, null, null, null, 2))
            .rejects.toThrow('Unsupported crypto version: 2');
    });

    it('checks the version before the key material', async () => {
        await expect(decryptSecret('', '', '', null, null, null, 99))
            .rejects.toThrow('Unsupported crypto version: 99');
    });
});

describe('encryptFile / decryptFile', () => {
    it('round-trips a small file and preserves its metadata', async () => {
        const content = new Uint8Array([0, 1, 2, 3, 250, 251, 252, 253, 254, 255]);
        const file = new File([content], 'rapport final.txt', { type: 'text/plain' });
        const encrypted = await encryptFile(file);

        expect(encrypted.encryptedBlob).toBeInstanceOf(Blob);
        expect(encrypted.encryptedBlob.type).toBe('application/octet-stream');
        expect(encrypted.salt).toBeNull();
        expect(encrypted.iv2).toBeNull();

        const decrypted = await decryptFile(
            await blobToArrayBuffer(encrypted.encryptedBlob),
            encrypted.iv,
            encrypted.keyMaterial
        );

        expect(decrypted.filename).toBe('rapport final.txt');
        expect(decrypted.mime).toBe('text/plain');
        expect(decrypted.size).toBe(content.length);
        expect(Array.from(new Uint8Array(decrypted.data))).toEqual(Array.from(content));
    });

    it('preserves a unicode filename', async () => {
        const file = new File([new Uint8Array([42])], 'été — résumé 🔐.pdf', { type: 'application/pdf' });
        const encrypted = await encryptFile(file);
        const decrypted = await decryptFile(
            await blobToArrayBuffer(encrypted.encryptedBlob),
            encrypted.iv,
            encrypted.keyMaterial
        );

        expect(decrypted.filename).toBe('été — résumé 🔐.pdf');
        expect(decrypted.mime).toBe('application/pdf');
    });

    it('falls back to application/octet-stream when the file has no type', async () => {
        const file = new File([new Uint8Array([7])], 'blob.bin');
        const encrypted = await encryptFile(file);
        const decrypted = await decryptFile(
            await blobToArrayBuffer(encrypted.encryptedBlob),
            encrypted.iv,
            encrypted.keyMaterial
        );

        expect(decrypted.mime).toBe('application/octet-stream');
    });

    it('round-trips an empty file', async () => {
        const file = new File([new Uint8Array(0)], 'empty.txt', { type: 'text/plain' });
        const encrypted = await encryptFile(file);
        const decrypted = await decryptFile(
            await blobToArrayBuffer(encrypted.encryptedBlob),
            encrypted.iv,
            encrypted.keyMaterial
        );

        expect(decrypted.size).toBe(0);
        expect(decrypted.data.byteLength).toBe(0);
        expect(decrypted.filename).toBe('empty.txt');
    });

    it('round-trips a file through the passphrase layer', async () => {
        const content = new Uint8Array([9, 8, 7, 6, 5]);
        const file = new File([content], 'secret.bin', { type: 'application/octet-stream' });
        const encrypted = await encryptFile(file, 'file passphrase');

        expect(base64UrlToBytes(encrypted.salt).length).toBe(SALT_LENGTH);
        expect(base64UrlToBytes(encrypted.iv2).length).toBe(IV_LENGTH);

        const decrypted = await decryptFile(
            await blobToArrayBuffer(encrypted.encryptedBlob),
            encrypted.iv,
            encrypted.keyMaterial,
            encrypted.salt,
            encrypted.iv2,
            'file passphrase'
        );

        expect(Array.from(new Uint8Array(decrypted.data))).toEqual(Array.from(content));
        expect(decrypted.filename).toBe('secret.bin');
    });

    it('rejects a wrong passphrase on a file', async () => {
        const file = new File([new Uint8Array([1])], 'secret.bin', { type: 'application/octet-stream' });
        const encrypted = await encryptFile(file, 'file passphrase');

        await expect(decryptFile(
            await blobToArrayBuffer(encrypted.encryptedBlob),
            encrypted.iv,
            encrypted.keyMaterial,
            encrypted.salt,
            encrypted.iv2,
            'other passphrase'
        )).rejects.toThrow();
    });

    it('rejects a file without key material', async () => {
        const file = new File([new Uint8Array([1])], 'secret.bin');
        const encrypted = await encryptFile(file);

        await expect(decryptFile(await blobToArrayBuffer(encrypted.encryptedBlob), encrypted.iv, ''))
            .rejects.toThrow('Key material is required');
    });

    it('rejects a tampered encrypted file', async () => {
        const file = new File([new Uint8Array([1, 2, 3, 4])], 'secret.bin');
        const encrypted = await encryptFile(file);
        const bytes = new Uint8Array(await blobToArrayBuffer(encrypted.encryptedBlob));
        bytes[0] ^= 0x01;

        await expect(decryptFile(bytes.buffer, encrypted.iv, encrypted.keyMaterial)).rejects.toThrow();
    });

    it('rejects a wrong key on a file', async () => {
        const file = new File([new Uint8Array([1, 2, 3, 4])], 'secret.bin');
        const encrypted = await encryptFile(file);
        const other = await encryptFile(new File([new Uint8Array([5])], 'other.bin'));

        await expect(decryptFile(
            await blobToArrayBuffer(encrypted.encryptedBlob),
            encrypted.iv,
            other.keyMaterial
        )).rejects.toThrow();
    });

    it('keeps the ciphertext larger than the plaintext because of the metadata header', async () => {
        const content = new Uint8Array(64);
        const file = new File([content], 'a.txt', { type: 'text/plain' });
        const encrypted = await encryptFile(file);
        const metaJson = JSON.stringify({ filename: 'a.txt', mime: 'text/plain', size: 64 });

        expect(encrypted.encryptedBlob.size).toBe(4 + metaJson.length + content.length + 16);
    });
});

describe('file metadata header', () => {
    it('rejects a payload shorter than the 4-byte header', async () => {
        const { encryptedData, iv, keyMaterial } = await encryptRawPayload(new Uint8Array([1, 2]));

        await expect(decryptFile(encryptedData, iv, keyMaterial))
            .rejects.toThrow('Decrypted data too short for metadata header');
    });

    it('rejects an empty payload', async () => {
        const { encryptedData, iv, keyMaterial } = await encryptRawPayload(new Uint8Array(0));

        await expect(decryptFile(encryptedData, iv, keyMaterial))
            .rejects.toThrow('Decrypted data too short for metadata header');
    });

    it('rejects a metadata length larger than the payload', async () => {
        const payload = new Uint8Array([0, 0, 0, 255, 1, 2, 3]);
        const { encryptedData, iv, keyMaterial } = await encryptRawPayload(payload);

        await expect(decryptFile(encryptedData, iv, keyMaterial)).rejects.toThrow('Invalid metadata length');
    });

    it('rejects a truncated metadata JSON', async () => {
        const meta = new TextEncoder().encode('{"filename":"a.txt","mim');
        const payload = new Uint8Array(4 + meta.length);
        new DataView(payload.buffer).setUint32(0, meta.length, false);
        payload.set(meta, 4);

        const { encryptedData, iv, keyMaterial } = await encryptRawPayload(payload);

        await expect(decryptFile(encryptedData, iv, keyMaterial)).rejects.toThrow();
    });

    it('reads the header as big-endian', async () => {
        const meta = new TextEncoder().encode(JSON.stringify({ filename: 'be.txt', mime: 'text/plain', size: 2 }));
        const content = new Uint8Array([65, 66]);
        const payload = new Uint8Array(4 + meta.length + content.length);
        new DataView(payload.buffer).setUint32(0, meta.length, false);
        payload.set(meta, 4);
        payload.set(content, 4 + meta.length);

        const { encryptedData, iv, keyMaterial } = await encryptRawPayload(payload);
        const decrypted = await decryptFile(encryptedData, iv, keyMaterial);

        expect(decrypted.filename).toBe('be.txt');
        expect(decrypted.size).toBe(2);
        expect(Array.from(new Uint8Array(decrypted.data))).toEqual([65, 66]);
    });
});

describe('isCryptoAvailable', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('reports the Web Crypto API as available in this environment', () => {
        expect(isCryptoAvailable()).toBe(true);
    });

    it('reports it as unavailable when subtle is missing', () => {
        vi.stubGlobal('crypto', { getRandomValues: () => {} });

        expect(isCryptoAvailable()).toBe(false);
    });

    it('reports it as unavailable when getRandomValues is missing', () => {
        vi.stubGlobal('crypto', { subtle: {} });

        expect(isCryptoAvailable()).toBe(false);
    });
});
