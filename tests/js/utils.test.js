import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { solvePow } from '../../resources/js/pow-solver.js';
import { buildCipherMeta, fetchHeaders, formatFileSize, t } from '../../resources/js/utils.js';
import { resetRing, startRing } from '../../resources/js/utils/poll-ring.js';

afterEach(() => {
    delete window.translations;
    document.head.innerHTML = '';
    document.body.innerHTML = '';
});

describe('t', () => {
    it('returns the key when no translations are exposed', () => {
        expect(t('unit_bytes')).toBe('unit_bytes');
    });

    it('returns the translation when it exists', () => {
        window.translations = { unit_bytes: 'octets' };

        expect(t('unit_bytes')).toBe('octets');
    });

    it('falls back to the key for a missing or empty entry', () => {
        window.translations = { unit_bytes: 'octets', unit_kilobytes: '' };

        expect(t('unknown_key')).toBe('unknown_key');
        expect(t('unit_kilobytes')).toBe('unit_kilobytes');
    });
});

describe('formatFileSize', () => {
    beforeEach(() => {
        window.translations = { unit_bytes: 'o', unit_kilobytes: 'Ko', unit_megabytes: 'Mo' };
    });

    it('returns an empty string for null and undefined', () => {
        expect(formatFileSize(null)).toBe('');
        expect(formatFileSize(undefined)).toBe('');
    });

    it('formats bytes below 1 KiB', () => {
        expect(formatFileSize(0)).toBe('0 o');
        expect(formatFileSize(512)).toBe('512 o');
        expect(formatFileSize(1023)).toBe('1023 o');
    });

    it('formats kilobytes from 1 KiB up to 1 MiB', () => {
        expect(formatFileSize(1024)).toBe('1.0 Ko');
        expect(formatFileSize(1536)).toBe('1.5 Ko');
        expect(formatFileSize(1024 * 1024 - 1)).toBe('1024.0 Ko');
    });

    it('formats megabytes from 1 MiB up', () => {
        expect(formatFileSize(1024 * 1024)).toBe('1.0 Mo');
        expect(formatFileSize(3 * 1024 * 1024 + 512 * 1024)).toBe('3.5 Mo');
    });
});

describe('fetchHeaders', () => {
    it('reads the CSRF token from the meta tag', () => {
        document.head.innerHTML = '<meta name="csrf-token" content="token-abc">';

        expect(fetchHeaders()).toEqual({
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': 'token-abc',
            'Accept': 'application/json',
        });
    });

    it('throws when the meta tag is missing', () => {
        expect(() => fetchHeaders()).toThrow(TypeError);
    });
});

describe('buildCipherMeta', () => {
    it('describes the single-layer payload without a passphrase', () => {
        const meta = buildCipherMeta({ iv: 'IV', version: 1 });

        expect(meta).toEqual({
            alg: 'AES-256-GCM',
            iv: 'IV',
            version: 1,
            has_passphrase: false,
        });
    });

    it('describes the double-layer payload with a passphrase', () => {
        const meta = buildCipherMeta({ iv: 'IV', iv2: 'IV2', salt: 'SALT', version: 1 });

        expect(meta).toEqual({
            alg: 'AES-256-GCM',
            iv: 'IV',
            version: 1,
            has_passphrase: true,
            salt: 'SALT',
            iv2: 'IV2',
            kdf: 'PBKDF2-SHA256-600k',
        });
    });
});

function hexToBytes(hex) {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
        bytes[i / 2] = parseInt(hex.substring(i, i + 2), 16);
    }

    return bytes;
}

async function leadingZeroBits(challengeHex, nonceHex) {
    const challenge = hexToBytes(challengeHex);
    const nonce = hexToBytes(nonceHex);
    const data = new Uint8Array(challenge.length + nonce.length);
    data.set(challenge, 0);
    data.set(nonce, challenge.length);

    const hash = new Uint8Array(await crypto.subtle.digest('SHA-256', data));
    let bits = 0;
    for (const byte of hash) {
        if (byte === 0) {
            bits += 8;
            continue;
        }
        bits += Math.clz32(byte) - 24;
        break;
    }

    return bits;
}

describe('solvePow', () => {
    it('returns a nonce whose digest satisfies the difficulty', async () => {
        const challenge = 'a1b2c3d4e5f60718';
        const nonceHex = await solvePow(challenge, 8);

        expect(nonceHex).toMatch(/^[0-9a-f]{8}$/);
        expect(await leadingZeroBits(challenge, nonceHex)).toBeGreaterThanOrEqual(8);
    });

    it('throws pow_timeout when the difficulty cannot be met in time', async () => {
        await expect(solvePow('a1b2c3d4e5f60718', 40, { timeout: 1 })).rejects.toThrow('pow_timeout');
    });
});

const CIRCUMFERENCE = 2 * Math.PI * 12;

function mountRing() {
    document.body.innerHTML = `
        <svg id="pollRing" data-title-template="Actualisation dans :seconds s">
            <title id="pollRingTitle"></title>
            <circle id="pollRingProgress"></circle>
        </svg>
    `;

    return {
        ring: document.getElementById('pollRingProgress'),
        title: document.getElementById('pollRingTitle'),
    };
}

describe('poll-ring', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        resetRing();
        vi.useRealTimers();
    });

    it('draws a full ring and the remaining seconds right away', () => {
        const { ring, title } = mountRing();

        startRing(10000);

        expect(parseFloat(ring.style.strokeDashoffset)).toBeCloseTo(CIRCUMFERENCE, 5);
        expect(title.textContent).toBe('Actualisation dans 10 s');
    });

    it('shrinks the offset and counts down as time passes', () => {
        const { ring, title } = mountRing();

        startRing(10000);
        vi.advanceTimersByTime(5000);

        expect(parseFloat(ring.style.strokeDashoffset)).toBeCloseTo(CIRCUMFERENCE * 0.5, 5);
        expect(title.textContent).toBe('Actualisation dans 5 s');

        vi.advanceTimersByTime(6000);
        expect(parseFloat(ring.style.strokeDashoffset)).toBeCloseTo(0, 5);
        expect(title.textContent).toBe('Actualisation dans 0 s');
    });

    it('restores the full ring and stops ticking on reset', () => {
        const { ring, title } = mountRing();

        startRing(10000);
        vi.advanceTimersByTime(5000);
        resetRing();

        expect(parseFloat(ring.style.strokeDashoffset)).toBeCloseTo(CIRCUMFERENCE, 5);
        expect(title.textContent).toBe('');

        vi.advanceTimersByTime(5000);
        expect(parseFloat(ring.style.strokeDashoffset)).toBeCloseTo(CIRCUMFERENCE, 5);
        expect(title.textContent).toBe('');
    });

    it('is a no-op without the ring element', () => {
        document.body.innerHTML = '';

        expect(() => startRing(10000)).not.toThrow();
        expect(() => resetRing()).not.toThrow();
    });

    it('replaces a running timer instead of stacking one', () => {
        const { title } = mountRing();

        startRing(10000);
        vi.advanceTimersByTime(4000);
        startRing(10000);

        expect(title.textContent).toBe('Actualisation dans 10 s');
        expect(vi.getTimerCount()).toBe(1);
    });
});
