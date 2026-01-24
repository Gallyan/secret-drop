<?php

return [
    // App
    'app_name' => 'Secret Drop',
    'app_description' => 'Partagez des informations sensibles en toute sécurité avec un chiffrement de bout en bout.',

    // Features
    'feature_encryption' => 'Chiffrement AES-256-GCM dans votre navigateur',
    'feature_zero_knowledge' => 'Le serveur ne voit jamais vos données en clair',
    'feature_auto_destroy' => 'Auto-destruction après lecture',
    'feature_expiration' => 'Expiration automatique configurable',

    // Form labels
    'your_secret' => 'Votre secret',
    'your_file' => 'Votre fichier',
    'expires_in' => 'Expire dans',
    'max_reads' => 'Lectures max',
    'destroy_after_read' => 'Détruire après lecture',
    'advanced_options' => 'Options avancées',
    'passphrase' => 'Passphrase',
    'passphrase_placeholder' => 'Protection supplémentaire',
    'your_email' => 'Votre email',
    'email_placeholder' => 'pour gérer votre secret',

    // Form tabs
    'tab_text' => 'Texte',
    'tab_file' => 'Fichier',

    // Form placeholders
    'secret_placeholder' => 'Entrez votre message confidentiel...',
    'max_reads_placeholder' => 'Illimité',

    // Expiration options
    'expiration_1h' => '1 heure',
    'expiration_1d' => '1 jour',
    'expiration_7d' => '7 jours',
    'expiration_30d' => '30 jours',

    // File upload
    'file_drop_click' => 'Cliquez pour choisir',
    'file_drop_or_drag' => 'ou glissez un fichier',
    'file_max_size' => 'Maximum 100 Mo',
    'file_too_large' => 'Le fichier est trop volumineux (max 100 Mo)',

    // Buttons
    'btn_encrypt' => 'Chiffrer et créer le lien',
    'btn_encrypting' => 'Chiffrement...',
    'btn_encrypting_upload' => 'Chiffrement et upload...',
    'btn_copy' => 'Copier',
    'btn_copied' => 'Copié !',
    'btn_decrypt' => 'Déchiffrer',
    'btn_decrypting' => 'Déchiffrement...',
    'btn_retry' => 'Réessayer',
    'btn_download_again' => 'Télécharger à nouveau',
    'btn_create_new' => 'Créer un nouveau secret',

    // Success
    'secret_created' => 'Secret créé',
    'share_link_instruction' => 'Partagez ce lien avec votre destinataire',
    'warning_link_contains_key' => 'Ce lien contient la clé de déchiffrement. Ne le partagez qu\'avec le destinataire.',
    'warning_passphrase_required' => 'Le destinataire devra entrer la passphrase pour déchiffrer le secret.',

    // View secret
    'secret_message' => 'Message secret',
    'secret_file' => 'Fichier secret',
    'encrypted_end_to_end_message' => 'Ce message a été chiffré de bout en bout',
    'encrypted_end_to_end_file' => 'Ce fichier a été chiffré de bout en bout',
    'passphrase_protected' => 'Ce secret est protégé par une passphrase.',
    'passphrase_input_placeholder' => 'Entrez la passphrase',
    'decrypting_message' => 'Déchiffrement en cours...',
    'decrypting_file' => 'Téléchargement et déchiffrement...',
    'file_decrypted' => 'Fichier déchiffré',
    'note_destroyed_text' => 'Ce secret a été configuré pour être détruit après lecture. Il n\'est plus accessible.',
    'note_destroyed_file' => 'Ce fichier a été configuré pour être détruit après lecture. Il n\'est plus accessible sur le serveur.',

    // Errors
    'error_not_found' => 'Secret introuvable',
    'error_unavailable' => 'Secret indisponible',
    'error_generic' => 'Erreur',
    'secret_not_exist' => 'Ce secret n\'existe pas ou a peut-être été supprimé.',
    'secret_expired' => 'Ce secret a expiré et n\'est plus accessible.',
    'secret_revoked' => 'Ce secret a été révoqué par son créateur.',
    'secret_max_views' => 'Ce secret a atteint son nombre maximum de lectures et n\'est plus accessible.',
    'secret_unavailable_generic' => 'Ce secret n\'est plus accessible.',
    'error_loading' => 'Une erreur est survenue lors du chargement du secret.',
    'error_connection' => 'Impossible de charger le secret. Vérifiez votre connexion.',

    // Crypto errors
    'crypto_not_supported' => 'Votre navigateur ne supporte pas le chiffrement sécurisé',
    'crypto_key_missing' => 'Clé de déchiffrement manquante dans l\'URL',
    'crypto_fragment_invalid' => 'Format de fragment invalide',
    'crypto_passphrase_required' => 'La passphrase est requise',
    'crypto_passphrase_incorrect' => 'Passphrase incorrecte ou données corrompues',
    'crypto_decryption_failed' => 'Échec du déchiffrement. La clé est peut-être incorrecte.',
    'crypto_decryption_error' => 'Une erreur est survenue lors du déchiffrement',
    'crypto_file_download_failed' => 'Impossible de télécharger le fichier chiffré',
    'crypto_clipboard_failed' => 'Impossible de copier dans le presse-papier',
    'crypto_enter_secret' => 'Veuillez entrer un secret',
    'crypto_select_file' => 'Veuillez sélectionner un fichier',
    'crypto_creation_error' => 'Erreur lors de la création du secret',

    // Loading
    'loading' => 'Chargement...',
    'loading_secret' => 'Chargement du secret...',
];
