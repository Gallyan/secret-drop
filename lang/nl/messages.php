<?php

return [
    // App
    'app_name' => 'Secret Drop',
    'app_description' => 'Deel gevoelige informatie veilig met end-to-end encryptie.',

    // Features
    'feature_encryption' => 'AES-256-GCM encryptie in uw browser',
    'feature_zero_knowledge' => 'Server ziet nooit uw onversleutelde gegevens',
    'feature_auto_destroy' => 'Automatisch vernietigen na lezen',
    'feature_expiration' => 'Configureerbare automatische vervaldatum',

    // Form labels
    'your_secret' => 'Uw geheim',
    'your_file' => 'Uw bestand',
    'expires_in' => 'Verloopt over',
    'expires_in_hint' => 'Het geheim wordt automatisch verwijderd na deze tijd, ook als het niet is gelezen.',
    'max_reads' => 'Max. leesbeurten',
    'max_reads_hint' => 'Beperkt hoe vaak het geheim bekeken kan worden. Bij bereiken wordt het automatisch verwijderd.',
    'destroy_after_read' => 'Vernietigen na lezen',
    'advanced_options' => 'Geavanceerde opties',
    'passphrase' => 'Wachtwoordzin',
    'passphrase_hint' => 'Voegt een beveiligingslaag toe. De ontvanger moet deze kennen. Wordt nooit naar de server gestuurd.',
    'passphrase_placeholder' => 'Extra bescherming',
    'your_email' => 'Uw e-mail',
    'email_placeholder' => 'uw@email.nl',
    'email_hint' => 'Om uw geheimen later te beheren (intrekken, verlengen)',

    // Form tabs
    'tab_text' => 'Tekst',
    'tab_file' => 'Bestand',

    // Form placeholders
    'secret_placeholder' => 'Voer uw vertrouwelijke bericht in...',
    'max_reads_placeholder' => 'Onbeperkt',

    // Expiration options
    'expiration_1h' => '1 uur',
    'expiration_1d' => '1 dag',
    'expiration_7d' => '7 dagen',
    'expiration_30d' => '30 dagen',

    // File upload
    'file_drop_click' => 'Klik om te kiezen',
    'file_drop_or_drag' => 'of sleep een bestand',
    'file_max_size' => 'Maximaal 100 MB',
    'file_too_large' => 'Bestand is te groot (max 100 MB)',

    // Buttons
    'btn_encrypt' => 'Versleutelen en link maken',
    'btn_encrypting' => 'Versleutelen...',
    'btn_encrypting_upload' => 'Versleutelen en uploaden...',
    'btn_copy' => 'Kopiëren',
    'btn_copied' => 'Gekopieerd!',
    'btn_decrypt' => 'Ontsleutelen',
    'btn_decrypting' => 'Ontsleutelen...',
    'btn_retry' => 'Opnieuw proberen',
    'btn_cancel' => 'Annuleren',
    'btn_download_again' => 'Opnieuw downloaden',
    'btn_create_new' => 'Nieuw geheim maken',

    // Success
    'secret_created' => 'Geheim aangemaakt',
    'share_link_instruction' => 'Deel deze link met uw ontvanger',
    'warning_link_contains_key' => 'Deze link bevat de ontsleutelingssleutel. Deel alleen met de beoogde ontvanger.',
    'warning_passphrase_required' => 'De ontvanger moet de wachtwoordzin invoeren om het geheim te ontsleutelen.',
    'success_admin_hint' => 'U kunt dit geheim beheren (intrekken, verlengen) via de ":link" link onderaan de pagina.',

    // QR Code
    'show_qr_code' => 'QR-code tonen',
    'hide_qr_code' => 'QR-code verbergen',
    'download_qr_code' => 'QR-code downloaden',
    'qr_code_hint' => 'Scan deze QR-code om de deellink op een ander apparaat te openen.',
    'qr_code_alt' => 'QR-code met de deellink',
    'qr_generation_failed' => 'QR-code genereren mislukt.',

    // View secret
    'secret_message' => 'Geheim bericht',
    'secret_file' => 'Geheim bestand',
    'encrypted_end_to_end_message' => 'Dit bericht is end-to-end versleuteld',
    'encrypted_end_to_end_file' => 'Dit bestand is end-to-end versleuteld',
    'passphrase_protected' => 'Dit geheim is beveiligd met een wachtwoordzin.',
    'passphrase_input_placeholder' => 'Voer de wachtwoordzin in',
    'decrypting_message' => 'Ontsleutelen...',
    'decrypting_file' => 'Downloaden en ontsleutelen...',
    'file_decrypted' => 'Bestand ontsleuteld',
    'note_destroyed_text' => 'Dit geheim was ingesteld om na lezen te worden vernietigd. Het is niet meer toegankelijk.',
    'note_destroyed_file' => 'Dit bestand was ingesteld om na lezen te worden vernietigd. Het is niet meer toegankelijk op de server.',

    // Errors
    'error_not_found' => 'Geheim niet gevonden',
    'error_unavailable' => 'Geheim niet beschikbaar',
    'error_generic' => 'Fout',
    'secret_not_exist' => 'Dit geheim bestaat niet of is verwijderd.',
    'secret_expired' => 'Dit geheim is verlopen en niet meer toegankelijk.',
    'secret_revoked' => 'Dit geheim is ingetrokken door de maker.',
    'secret_max_views' => 'Dit geheim heeft het maximale aantal weergaven bereikt en is niet meer toegankelijk.',
    'secret_unavailable_generic' => 'Dit geheim is niet meer toegankelijk.',
    'error_loading' => 'Er is een fout opgetreden bij het laden van het geheim.',
    'error_connection' => 'Kan het geheim niet laden. Controleer uw verbinding.',

    // Crypto errors
    'crypto_not_supported' => 'Uw browser ondersteunt geen veilige encryptie',
    'crypto_key_missing' => 'Ontsleutelingssleutel ontbreekt in URL',
    'crypto_fragment_invalid' => 'Ongeldig fragmentformaat',
    'crypto_passphrase_required' => 'Wachtwoordzin is vereist',
    'crypto_passphrase_incorrect' => 'Onjuiste wachtwoordzin of beschadigde gegevens',
    'crypto_decryption_failed' => 'Ontsleuteling mislukt. De sleutel is mogelijk onjuist.',
    'crypto_decryption_error' => 'Er is een fout opgetreden tijdens het ontsleutelen',
    'crypto_file_download_failed' => 'Kan versleuteld bestand niet downloaden',
    'crypto_clipboard_failed' => 'Kan niet naar klembord kopiëren',
    'crypto_enter_secret' => 'Voer een geheim in',
    'crypto_select_file' => 'Selecteer een bestand',
    'crypto_creation_error' => 'Fout bij maken van geheim',

    // Loading
    'loading' => 'Laden...',
    'loading_secret' => 'Geheim laden...',

    // Emails
    'email_secret_created_subject' => 'Uw geheim is aangemaakt',
    'email_secret_created_intro' => 'U heeft een nieuw geheim aangemaakt. Gebruik de onderstaande link om het te beheren (status bekijken, intrekken, verlengen).',
    'email_type' => 'Type',
    'email_expires' => 'Verloopt',
    'email_manage_secret' => 'Mijn geheim beheren',
    'email_save_link_warning' => 'Bewaar deze link! Dit is de enige manier om toegang te krijgen tot het beheer van uw geheim.',
    'email_link_label' => 'Of kopieer deze link:',
    'email_footer' => 'Deze e-mail is verzonden door :app.',
    'type_text' => 'Tekst',
    'type_file' => 'Bestand',

    // Footer
    'footer_manage' => 'Mijn geheimen beheren',
    'footer_legal' => 'Juridische informatie',

    // Legal page
    'legal_title' => 'Juridische Informatie',
    'legal_editor_title' => 'Website-uitgever',
    'legal_editor_text' => 'Deze website wordt uitgegeven door :name.',
    'legal_hosting_title' => 'Hosting',
    'legal_hosting_text' => 'Deze website wordt gehost door:',
    'legal_hosting_phone' => 'Telefoon:',
    'legal_data_title' => 'Gegevensbescherming',
    'legal_data_text' => 'Secret Drop is ontworpen met een "zero-knowledge" principe. Geheimen worden in uw browser versleuteld voordat ze naar de server worden verzonden. De server slaat alleen versleutelde gegevens op en heeft geen toegang tot de onversleutelde inhoud van uw geheimen.',
    'legal_data_stored' => 'Opgeslagen gegevens:',
    'legal_data_item_ciphertext' => 'Versleutelde gegevens (ciphertext, IV, salt)',
    'legal_data_item_metadata' => 'Metadata (aanmaakdatum, vervaldatum, leesteller)',
    'legal_data_item_file_meta' => 'Bestandsmetadata (originele naam, type, grootte)',
    'legal_data_item_email' => 'E-mail hash (indien opgegeven, voor beheerderstoegang)',
    'legal_data_not_stored' => 'NIET opgeslagen gegevens:',
    'legal_data_not_item_plaintext' => 'Onversleutelde inhoud van geheimen',
    'legal_data_not_item_key' => 'Encryptiesleutels (alleen verzonden via URL-fragment)',
    'legal_cookies_title' => 'Cookies',
    'legal_cookies_text' => 'Deze website gebruikt alleen essentiële technische cookies (sessie, themavoorkeur). Er worden geen tracking- of advertentiecookies gebruikt.',
    'legal_cookies_cnil' => 'In overeenstemming met CNIL-aanbevelingen zijn deze strikt noodzakelijke cookies vrijgesteld van toestemmingsvereisten.',
    'legal_contact_title' => 'Contact',
    'legal_contact_text' => 'Voor vragen over deze website kunt u contact opnemen via :email.',
    'legal_contact_prefix' => 'Voor vragen over deze website kunt u contact opnemen via',

    // Admin
    'admin_title' => 'Uw geheimen beheren',
    'admin_description' => 'Voer het e-mailadres in dat u heeft gebruikt bij het maken van uw geheimen om toegang te krijgen tot het beheerpaneel.',
    'admin_email_placeholder' => 'uw@email.nl',
    'admin_send_link' => 'Magische link verzenden',
    'admin_back_home' => 'Terug naar home',
    'admin_link_sent_title' => 'Controleer uw inbox',
    'admin_link_sent_description' => 'Als er geheimen bestaan voor dit e-mailadres, is er een magische link verzonden.',
    'admin_link_sent_warning' => 'De link is 5 minuten geldig en kan slechts één keer worden gebruikt.',
    'admin_invalid_link_title' => 'Ongeldige of verlopen link',
    'admin_invalid_link_description' => 'Deze magische link is ongeldig of al gebruikt. Vraag een nieuwe aan.',
    'admin_not_found_title' => 'Geheim niet gevonden',
    'admin_not_found_description' => 'Dit geheim bestaat niet of is verwijderd.',
    'admin_dashboard_title' => 'Mijn geheimen',
    'admin_secrets_count' => ':count geheim(en)',
    'admin_logout' => 'Uitloggen',
    'admin_no_secrets' => 'Geen geheimen gevonden',
    'admin_no_secrets_description' => 'U heeft geen geheimen aangemaakt met dit e-mailadres.',
    'admin_status_active' => 'Actief',
    'admin_status_expired' => 'Verlopen',
    'admin_status_revoked' => 'Ingetrokken',
    'admin_status_consumed' => 'Verbruikt',
    'admin_created' => 'Aangemaakt',
    'admin_expires' => 'Verloopt',
    'admin_read_count' => 'Leesteller',
    'admin_first_read' => 'Eerste lezing',
    'admin_mode' => 'Modus',
    'admin_single_use' => 'Eenmalig gebruik',
    'admin_multi_use' => 'Meervoudig gebruik',
    'admin_day' => 'dag',
    'admin_days' => 'dagen',
    'admin_extend' => 'Verlengen',
    'admin_revoke' => 'Intrekken',
    'admin_revoke_confirm' => 'Weet u zeker dat u dit geheim wilt intrekken? Deze actie is onomkeerbaar.',

    // Magic link email
    'email_magic_link_subject' => 'Uw toegangslink voor Secret Drop',
    'email_magic_link_intro' => 'U heeft toegang aangevraagd om uw geheimen te beheren. Klik op de onderstaande knop om in te loggen.',
    'email_magic_link_button' => 'Mijn geheimen openen',
    'email_magic_link_warning' => 'Deze link verloopt over 5 minuten en kan slechts één keer worden gebruikt.',

    // Super Admin
    'superadmin_title' => 'Super Admin',
    'superadmin_description' => '',
    'email_superadmin_subject' => 'Super Admin Toegang - Secret Drop',
    'email_superadmin_intro' => 'Er is een super admin toegangsverzoek gedaan. Klik op de onderstaande knop om het dashboard te openen.',
    'email_superadmin_button' => 'Dashboard openen',
    'superadmin_link_sent_description' => 'Als dit e-mailadres geautoriseerd is, is er een magische link verzonden.',
    'superadmin_dashboard_title' => 'Gebruiksstatistieken',
    'superadmin_dashboard_subtitle' => 'Anonieme gebruiksgegevens voor uw Secret Drop-instantie.',

    // Periods
    'period_7d' => 'Laatste 7 dagen',
    'period_30d' => 'Laatste 30 dagen',
    'period_90d' => 'Laatste 90 dagen',
    'period_1y' => 'Laatste jaar',
    'period_all' => 'Alle tijd',

    // Stats
    'stat_secrets_created' => 'Geheimen aangemaakt',
    'stat_secrets_read' => 'Geheimen gelezen',
    'stat_files_shared' => 'Bestanden gedeeld',
    'stat_volume' => 'Volume',
    'stat_current_disk_usage' => 'Huidig schijfgebruik',
    'stat_text' => 'Tekst',
    'stat_file' => 'Bestand',
    'stat_reads' => 'Lezingen',
    'stat_passphrase' => 'Met wachtwoordzin',
    'stat_single_use' => 'Eenmalig gebruik',
    'stat_max_views' => 'Max weergaven limiet',
    'stat_read' => 'Gelezen',
    'stat_expired_unread' => 'Verlopen ongelezen',
    'stat_revoked' => 'Ingetrokken',
    'stat_max_reached' => 'Max weergaven bereikt',
    'stat_magic_links_requested' => 'Magische links aangevraagd',
    'stat_magic_links_used' => 'Magische links gebruikt',
    'stat_secrets_extended' => 'Geheimen verlengd',

    // Charts
    'chart_secrets_created' => 'Geheimen aangemaakt',
    'chart_secrets_read' => 'Geheimen gelezen',
    'chart_secret_types' => 'Geheimtypes',
    'chart_secret_options' => 'Gebruikte opties',
    'chart_secret_outcomes' => 'Geheimresultaten',
    'chart_admin_activity' => 'Beheeractiviteit',
    'chart_heatmap_created' => 'Aanmaak heatmap (per dag/uur)',
    'chart_heatmap_read' => 'Lezing heatmap (per dag/uur)',

    // Stats
    'stat_avg_first_read' => 'Gem. eerste leestijd',

    // Days
    'day_sunday' => 'Zo',
    'day_monday' => 'Ma',
    'day_tuesday' => 'Di',
    'day_wednesday' => 'Wo',
    'day_thursday' => 'Do',
    'day_friday' => 'Vr',
    'day_saturday' => 'Za',

    // File size units
    'unit_bytes' => 'B',
    'unit_kilobytes' => 'KB',
    'unit_megabytes' => 'MB',
    'unit_gigabytes' => 'GB',

    // Accessibility
    'a11y_back' => 'Terug',
    'a11y_period_selector' => 'Periode selecteren',
    'a11y_extend_days' => 'Aantal dagen om te verlengen',
    'a11y_expand_secret' => 'Geheimdetails tonen',

    // Split mode
    'split_mode' => 'Link en sleutel scheiden',
    'split_mode_hint' => 'Stuur link en sleutel via verschillende kanalen voor betere beveiliging',
    'split_mode_tooltip' => 'Als één kanaal wordt gecompromitteerd, heeft de aanvaller slechts een deel van de informatie.',
    'share_link_label' => 'Deellink',
    'share_key_label' => 'Ontsleutelingssleutel',
    'split_mode_warning' => 'Stuur de sleutel via een ander kanaal (SMS, telefoongesprek, persoonlijk...).',
    'enter_key_manually' => 'Voer de ontsleutelingssleutel in',
    'key_placeholder' => 'Afzonderlijk ontvangen sleutel',
    'btn_unlock' => 'Ontgrendelen',

    // Stats
    'stat_split_mode' => 'Gescheiden modus',

    // Rate limiting & Captcha
    'rate_limit_exceeded' => 'Te veel verzoeken. Los de berekening hieronder op om door te gaan.',
    'captcha_label' => 'Anti-robot verificatie',
    'captcha_placeholder' => 'Uw antwoord',
    'captcha_hint' => 'Reken uit: :challenge = ?',
    'captcha_invalid' => 'Onjuist antwoord. Probeer het opnieuw.',

    // Labels
    'label_important' => 'Belangrijk:',
    'label_note' => 'Opmerking:',

    // Passphrase strength criteria
    'passphrase_min_length' => '12 tekens min.',
    'passphrase_lowercase' => 'Kleine letter',
    'passphrase_uppercase' => 'Hoofdletter',
    'passphrase_digit' => 'Cijfer',
    'passphrase_special' => 'Speciaal teken',
];
