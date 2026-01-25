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
const PBKDF2_ITERATIONS = 600000;

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
 * If passphrase is provided, applies double encryption:
 * 1. First layer: random 256-bit key (transmitted in URL fragment)
 * 2. Second layer: key derived from passphrase (shared separately)
 *
 * @param {string} plaintext - The secret to encrypt
 * @param {string|null} passphrase - Optional passphrase for additional protection
 * @returns {Promise<{ciphertext: string, iv: string, salt: string|null, iv2: string|null, keyMaterial: string, version: number}>}
 */
export async function encryptSecret(plaintext, passphrase = null) {
    const encoder = new TextEncoder();
    const plaintextBytes = encoder.encode(plaintext);

    // Always generate a random key (256 bits)
    const randomKey = await generateKey();
    const rawKey = await exportKey(randomKey);
    const keyMaterial = bytesToBase64Url(rawKey);

    // First encryption layer with random key
    const iv = generateRandomBytes(IV_LENGTH);
    let ciphertextBytes = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        randomKey,
        plaintextBytes
    );

    let salt = null;
    let iv2 = null;

    // Second encryption layer with passphrase-derived key
    if (passphrase && passphrase.trim()) {
        salt = generateRandomBytes(SALT_LENGTH);
        iv2 = generateRandomBytes(IV_LENGTH);
        const passphraseKey = await deriveKeyFromPassphrase(passphrase, salt);

        ciphertextBytes = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv2 },
            passphraseKey,
            ciphertextBytes
        );
    }

    return {
        ciphertext: bytesToBase64Url(new Uint8Array(ciphertextBytes)),
        iv: bytesToBase64Url(iv),
        salt: salt ? bytesToBase64Url(salt) : null,
        iv2: iv2 ? bytesToBase64Url(iv2) : null,
        keyMaterial,
        version: CRYPTO_VERSION
    };
}

/**
 * Decrypt ciphertext using AES-256-GCM
 * If passphrase was used, performs double decryption:
 * 1. First: decrypt with passphrase-derived key
 * 2. Then: decrypt with random key from URL fragment
 *
 * @param {string} ciphertext - Base64URL encoded ciphertext
 * @param {string} iv - Base64URL encoded IV (for random key layer)
 * @param {string} keyMaterial - Base64URL encoded random key
 * @param {string|null} salt - Base64URL encoded salt (if passphrase was used)
 * @param {string|null} iv2 - Base64URL encoded IV for passphrase layer
 * @param {string|null} passphrase - Passphrase (if used during encryption)
 * @param {number} version - Crypto version
 * @returns {Promise<string>}
 */
export async function decryptSecret(ciphertext, iv, keyMaterial, salt = null, iv2 = null, passphrase = null, version = CRYPTO_VERSION) {
    if (version !== CRYPTO_VERSION) {
        throw new Error(`Unsupported crypto version: ${version}`);
    }

    if (!keyMaterial) {
        throw new Error('Key material is required');
    }

    let dataBytes = base64UrlToBytes(ciphertext);

    // First: decrypt passphrase layer if present
    if (salt && iv2 && passphrase) {
        const saltBytes = base64UrlToBytes(salt);
        const iv2Bytes = base64UrlToBytes(iv2);
        const passphraseKey = await deriveKeyFromPassphrase(passphrase, saltBytes);

        dataBytes = new Uint8Array(await crypto.subtle.decrypt(
            { name: 'AES-GCM', iv: iv2Bytes },
            passphraseKey,
            dataBytes
        ));
    }

    // Then: decrypt with random key
    const ivBytes = base64UrlToBytes(iv);
    const rawKey = base64UrlToBytes(keyMaterial);
    const randomKey = await importKey(rawKey);

    const plaintextBytes = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: ivBytes },
        randomKey,
        dataBytes
    );

    const decoder = new TextDecoder();
    return decoder.decode(plaintextBytes);
}

/**
 * Fragment format:
 * - 'A' + keyMaterial = version 1, key always present
 *
 * Note: passphrase info comes from server metadata (cipher_meta.has_passphrase),
 * not from the fragment. The key is always in the URL for maximum security
 * (passphrase adds a layer, doesn't replace the key).
 */
const FRAGMENT_PREFIX = 'A';

/**
 * Build the URL fragment containing key material
 * @param {string} keyMaterial - Base64URL encoded key (always required now)
 * @returns {string}
 */
export function buildKeyFragment(keyMaterial) {
    if (!keyMaterial) {
        throw new Error('Key material is required');
    }

    return FRAGMENT_PREFIX + keyMaterial;
}

/**
 * Parse key material from URL fragment
 * @param {string} fragment - URL fragment (without #)
 * @returns {{keyMaterial: string, version: number}}
 */
export function parseKeyFragment(fragment) {
    if (!fragment || fragment.length === 0) {
        throw new Error('Fragment de clé manquant');
    }

    const prefix = fragment.charAt(0);
    const rest = fragment.substring(1);

    if (prefix !== 'A') {
        throw new Error('Format de fragment invalide');
    }

    if (!rest) {
        throw new Error('Clé manquante dans le fragment');
    }

    return { keyMaterial: rest, version: 1 };
}

/**
 * Encrypt a file using AES-256-GCM
 * If passphrase is provided, applies double encryption (same as encryptSecret)
 *
 * @param {File} file - The file to encrypt
 * @param {string|null} passphrase - Optional passphrase for additional protection
 * @returns {Promise<{encryptedBlob: Blob, iv: string, salt: string|null, iv2: string|null, keyMaterial: string, version: number, filename: string, mime: string, size: number}>}
 */
export async function encryptFile(file, passphrase = null) {
    const arrayBuffer = await file.arrayBuffer();
    const fileBytes = new Uint8Array(arrayBuffer);

    // Always generate a random key (256 bits)
    const randomKey = await generateKey();
    const rawKey = await exportKey(randomKey);
    const keyMaterial = bytesToBase64Url(rawKey);

    // First encryption layer with random key
    const iv = generateRandomBytes(IV_LENGTH);
    let ciphertextBytes = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        randomKey,
        fileBytes
    );

    let salt = null;
    let iv2 = null;

    // Second encryption layer with passphrase-derived key
    if (passphrase && passphrase.trim()) {
        salt = generateRandomBytes(SALT_LENGTH);
        iv2 = generateRandomBytes(IV_LENGTH);
        const passphraseKey = await deriveKeyFromPassphrase(passphrase, salt);

        ciphertextBytes = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv2 },
            passphraseKey,
            ciphertextBytes
        );
    }

    return {
        encryptedBlob: new Blob([ciphertextBytes], { type: 'application/octet-stream' }),
        iv: bytesToBase64Url(iv),
        salt: salt ? bytesToBase64Url(salt) : null,
        iv2: iv2 ? bytesToBase64Url(iv2) : null,
        keyMaterial,
        version: CRYPTO_VERSION,
        filename: file.name,
        mime: file.type || 'application/octet-stream',
        size: file.size
    };
}

/**
 * Decrypt file data using AES-256-GCM
 * If passphrase was used, performs double decryption (same as decryptSecret)
 *
 * @param {ArrayBuffer} encryptedData - The encrypted file data
 * @param {string} iv - Base64URL encoded IV (for random key layer)
 * @param {string} keyMaterial - Base64URL encoded random key
 * @param {string|null} salt - Base64URL encoded salt (if passphrase was used)
 * @param {string|null} iv2 - Base64URL encoded IV for passphrase layer
 * @param {string|null} passphrase - Passphrase (if used during encryption)
 * @param {number} version - Crypto version
 * @returns {Promise<ArrayBuffer>}
 */
export async function decryptFile(encryptedData, iv, keyMaterial, salt = null, iv2 = null, passphrase = null, version = CRYPTO_VERSION) {
    if (version !== CRYPTO_VERSION) {
        throw new Error(`Unsupported crypto version: ${version}`);
    }

    if (!keyMaterial) {
        throw new Error('Key material is required');
    }

    let dataBytes = new Uint8Array(encryptedData);

    // First: decrypt passphrase layer if present
    if (salt && iv2 && passphrase) {
        const saltBytes = base64UrlToBytes(salt);
        const iv2Bytes = base64UrlToBytes(iv2);
        const passphraseKey = await deriveKeyFromPassphrase(passphrase, saltBytes);

        dataBytes = new Uint8Array(await crypto.subtle.decrypt(
            { name: 'AES-GCM', iv: iv2Bytes },
            passphraseKey,
            dataBytes
        ));
    }

    // Then: decrypt with random key
    const ivBytes = base64UrlToBytes(iv);
    const rawKey = base64UrlToBytes(keyMaterial);
    const randomKey = await importKey(rawKey);

    return crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: ivBytes },
        randomKey,
        dataBytes
    );
}

/**
 * Check if Web Crypto API is available
 * @returns {boolean}
 */
export function isCryptoAvailable() {
    return !!(crypto && crypto.subtle && crypto.getRandomValues);
}
