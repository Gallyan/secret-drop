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
    'email_placeholder' => 'votre@email.com',
    'email_hint' => 'Pour gérer vos secrets plus tard (révoquer, prolonger)',

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
    'success_admin_hint' => 'Vous pourrez gérer ce secret (révoquer, prolonger) via le lien « :link » en bas de page.',

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

    // Emails
    'email_secret_created_subject' => 'Votre secret a été créé',
    'email_secret_created_intro' => 'Vous avez créé un nouveau secret. Utilisez le lien ci-dessous pour le gérer (voir le statut, révoquer, prolonger).',
    'email_type' => 'Type',
    'email_expires' => 'Expiration',
    'email_manage_secret' => 'Gérer mon secret',
    'email_save_link_warning' => 'Conservez ce lien ! C\'est le seul moyen d\'accéder à l\'administration de votre secret.',
    'email_link_label' => 'Ou copiez ce lien :',
    'email_footer' => 'Cet email a été envoyé par :app.',
    'type_text' => 'Texte',
    'type_file' => 'Fichier',

    // Footer
    'footer_manage' => 'Gérer mes secrets',
    'footer_legal' => 'Mentions légales',

    // Legal page
    'legal_title' => 'Mentions légales',
    'legal_editor_title' => 'Éditeur du site',
    'legal_editor_text' => 'Ce site est édité par :name.',
    'legal_hosting_title' => 'Hébergement',
    'legal_hosting_text' => 'Ce site est hébergé par :host.',
    'legal_data_title' => 'Protection des données',
    'legal_data_text' => 'Secret Drop est conçu selon le principe de "zero-knowledge". Les secrets sont chiffrés dans votre navigateur avant d\'être envoyés au serveur. Le serveur ne stocke que des données chiffrées et ne peut pas accéder au contenu en clair de vos secrets.',
    'legal_data_stored' => 'Données stockées :',
    'legal_data_item_ciphertext' => 'Données chiffrées (ciphertext, IV, salt)',
    'legal_data_item_metadata' => 'Métadonnées (date de création, expiration, nombre de lectures)',
    'legal_data_item_email' => 'Hash de l\'email (si fourni, pour l\'accès administrateur)',
    'legal_data_not_stored' => 'Données NON stockées :',
    'legal_data_not_item_plaintext' => 'Contenu en clair des secrets',
    'legal_data_not_item_key' => 'Clés de chiffrement (transmises uniquement via le fragment d\'URL)',
    'legal_cookies_title' => 'Cookies',
    'legal_cookies_text' => 'Ce site utilise uniquement des cookies techniques essentiels au fonctionnement (session, préférence de thème). Aucun cookie de tracking ou publicitaire n\'est utilisé.',
    'legal_cookies_cnil' => 'Conformément aux recommandations de la CNIL, ces cookies strictement nécessaires sont exemptés du recueil de consentement.',
    'legal_contact_title' => 'Contact',
    'legal_contact_text' => 'Pour toute question concernant ce site, vous pouvez nous contacter à :email.',

    // Admin
    'admin_title' => 'Gérer vos secrets',
    'admin_description' => 'Entrez l\'email utilisé lors de la création de vos secrets pour accéder au panneau d\'administration.',
    'admin_email_placeholder' => 'votre@email.com',
    'admin_send_link' => 'Envoyer le lien magique',
    'admin_back_home' => 'Retour à l\'accueil',
    'admin_link_sent_title' => 'Vérifiez votre boîte mail',
    'admin_link_sent_description' => 'Si des secrets existent pour cet email, un lien magique a été envoyé.',
    'admin_link_sent_warning' => 'Le lien est valable 5 minutes et ne peut être utilisé qu\'une seule fois.',
    'admin_invalid_link_title' => 'Lien invalide ou expiré',
    'admin_invalid_link_description' => 'Ce lien magique est invalide ou a déjà été utilisé. Veuillez en demander un nouveau.',
    'admin_not_found_title' => 'Secret introuvable',
    'admin_not_found_description' => 'Ce secret n\'existe pas ou a été supprimé.',
    'admin_dashboard_title' => 'Mes secrets',
    'admin_secrets_count' => ':count secret(s)',
    'admin_logout' => 'Se déconnecter',
    'admin_no_secrets' => 'Aucun secret trouvé',
    'admin_no_secrets_description' => 'Vous n\'avez créé aucun secret avec cette adresse email.',
    'admin_status_active' => 'Actif',
    'admin_status_expired' => 'Expiré',
    'admin_status_revoked' => 'Révoqué',
    'admin_status_consumed' => 'Consommé',
    'admin_created' => 'Créé',
    'admin_expires' => 'Expire',
    'admin_read_count' => 'Lectures',
    'admin_first_read' => 'Première lecture',
    'admin_mode' => 'Mode',
    'admin_single_use' => 'Usage unique',
    'admin_multi_use' => 'Multi-usage',
    'admin_day' => 'jour',
    'admin_days' => 'jours',
    'admin_extend' => 'Prolonger',
    'admin_revoke' => 'Révoquer',
    'admin_revoke_confirm' => 'Êtes-vous sûr de vouloir révoquer ce secret ? Cette action est irréversible.',

    // Magic link email
    'email_magic_link_subject' => 'Votre lien d\'accès à Secret Drop',
    'email_magic_link_intro' => 'Vous avez demandé l\'accès pour gérer vos secrets. Cliquez sur le bouton ci-dessous pour vous connecter.',
    'email_magic_link_button' => 'Accéder à mes secrets',
    'email_magic_link_warning' => 'Ce lien expire dans 5 minutes et ne peut être utilisé qu\'une seule fois.',

    // Super Admin
    'superadmin_title' => 'Super Admin',
    'superadmin_description' => '',
    'email_superadmin_subject' => 'Accès Super Admin - Secret Drop',
    'email_superadmin_intro' => 'Une demande d\'accès super admin a été effectuée. Cliquez sur le bouton ci-dessous pour accéder au tableau de bord.',
    'email_superadmin_button' => 'Accéder au tableau de bord',
    'superadmin_link_sent_description' => 'Si cet email est autorisé, un lien magique a été envoyé.',
    'superadmin_dashboard_title' => 'Statistiques d\'utilisation',
    'superadmin_dashboard_subtitle' => 'Données d\'utilisation anonymes de votre instance Secret Drop.',

    // Periods
    'period_7d' => '7 derniers jours',
    'period_30d' => '30 derniers jours',
    'period_90d' => '90 derniers jours',
    'period_1y' => 'Dernière année',
    'period_all' => 'Depuis toujours',

    // Stats
    'stat_total_secrets' => 'Total secrets',
    'stat_total_reads' => 'Total lectures',
    'stat_total_files' => 'Fichiers partagés',
    'stat_total_volume' => 'Volume total',
    'stat_text' => 'Texte',
    'stat_file' => 'Fichier',
    'stat_reads' => 'Lectures',
    'stat_passphrase' => 'Avec passphrase',
    'stat_single_use' => 'Usage unique',
    'stat_max_views' => 'Limite de lectures',
    'stat_read' => 'Lus',
    'stat_expired_unread' => 'Expirés non lus',
    'stat_revoked' => 'Révoqués',
    'stat_max_reached' => 'Limite atteinte',
    'stat_magic_links_requested' => 'Magic links demandés',
    'stat_magic_links_used' => 'Magic links utilisés',
    'stat_secrets_extended' => 'Secrets prolongés',

    // Charts
    'chart_secrets_created' => 'Secrets créés',
    'chart_secrets_read' => 'Secrets lus',
    'chart_secret_types' => 'Types de secrets',
    'chart_secret_options' => 'Options utilisées',
    'chart_secret_outcomes' => 'Devenir des secrets',
    'chart_admin_activity' => 'Activité admin',

    // File size units
    'unit_bytes' => 'o',
    'unit_kilobytes' => 'Ko',
    'unit_megabytes' => 'Mo',
    'unit_gigabytes' => 'Go',

    // Accessibility
    'a11y_back' => 'Retour',
    'a11y_period_selector' => 'Sélectionner la période',
    'a11y_extend_days' => 'Nombre de jours de prolongation',
    'a11y_expand_secret' => 'Afficher les détails du secret',
];
