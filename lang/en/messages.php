<?php

return [
    // App
    'app_name' => 'Secret Drop',
    'app_description' => 'Securely share sensitive information with end-to-end encryption.',

    // Features
    'feature_encryption' => 'AES-256-GCM encryption in your browser',
    'feature_zero_knowledge' => 'Server never sees your plaintext data',
    'feature_auto_destroy' => 'Auto-destroy after reading',
    'feature_expiration' => 'Configurable automatic expiration',

    // Form labels
    'your_secret' => 'Your secret',
    'your_file' => 'Your file',
    'expires_in' => 'Expires in',
    'max_reads' => 'Max reads',
    'destroy_after_read' => 'Destroy after reading',
    'advanced_options' => 'Advanced options',
    'passphrase' => 'Passphrase',
    'passphrase_placeholder' => 'Additional protection',
    'your_email' => 'Your email',
    'email_placeholder' => 'to manage your secret',

    // Form tabs
    'tab_text' => 'Text',
    'tab_file' => 'File',

    // Form placeholders
    'secret_placeholder' => 'Enter your confidential message...',
    'max_reads_placeholder' => 'Unlimited',

    // Expiration options
    'expiration_1h' => '1 hour',
    'expiration_1d' => '1 day',
    'expiration_7d' => '7 days',
    'expiration_30d' => '30 days',

    // File upload
    'file_drop_click' => 'Click to choose',
    'file_drop_or_drag' => 'or drag a file',
    'file_max_size' => 'Maximum 100 MB',
    'file_too_large' => 'File is too large (max 100 MB)',

    // Buttons
    'btn_encrypt' => 'Encrypt and create link',
    'btn_encrypting' => 'Encrypting...',
    'btn_encrypting_upload' => 'Encrypting and uploading...',
    'btn_copy' => 'Copy',
    'btn_copied' => 'Copied!',
    'btn_decrypt' => 'Decrypt',
    'btn_decrypting' => 'Decrypting...',
    'btn_retry' => 'Retry',
    'btn_download_again' => 'Download again',
    'btn_create_new' => 'Create a new secret',

    // Success
    'secret_created' => 'Secret created',
    'share_link_instruction' => 'Share this link with your recipient',
    'warning_link_contains_key' => 'This link contains the decryption key. Only share it with the intended recipient.',
    'warning_passphrase_required' => 'The recipient will need to enter the passphrase to decrypt the secret.',

    // View secret
    'secret_message' => 'Secret message',
    'secret_file' => 'Secret file',
    'encrypted_end_to_end_message' => 'This message was encrypted end-to-end',
    'encrypted_end_to_end_file' => 'This file was encrypted end-to-end',
    'passphrase_protected' => 'This secret is protected by a passphrase.',
    'passphrase_input_placeholder' => 'Enter the passphrase',
    'decrypting_message' => 'Decrypting...',
    'decrypting_file' => 'Downloading and decrypting...',
    'file_decrypted' => 'File decrypted',
    'note_destroyed_text' => 'This secret was configured to be destroyed after reading. It is no longer accessible.',
    'note_destroyed_file' => 'This file was configured to be destroyed after reading. It is no longer accessible on the server.',

    // Errors
    'error_not_found' => 'Secret not found',
    'error_unavailable' => 'Secret unavailable',
    'error_generic' => 'Error',
    'secret_not_exist' => 'This secret does not exist or may have been deleted.',
    'secret_expired' => 'This secret has expired and is no longer accessible.',
    'secret_revoked' => 'This secret was revoked by its creator.',
    'secret_max_views' => 'This secret has reached its maximum number of views and is no longer accessible.',
    'secret_unavailable_generic' => 'This secret is no longer accessible.',
    'error_loading' => 'An error occurred while loading the secret.',
    'error_connection' => 'Unable to load the secret. Check your connection.',

    // Crypto errors
    'crypto_not_supported' => 'Your browser does not support secure encryption',
    'crypto_key_missing' => 'Decryption key missing from URL',
    'crypto_fragment_invalid' => 'Invalid fragment format',
    'crypto_passphrase_required' => 'Passphrase is required',
    'crypto_passphrase_incorrect' => 'Incorrect passphrase or corrupted data',
    'crypto_decryption_failed' => 'Decryption failed. The key may be incorrect.',
    'crypto_decryption_error' => 'An error occurred during decryption',
    'crypto_file_download_failed' => 'Unable to download encrypted file',
    'crypto_clipboard_failed' => 'Unable to copy to clipboard',
    'crypto_enter_secret' => 'Please enter a secret',
    'crypto_select_file' => 'Please select a file',
    'crypto_creation_error' => 'Error creating secret',

    // Loading
    'loading' => 'Loading...',
    'loading_secret' => 'Loading secret...',
];
