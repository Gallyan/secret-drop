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
    'email_placeholder' => 'your@email.com',
    'email_hint' => 'To manage your secrets later (revoke, extend)',

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
    'success_admin_hint' => 'You can manage this secret (revoke, extend) via the ":link" link at the bottom of the page.',

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

    // Emails
    'email_secret_created_subject' => 'Your secret has been created',
    'email_secret_created_intro' => 'You have created a new secret. Use the link below to manage it (view status, revoke, extend).',
    'email_type' => 'Type',
    'email_expires' => 'Expires',
    'email_manage_secret' => 'Manage my secret',
    'email_save_link_warning' => 'Save this link! It is the only way to access your secret\'s administration.',
    'email_link_label' => 'Or copy this link:',
    'email_footer' => 'This email was sent by :app.',
    'type_text' => 'Text',
    'type_file' => 'File',

    // Footer
    'footer_manage' => 'Manage my secrets',
    'footer_legal' => 'Legal notice',

    // Legal page
    'legal_title' => 'Legal Notice',
    'legal_editor_title' => 'Website Publisher',
    'legal_editor_text' => 'This website is published by :name.',
    'legal_hosting_title' => 'Hosting',
    'legal_hosting_text' => 'This website is hosted by :host.',
    'legal_data_title' => 'Data Protection',
    'legal_data_text' => 'Secret Drop is designed with a "zero-knowledge" principle. Secrets are encrypted in your browser before being sent to the server. The server only stores encrypted data and cannot access the plaintext content of your secrets.',
    'legal_data_stored' => 'Data stored:',
    'legal_data_item_ciphertext' => 'Encrypted data (ciphertext, IV, salt)',
    'legal_data_item_metadata' => 'Metadata (creation date, expiration, read count)',
    'legal_data_item_email' => 'Email hash (if provided, for admin access)',
    'legal_data_not_stored' => 'Data NOT stored:',
    'legal_data_not_item_plaintext' => 'Plaintext content of secrets',
    'legal_data_not_item_key' => 'Encryption keys (transmitted only via URL fragment)',
    'legal_cookies_title' => 'Cookies',
    'legal_cookies_text' => 'This website only uses essential technical cookies (session, theme preference). No tracking or advertising cookies are used.',
    'legal_cookies_cnil' => 'In accordance with CNIL recommendations, these strictly necessary cookies are exempt from consent requirements.',
    'legal_contact_title' => 'Contact',
    'legal_contact_text' => 'For any questions about this website, you can contact us at :email.',

    // Admin
    'admin_title' => 'Manage your secrets',
    'admin_description' => 'Enter the email you used when creating your secrets to access the admin panel.',
    'admin_email_placeholder' => 'your@email.com',
    'admin_send_link' => 'Send magic link',
    'admin_back_home' => 'Back to home',
    'admin_link_sent_title' => 'Check your inbox',
    'admin_link_sent_description' => 'If secrets exist for this email, a magic link has been sent.',
    'admin_link_sent_warning' => 'The link is valid for 5 minutes and can only be used once.',
    'admin_invalid_link_title' => 'Invalid or expired link',
    'admin_invalid_link_description' => 'This magic link is invalid or has already been used. Please request a new one.',
    'admin_not_found_title' => 'Secret not found',
    'admin_not_found_description' => 'This secret does not exist or has been deleted.',
    'admin_dashboard_title' => 'My secrets',
    'admin_secrets_count' => ':count secret(s)',
    'admin_logout' => 'Log out',
    'admin_no_secrets' => 'No secrets found',
    'admin_no_secrets_description' => 'You have not created any secrets with this email address.',
    'admin_status_active' => 'Active',
    'admin_status_expired' => 'Expired',
    'admin_status_revoked' => 'Revoked',
    'admin_status_consumed' => 'Consumed',
    'admin_created' => 'Created',
    'admin_expires' => 'Expires',
    'admin_read_count' => 'Read count',
    'admin_first_read' => 'First read',
    'admin_mode' => 'Mode',
    'admin_single_use' => 'Single use',
    'admin_multi_use' => 'Multi-use',
    'admin_day' => 'day',
    'admin_days' => 'days',
    'admin_extend' => 'Extend',
    'admin_revoke' => 'Revoke',
    'admin_revoke_confirm' => 'Are you sure you want to revoke this secret? This action is irreversible.',

    // Magic link email
    'email_magic_link_subject' => 'Your access link to Secret Drop',
    'email_magic_link_intro' => 'You requested access to manage your secrets. Click the button below to log in.',
    'email_magic_link_button' => 'Access my secrets',
    'email_magic_link_warning' => 'This link expires in 5 minutes and can only be used once.',

    // Super Admin
    'superadmin_title' => 'Super Admin',
    'superadmin_description' => '',
    'email_superadmin_subject' => 'Super Admin Access - Secret Drop',
    'email_superadmin_intro' => 'A super admin access request was made. Click the button below to access the dashboard.',
    'email_superadmin_button' => 'Access dashboard',
    'superadmin_link_sent_description' => 'If this email is authorized, a magic link has been sent.',
    'superadmin_dashboard_title' => 'Usage Statistics',
    'superadmin_dashboard_subtitle' => 'Anonymous usage data for your Secret Drop instance.',

    // Periods
    'period_7d' => 'Last 7 days',
    'period_30d' => 'Last 30 days',
    'period_90d' => 'Last 90 days',
    'period_1y' => 'Last year',
    'period_all' => 'All time',

    // Stats
    'stat_total_secrets' => 'Total secrets',
    'stat_total_reads' => 'Total reads',
    'stat_total_files' => 'Files shared',
    'stat_total_volume' => 'Total volume',
    'stat_text' => 'Text',
    'stat_file' => 'File',
    'stat_reads' => 'Reads',
    'stat_passphrase' => 'With passphrase',
    'stat_single_use' => 'Single use',
    'stat_max_views' => 'Max views limit',
    'stat_read' => 'Read',
    'stat_expired_unread' => 'Expired unread',
    'stat_revoked' => 'Revoked',
    'stat_max_reached' => 'Max views reached',
    'stat_magic_links_requested' => 'Magic links requested',
    'stat_magic_links_used' => 'Magic links used',
    'stat_secrets_extended' => 'Secrets extended',

    // Charts
    'chart_secrets_created' => 'Secrets created',
    'chart_secrets_read' => 'Secrets read',
    'chart_secret_types' => 'Secret types',
    'chart_secret_options' => 'Options used',
    'chart_secret_outcomes' => 'Secret outcomes',
    'chart_admin_activity' => 'Admin activity',
];
