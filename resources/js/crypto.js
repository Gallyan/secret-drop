/**
 * Secret Drop - Client-side encryption module
 *
 * Uses Web Crypto API for zero-knowledge encryption:
 * - AES-256-GCM for symmetric encryption
 * - PBKDF2 for optional passphrase key derivation
 * - Base64URL encoding for URL-safe transport
 */

const CRYPTO_VERSION = 1;
const AES_KEY_LENGTH = 256;
const IV_LENGTH = 12; // 96 bits for AES-GCM
const SALT_LENGTH = 16; // 128 bits for PBKDF2
const PBKDF2_ITERATIONS = 200000;

/**
 * Encode bytes to Base64URL (URL-safe, no padding)
 * @param {Uint8Array} bytes
 * @returns {string}
 */
export function bytesToBase64Url(bytes) {
    const base64 = btoa(String.fromCharCode(...bytes));
    return base64
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

/**
 * Decode Base64URL to bytes
 * @param {string} base64url
 * @returns {Uint8Array}
 */
export function base64UrlToBytes(base64url) {
    const base64 = base64url
        .replace(/-/g, '+')
        .replace(/_/g, '/');
    const padding = (4 - (base64.length % 4)) % 4;
    const padded = base64 + '='.repeat(padding);
    const binary = atob(padded);
    return Uint8Array.from(binary, c => c.charCodeAt(0));
}

/**
 * Generate cryptographically secure random bytes
 * @param {number} length
 * @returns {Uint8Array}
 */
function generateRandomBytes(length) {
    return crypto.getRandomValues(new Uint8Array(length));
}

/**
 * Generate a new AES-256-GCM key
 * @returns {Promise<CryptoKey>}
 */
async function generateKey() {
    return crypto.subtle.generateKey(
        { name: 'AES-GCM', length: AES_KEY_LENGTH },
        true, // extractable
        ['encrypt', 'decrypt']
    );
}

/**
 * Derive an AES key from a passphrase using PBKDF2
 * @param {string} passphrase
 * @param {Uint8Array} salt
 * @returns {Promise<CryptoKey>}
 */
async function deriveKeyFromPassphrase(passphrase, salt) {
    const encoder = new TextEncoder();
    const keyMaterial = await crypto.subtle.importKey(
        'raw',
        encoder.encode(passphrase),
        'PBKDF2',
        false,
        ['deriveKey']
    );

    return crypto.subtle.deriveKey(
        {
            name: 'PBKDF2',
            salt,
            iterations: PBKDF2_ITERATIONS,
            hash: 'SHA-256'
        },
        keyMaterial,
        { name: 'AES-GCM', length: AES_KEY_LENGTH },
        true, // extractable
        ['encrypt', 'decrypt']
    );
}

/**
 * Export a CryptoKey to raw bytes
 * @param {CryptoKey} key
 * @returns {Promise<Uint8Array>}
 */
async function exportKey(key) {
    const rawKey = await crypto.subtle.exportKey('raw', key);
    return new Uint8Array(rawKey);
}

/**
 * Import raw bytes as an AES-GCM key
 * @param {Uint8Array} rawKey
 * @returns {Promise<CryptoKey>}
 */
async function importKey(rawKey) {
    return crypto.subtle.importKey(
        'raw',
        rawKey,
        { name: 'AES-GCM', length: AES_KEY_LENGTH },
        false,
        ['decrypt']
    );
}

/**
 * Encrypt plaintext using AES-256-GCM
 * @param {string} plaintext - The secret to encrypt
 * @param {string|null} passphrase - Optional passphrase for additional protection
 * @returns {Promise<{ciphertext: string, iv: string, salt: string|null, keyMaterial: string, version: number}>}
 */
export async function encryptSecret(plaintext, passphrase = null) {
    const encoder = new TextEncoder();
    const plaintextBytes = encoder.encode(plaintext);
    const iv = generateRandomBytes(IV_LENGTH);

    let key;
    let salt = null;
    let keyMaterial;

    if (passphrase && passphrase.trim()) {
        // Derive key from passphrase
        salt = generateRandomBytes(SALT_LENGTH);
        key = await deriveKeyFromPassphrase(passphrase, salt);
        // Key material is empty when using passphrase (key is derived from passphrase + salt)
        keyMaterial = '';
    } else {
        // Generate random key
        key = await generateKey();
        const rawKey = await exportKey(key);
        keyMaterial = bytesToBase64Url(rawKey);
    }

    const ciphertextBytes = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        key,
        plaintextBytes
    );

    return {
        ciphertext: bytesToBase64Url(new Uint8Array(ciphertextBytes)),
        iv: bytesToBase64Url(iv),
        salt: salt ? bytesToBase64Url(salt) : null,
        keyMaterial,
        version: CRYPTO_VERSION
    };
}

/**
 * Decrypt ciphertext using AES-256-GCM
 * @param {string} ciphertext - Base64URL encoded ciphertext
 * @param {string} iv - Base64URL encoded IV
 * @param {string} keyMaterial - Base64URL encoded key (empty if passphrase was used)
 * @param {string|null} salt - Base64URL encoded salt (if passphrase was used)
 * @param {string|null} passphrase - Passphrase (if used during encryption)
 * @param {number} version - Crypto version
 * @returns {Promise<string>}
 */
export async function decryptSecret(ciphertext, iv, keyMaterial, salt = null, passphrase = null, version = CRYPTO_VERSION) {
    if (version !== CRYPTO_VERSION) {
        throw new Error(`Unsupported crypto version: ${version}`);
    }

    const ciphertextBytes = base64UrlToBytes(ciphertext);
    const ivBytes = base64UrlToBytes(iv);

    let key;
    if (salt && passphrase) {
        // Derive key from passphrase
        const saltBytes = base64UrlToBytes(salt);
        key = await deriveKeyFromPassphrase(passphrase, saltBytes);
    } else if (keyMaterial) {
        // Import the raw key
        const rawKey = base64UrlToBytes(keyMaterial);
        key = await importKey(rawKey);
    } else {
        throw new Error('Either keyMaterial or passphrase+salt must be provided');
    }

    const plaintextBytes = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: ivBytes },
        key,
        ciphertextBytes
    );

    const decoder = new TextDecoder();
    return decoder.decode(plaintextBytes);
}

/**
 * Build the URL fragment containing key material
 * @param {string} keyMaterial - Base64URL encoded key
 * @param {boolean} hasPassphrase - Whether a passphrase was used
 * @param {number} version - Crypto version
 * @returns {string}
 */
export function buildKeyFragment(keyMaterial, hasPassphrase, version = CRYPTO_VERSION) {
    const params = new URLSearchParams();
    params.set('v', version.toString());

    if (hasPassphrase) {
        params.set('p', '1'); // indicates passphrase is required
    } else {
        params.set('k', keyMaterial);
    }

    return params.toString();
}

/**
 * Parse key material from URL fragment
 * @param {string} fragment - URL fragment (without #)
 * @returns {{keyMaterial: string|null, hasPassphrase: boolean, version: number}}
 */
export function parseKeyFragment(fragment) {
    const params = new URLSearchParams(fragment);

    return {
        keyMaterial: params.get('k') || null,
        hasPassphrase: params.get('p') === '1',
        version: parseInt(params.get('v') || '1', 10)
    };
}

/**
 * Check if Web Crypto API is available
 * @returns {boolean}
 */
export function isCryptoAvailable() {
    return !!(crypto && crypto.subtle && crypto.getRandomValues);
}
