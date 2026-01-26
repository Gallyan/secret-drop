<?php

return [
    // App
    'app_name' => 'Secret Drop',
    'app_description' => 'Condividi informazioni sensibili in modo sicuro con crittografia end-to-end.',

    // Features
    'feature_encryption' => 'Crittografia di livello militare nel tuo browser',
    'feature_zero_knowledge' => 'Il server non vede mai i tuoi dati in chiaro',
    'feature_auto_destroy' => 'Auto-distruzione dopo la lettura',
    'feature_expiration' => 'Scadenza automatica configurabile',

    // Form labels
    'your_secret' => 'Il tuo segreto',
    'your_file' => 'Il tuo file',
    'expires_in' => 'Scade tra',
    'expires_in_hint' => 'Il segreto verrà eliminato automaticamente dopo questo tempo, anche se non è stato letto.',
    'max_reads' => 'Letture massime',
    'max_reads_hint' => 'Limita quante volte il segreto può essere visualizzato. Raggiunto il limite, viene eliminato automaticamente.',
    'advanced_options' => 'Opzioni avanzate',
    'passphrase' => 'Frase segreta',
    'passphrase_hint' => 'Protezione aggiuntiva. Il destinatario deve conoscerla. Non viene mai inviata al server.',
    'passphrase_placeholder' => 'Protezione aggiuntiva',
    'your_email' => 'La tua email',
    'email_placeholder' => 'tua@email.com',
    'email_hint' => 'Per gestire i tuoi segreti in seguito (revocare, estendere)',

    // Form tabs
    'tab_text' => 'Testo',
    'tab_file' => 'File',

    // Form placeholders
    'secret_placeholder' => 'Inserisci il tuo messaggio confidenziale...',
    'max_reads_placeholder' => 'Illimitato',

    // Expiration options
    'expiration_1h' => '1 ora',
    'expiration_1d' => '1 giorno',
    'expiration_7d' => '7 giorni',
    'expiration_30d' => '30 giorni',
    'expiration_90d' => '90 giorni',

    // File upload
    'file_drop_click' => 'Clicca per scegliere',
    'file_drop_or_drag' => 'o trascina un file',
    'file_max_size' => 'Massimo 100 MB',
    'file_too_large' => 'Il file è troppo grande (max 100 MB)',

    // Buttons
    'btn_encrypt' => 'Crittografa e crea link',
    'btn_encrypting' => 'Crittografando...',
    'btn_encrypting_upload' => 'Crittografando e caricando...',
    'btn_copy' => 'Copia',
    'btn_copied' => 'Copiato!',
    'btn_decrypt' => 'Decripta',
    'btn_decrypting' => 'Decriptando...',
    'btn_retry' => 'Riprova',
    'btn_cancel' => 'Annulla',
    'btn_download_again' => 'Scarica di nuovo',
    'btn_create_new' => 'Crea un nuovo segreto',

    // Success
    'secret_created' => 'Segreto creato',
    'share_link_instruction' => 'Condividi questo link con il tuo destinatario',
    'warning_link_contains_key' => 'Questo link contiene la chiave di decrittazione. Condividilo solo con il destinatario previsto.',
    'warning_passphrase_required' => 'Il destinatario dovrà inserire la frase segreta per decriptare il segreto.',
    'success_admin_hint' => 'Puoi gestire questo segreto (revocare, estendere) tramite il link ":link" in fondo alla pagina.',

    // QR Code
    'show_qr_code' => 'Mostra codice QR',
    'hide_qr_code' => 'Nascondi codice QR',
    'download_qr_code' => 'Scarica codice QR',
    'qr_code_hint' => 'Scansiona questo codice QR per aprire il link su un altro dispositivo.',
    'qr_code_alt' => 'Codice QR contenente il link di condivisione',
    'qr_generation_failed' => 'Impossibile generare il codice QR.',

    // View secret
    'secret_message' => 'Messaggio segreto',
    'secret_file' => 'File segreto',
    'encrypted_end_to_end_message' => 'Questo messaggio è stato crittografato end-to-end',
    'encrypted_end_to_end_file' => 'Questo file è stato crittografato end-to-end',
    'passphrase_protected' => 'Questo segreto è protetto da una frase segreta.',
    'passphrase_input_placeholder' => 'Inserisci la frase segreta',
    'decrypting_message' => 'Decriptando...',
    'decrypting_file' => 'Scaricando e decriptando...',
    'file_decrypted' => 'File decriptato',
    'note_destroyed_text' => 'Questo segreto era configurato per essere distrutto dopo la lettura. Non è più accessibile.',
    'note_destroyed_file' => 'Questo file era configurato per essere distrutto dopo la lettura. Non è più accessibile sul server.',
    'last_read_warning_title' => 'Questa è l\'ultima lettura disponibile',
    'last_read_warning_text' => 'Una volta visualizzato, questo segreto verrà eliminato definitivamente. Assicurati di essere pronto a visualizzarlo.',
    'last_read_warning_short' => 'Questa è l\'ultima lettura disponibile. Il segreto verrà eliminato definitivamente dopo la visualizzazione.',
    'btn_reveal_secret' => 'Mostra il segreto',

    // Errors
    'error_not_found' => 'Segreto non trovato',
    'error_unavailable' => 'Segreto non disponibile',
    'error_generic' => 'Errore',
    'secret_not_exist' => 'Questo segreto non esiste o potrebbe essere stato eliminato.',
    'secret_expired' => 'Questo segreto è scaduto e non è più accessibile.',
    'secret_revoked' => 'Questo segreto è stato revocato dal suo creatore.',
    'secret_max_views' => 'Questo segreto ha raggiunto il numero massimo di visualizzazioni e non è più accessibile.',
    'secret_unavailable_generic' => 'Questo segreto non è più accessibile.',
    'error_loading' => 'Si è verificato un errore durante il caricamento del segreto.',
    'error_connection' => 'Impossibile caricare il segreto. Verifica la tua connessione.',

    // Crypto errors
    'crypto_not_supported' => 'Il tuo browser non supporta la crittografia sicura',
    'crypto_key_missing' => 'Chiave di decrittazione mancante nell\'URL',
    'crypto_fragment_invalid' => 'Formato del frammento non valido',
    'crypto_passphrase_required' => 'Frase segreta richiesta',
    'crypto_passphrase_incorrect' => 'Frase segreta errata o dati alterati',
    'crypto_decryption_failed' => 'Decrittazione fallita. La chiave potrebbe essere errata.',
    'crypto_decryption_error' => 'Si è verificato un errore durante la decrittazione',
    'crypto_file_download_failed' => 'Impossibile scaricare il file crittografato',
    'crypto_clipboard_failed' => 'Impossibile copiare negli appunti',
    'crypto_enter_secret' => 'Per favore inserisci un segreto',
    'crypto_select_file' => 'Per favore seleziona un file',
    'crypto_creation_error' => 'Errore nella creazione del segreto',

    // Loading
    'loading' => 'Caricamento...',
    'loading_secret' => 'Caricamento segreto...',

    // Emails
    'email_secret_created_subject' => 'Il tuo segreto è stato creato',
    'email_secret_created_intro' => 'Hai creato un nuovo segreto. Usa il link qui sotto per gestirlo (visualizza stato, revoca, estendi).',
    'email_type' => 'Tipo',
    'email_expires' => 'Scade',
    'email_manage_secret' => 'Gestisci il mio segreto',
    'email_save_link_warning' => 'Salva questo link! È l\'unico modo per accedere all\'amministrazione del tuo segreto.',
    'email_link_label' => 'Oppure copia questo link:',
    'email_footer' => 'Questa email è stata inviata da :app.',
    'type_text' => 'Testo',
    'type_file' => 'File',

    // Footer
    'footer_manage' => 'Gestisci i miei segreti',
    'footer_legal' => 'Note legali',

    // Legal page
    'legal_title' => 'Note Legali',
    'legal_editor_title' => 'Editore del sito web',
    'legal_editor_text' => 'Questo sito web è pubblicato da :name.',
    'legal_hosting_title' => 'Hosting',
    'legal_hosting_text' => 'Questo sito web è ospitato da:',
    'legal_hosting_phone' => 'Telefono:',
    'legal_data_title' => 'Protezione dei dati',
    'legal_data_text' => 'Secret Drop è progettato con il principio "zero-knowledge". I segreti vengono crittografati nel tuo browser prima di essere inviati al server. Il server memorizza solo dati crittografati e non può accedere al contenuto in chiaro dei tuoi segreti.',
    'legal_data_stored' => 'Dati memorizzati:',
    'legal_data_item_ciphertext' => 'Dati crittografati (contenuto e parametri di crittografia)',
    'legal_data_item_metadata' => 'Metadati (data di creazione, scadenza, contatore letture)',
    'legal_data_item_file_meta' => 'Metadati file (nome originale, tipo, dimensione)',
    'legal_data_item_email' => 'Impronta dell\'email (se fornito, per accesso amministrativo)',
    'legal_data_not_stored' => 'Dati NON memorizzati:',
    'legal_data_not_item_plaintext' => 'Contenuto in chiaro dei segreti',
    'legal_data_not_item_key' => 'Chiavi di crittografia (trasmesse solo tramite la parte privata del link)',
    'legal_cookies_title' => 'Cookie',
    'legal_cookies_text' => 'Questo sito web utilizza solo cookie tecnici essenziali (sessione, preferenza tema). Non vengono utilizzati cookie di tracciamento o pubblicitari.',
    'legal_cookies_cnil' => 'In conformità con le raccomandazioni della CNIL, questi cookie strettamente necessari sono esenti dai requisiti di consenso.',
    'legal_contact_title' => 'Contatto',
    'legal_contact_text' => 'Per qualsiasi domanda su questo sito web, puoi contattarci a :email.',
    'legal_contact_prefix' => 'Per qualsiasi domanda su questo sito web, puoi contattarci a',

    // Admin
    'admin_title' => 'Gestisci i tuoi segreti',
    'admin_description' => 'Inserisci l\'email che hai usato quando hai creato i tuoi segreti per accedere al pannello di amministrazione.',
    'admin_email_placeholder' => 'tua@email.com',
    'admin_send_link' => 'Invia link magico',
    'admin_back_home' => 'Torna alla home',
    'admin_link_sent_title' => 'Controlla la tua posta',
    'admin_link_sent_description' => 'Se esistono segreti per questa email, è stato inviato un link magico.',
    'admin_link_sent_warning' => 'Il link è valido per 5 minuti e può essere usato una sola volta.',
    'admin_invalid_link_title' => 'Link non valido o scaduto',
    'admin_invalid_link_description' => 'Questo link magico non è valido o è già stato usato. Per favore richiedine uno nuovo.',
    'admin_not_found_title' => 'Segreto non trovato',
    'admin_not_found_description' => 'Questo segreto non esiste o è stato eliminato.',
    'admin_dashboard_title' => 'I miei segreti',
    'admin_secrets_count' => ':count segreto/i',
    'admin_logout' => 'Esci',
    'admin_no_secrets' => 'Nessun segreto trovato',
    'admin_no_secrets_description' => 'Non hai creato nessun segreto con questo indirizzo email.',
    'admin_status_active' => 'Attivo',
    'admin_status_expired' => 'Scaduto',
    'admin_status_revoked' => 'Revocato',
    'admin_status_consumed' => 'Consumato',
    'admin_created' => 'Creato',
    'admin_expires' => 'Scade',
    'admin_read_count' => 'Contatore letture',
    'admin_first_read' => 'Prima lettura',
    'admin_mode' => 'Modalità',
    'admin_limited_views' => ':count lettura/e max',
    'admin_unlimited' => 'Illimitato',
    'admin_day' => 'giorno',
    'admin_days' => 'giorni',
    'admin_extend' => 'Estendi',
    'admin_revoke' => 'Revoca',
    'admin_revoke_confirm' => 'Sei sicuro di voler revocare questo segreto? Questa azione è irreversibile.',

    // Magic link email
    'email_magic_link_subject' => 'Il tuo link di accesso a Secret Drop',
    'email_magic_link_intro' => 'Hai richiesto l\'accesso per gestire i tuoi segreti. Clicca il pulsante qui sotto per accedere.',
    'email_magic_link_button' => 'Accedi ai miei segreti',
    'email_magic_link_warning' => 'Questo link scade in 5 minuti e può essere usato una sola volta.',

    // Super Admin
    'superadmin_title' => 'Super Admin',
    'superadmin_description' => '',
    'email_superadmin_subject' => 'Accesso Super Admin - Secret Drop',
    'email_superadmin_intro' => 'È stata fatta una richiesta di accesso super admin. Clicca il pulsante qui sotto per accedere alla dashboard.',
    'email_superadmin_button' => 'Accedi alla dashboard',
    'superadmin_link_sent_description' => 'Se questa email è autorizzata, è stato inviato un link magico.',
    'superadmin_dashboard_title' => 'Statistiche di utilizzo',
    'superadmin_dashboard_subtitle' => 'Dati di utilizzo anonimi per la tua istanza di Secret Drop.',

    // Periods
    'period_7d' => 'Ultimi 7 giorni',
    'period_30d' => 'Ultimi 30 giorni',
    'period_90d' => 'Ultimi 90 giorni',
    'period_1y' => 'Ultimo anno',
    'period_all' => 'Tutto il tempo',

    // Stats
    'stat_secrets_created' => 'Segreti creati',
    'stat_secrets_read' => 'Segreti letti',
    'stat_files_shared' => 'File condivisi',
    'stat_volume' => 'Volume',
    'stat_current_disk_usage' => 'Utilizzo disco attuale',
    'stat_text' => 'Testo',
    'stat_file' => 'File',
    'stat_reads' => 'Letture',
    'stat_passphrase' => 'Con frase segreta',
    'stat_max_views' => 'Limite visualizzazioni massime',
    'stat_read' => 'Letto',
    'stat_expired_unread' => 'Scaduto non letto',
    'stat_revoked' => 'Revocato',
    'stat_max_reached' => 'Max visualizzazioni raggiunto',
    'stat_magic_links_requested' => 'Link magici richiesti',
    'stat_magic_links_used' => 'Link magici usati',
    'stat_secrets_extended' => 'Segreti estesi',

    // Charts
    'chart_secrets_created' => 'Segreti creati',
    'chart_secrets_read' => 'Segreti letti',
    'chart_secret_types' => 'Tipi di segreti',
    'chart_secret_options' => 'Opzioni usate',
    'chart_secret_outcomes' => 'Esiti dei segreti',
    'chart_admin_activity' => 'Attività amministrativa',
    'chart_heatmap_created' => 'Mappa di calore creazione (per giorno/ora)',
    'chart_heatmap_read' => 'Mappa di calore lettura (per giorno/ora)',

    // Stats
    'stat_avg_first_read' => 'Ritardo medio prima lettura',

    // Days
    'day_sunday' => 'Dom',
    'day_monday' => 'Lun',
    'day_tuesday' => 'Mar',
    'day_wednesday' => 'Mer',
    'day_thursday' => 'Gio',
    'day_friday' => 'Ven',
    'day_saturday' => 'Sab',

    // File size units
    'unit_bytes' => 'B',
    'unit_kilobytes' => 'KB',
    'unit_megabytes' => 'MB',
    'unit_gigabytes' => 'GB',

    // Accessibility
    'a11y_show_passphrase' => 'Mostra frase segreta',
    'a11y_hide_passphrase' => 'Nascondi frase segreta',
    'a11y_back' => 'Indietro',
    'a11y_period_selector' => 'Seleziona periodo',
    'a11y_extend_days' => 'Numero di giorni da estendere',
    'a11y_expand_secret' => 'Mostra dettagli segreto',

    // Split mode
    'split_mode' => 'Separa link e chiave',
    'split_mode_hint' => 'Invia il link e la chiave attraverso canali diversi per maggiore sicurezza',
    'split_mode_tooltip' => 'Se un canale viene compromesso, l\'attaccante ha solo una parte dell\'informazione.',
    'share_link_label' => 'Link di condivisione',
    'share_key_label' => 'Chiave di decrittazione',
    'split_mode_warning' => 'Invia la chiave attraverso un canale diverso (SMS, telefonata, di persona...).',
    'enter_key_manually' => 'Inserisci la chiave di decrittazione',
    'key_placeholder' => 'Chiave ricevuta separatamente',
    'btn_unlock' => 'Sblocca',

    // Stats
    'stat_split_mode' => 'Modalità separata',

    // Rate limiting & Captcha
    'rate_limit_exceeded' => 'Troppe richieste. Per favore risolvi il calcolo qui sotto per continuare.',
    'captcha_label' => 'Verifica anti-robot',
    'captcha_placeholder' => 'La tua risposta',
    'captcha_hint' => 'Risolvi: :challenge = ?',
    'captcha_invalid' => 'Risposta errata. Per favore riprova.',

    // Labels
    'label_important' => 'Importante:',
    'label_note' => 'Nota:',

    // Passphrase strength criteria
    'passphrase_min_length' => '12 caratteri min.',
    'passphrase_lowercase' => 'Minuscola',
    'passphrase_uppercase' => 'Maiuscola',
    'passphrase_digit' => 'Cifra',
    'passphrase_special' => 'Carattere speciale',
];
