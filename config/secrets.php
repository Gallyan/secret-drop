<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Secret Expiration Options
    |--------------------------------------------------------------------------
    |
    | Each key is the value used in forms and validation.
    | Each value is the number of hours until expiration.
    | The translation key is derived automatically: messages.expiration_{key}
    |
    */
    'expirations' => [
        '1h' => 1,
        '1d' => 24,
        '7d' => 168,
        '30d' => 720,
        '90d' => 2160,
    ],

    'default_expiration' => '7d',

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'daily_limit_per_ip' => (int) env('SECRETS_DAILY_LIMIT_PER_IP', 50),

    /*
    |--------------------------------------------------------------------------
    | Global Storage Quota (MB) — 0 for unlimited
    |--------------------------------------------------------------------------
    |
    | Covers everything the application stores: encrypted blobs on the secrets
    | disk and text ciphertexts in the database. Creation is refused with a 503
    | once the quota is reached, whatever the secret type.
    |
    | SECRETS_FILE_STORAGE_QUOTA_MB is the former name, kept as a fallback so a
    | deployment whose .env still uses it keeps its configured value. Drop it
    | once every environment has been renamed.
    |
    */
    'storage_quota_mb' => (int) env('SECRETS_STORAGE_QUOTA_MB', env('SECRETS_FILE_STORAGE_QUOTA_MB', 5120)),

    /*
    |--------------------------------------------------------------------------
    | Per-IP Stored Bytes Budget (MB) — 0 for unlimited
    |--------------------------------------------------------------------------
    |
    | Keeps one requester from filling the global quota on its own. Counts every
    | byte stored for that IP, uploaded files and text ciphertexts alike, for as
    | long as the secret can live (up to the longest expiration), not per day.
    |
    | SECRETS_DAILY_UPLOAD_MB_PER_IP is the former name, from when the budget
    | was reset daily, kept as a fallback so an existing .env keeps its value.
    |
    */
    'upload_mb_per_ip' => (int) env('SECRETS_UPLOAD_MB_PER_IP', env('SECRETS_DAILY_UPLOAD_MB_PER_IP', 1024)),

    /*
    |--------------------------------------------------------------------------
    | Session & Magic Link Durations (minutes)
    |--------------------------------------------------------------------------
    |
    | A new magic link can be requested at any time; only the latest one stays
    | valid. Two links to one address are spaced by the resend cooldown, which
    | bounds the mails without a counter a third party could exhaust. The hourly
    | cap applies to the +tag aliases of one mailbox taken together (each alias
    | owns its secrets separately); the base address itself is exempt.
    |
    */
    'magic_link_ttl' => (int) env('MAGIC_LINK_TTL', 10),
    'magic_link_max_per_recipient_per_hour' => (int) env('MAGIC_LINK_MAX_PER_RECIPIENT_PER_HOUR', 5),
    'magic_link_resend_cooldown_seconds' => (int) env('MAGIC_LINK_RESEND_COOLDOWN_SECONDS', 60),
    'admin_session_ttl' => (int) env('ADMIN_SESSION_TTL', 15),
    'super_admin_session_ttl' => (int) env('SUPER_ADMIN_SESSION_TTL', 15),

    /*
    |--------------------------------------------------------------------------
    | Email Hash Pepper
    |--------------------------------------------------------------------------
    |
    | Used as HMAC key when hashing creator emails. Without a pepper, a leaked
    | DB allows trivial bruteforce of known email lists against unsalted SHA-256.
    | Defaults to a value derived from APP_KEY (so zero-config still works).
    | Override only if you want to rotate the pepper independently of APP_KEY.
    |
    */
    'email_hash_pepper' => env('EMAIL_HASH_PEPPER')
        ?: hash('sha256', 'secret-drop:email-hash-pepper:'.env('APP_KEY', '')),
];
