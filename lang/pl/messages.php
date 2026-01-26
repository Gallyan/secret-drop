<?php

return [
    // App
    'app_name' => 'Secret Drop',
    'app_description' => 'Bezpiecznie udostępniaj poufne informacje z szyfrowaniem end-to-end.',

    // Features
    'feature_encryption' => 'Szyfrowanie AES-256-GCM w przeglądarce',
    'feature_zero_knowledge' => 'Serwer nigdy nie widzi Twoich danych jawnych',
    'feature_auto_destroy' => 'Automatyczne zniszczenie po odczytaniu',
    'feature_expiration' => 'Konfigurowalny termin wygaśnięcia',

    // Form labels
    'your_secret' => 'Twój sekret',
    'your_file' => 'Twój plik',
    'expires_in' => 'Wygasa za',
    'expires_in_hint' => 'Sekret zostanie automatycznie usunięty po tym czasie, nawet jeśli nie został odczytany.',
    'max_reads' => 'Maks. odczytów',
    'max_reads_hint' => 'Ogranicza ile razy sekret może być przeglądany. Po osiągnięciu limitu jest automatycznie usuwany.',
    'destroy_after_read' => 'Zniszcz po odczytaniu',
    'advanced_options' => 'Opcje zaawansowane',
    'passphrase' => 'Hasło',
    'passphrase_hint' => 'Dodaje warstwę bezpieczeństwa. Odbiorca musi je znać. Nigdy nie jest wysyłane na serwer.',
    'passphrase_placeholder' => 'Dodatkowa ochrona',
    'your_email' => 'Twój e-mail',
    'email_placeholder' => 'twoj@email.pl',
    'email_hint' => 'Aby zarządzać sekretami później (unieważnić, przedłużyć)',

    // Form tabs
    'tab_text' => 'Tekst',
    'tab_file' => 'Plik',

    // Form placeholders
    'secret_placeholder' => 'Wprowadź poufną wiadomość...',
    'max_reads_placeholder' => 'Bez limitu',

    // Expiration options
    'expiration_1h' => '1 godzina',
    'expiration_1d' => '1 dzień',
    'expiration_7d' => '7 dni',
    'expiration_30d' => '30 dni',

    // File upload
    'file_drop_click' => 'Kliknij, aby wybrać',
    'file_drop_or_drag' => 'lub przeciągnij plik',
    'file_max_size' => 'Maksymalnie 100 MB',
    'file_too_large' => 'Plik jest za duży (maks. 100 MB)',

    // Buttons
    'btn_encrypt' => 'Zaszyfruj i utwórz link',
    'btn_encrypting' => 'Szyfrowanie...',
    'btn_encrypting_upload' => 'Szyfrowanie i przesyłanie...',
    'btn_copy' => 'Kopiuj',
    'btn_copied' => 'Skopiowano!',
    'btn_decrypt' => 'Odszyfruj',
    'btn_decrypting' => 'Odszyfrowywanie...',
    'btn_retry' => 'Spróbuj ponownie',
    'btn_cancel' => 'Anuluj',
    'btn_download_again' => 'Pobierz ponownie',
    'btn_create_new' => 'Utwórz nowy sekret',

    // Success
    'secret_created' => 'Sekret utworzony',
    'share_link_instruction' => 'Udostępnij ten link odbiorcy',
    'warning_link_contains_key' => 'Ten link zawiera klucz deszyfrujący. Udostępnij tylko zamierzonemu odbiorcy.',
    'warning_passphrase_required' => 'Odbiorca będzie musiał podać hasło, aby odszyfrować sekret.',
    'success_admin_hint' => 'Możesz zarządzać tym sekretem (unieważnić, przedłużyć) przez link ":link" na dole strony.',

    // QR Code
    'show_qr_code' => 'Pokaż kod QR',
    'hide_qr_code' => 'Ukryj kod QR',
    'download_qr_code' => 'Pobierz kod QR',
    'qr_code_hint' => 'Zeskanuj ten kod QR, aby otworzyć link na innym urządzeniu.',
    'qr_code_alt' => 'Kod QR z linkiem do udostępnienia',
    'qr_generation_failed' => 'Nie udało się wygenerować kodu QR.',

    // View secret
    'secret_message' => 'Tajna wiadomość',
    'secret_file' => 'Tajny plik',
    'encrypted_end_to_end_message' => 'Ta wiadomość została zaszyfrowana end-to-end',
    'encrypted_end_to_end_file' => 'Ten plik został zaszyfrowany end-to-end',
    'passphrase_protected' => 'Ten sekret jest chroniony hasłem.',
    'passphrase_input_placeholder' => 'Wprowadź hasło',
    'decrypting_message' => 'Odszyfrowywanie...',
    'decrypting_file' => 'Pobieranie i odszyfrowywanie...',
    'file_decrypted' => 'Plik odszyfrowany',
    'note_destroyed_text' => 'Ten sekret został skonfigurowany do zniszczenia po odczytaniu. Nie jest już dostępny.',
    'note_destroyed_file' => 'Ten plik został skonfigurowany do zniszczenia po odczytaniu. Nie jest już dostępny na serwerze.',

    // Errors
    'error_not_found' => 'Sekret nie znaleziony',
    'error_unavailable' => 'Sekret niedostępny',
    'error_generic' => 'Błąd',
    'secret_not_exist' => 'Ten sekret nie istnieje lub został usunięty.',
    'secret_expired' => 'Ten sekret wygasł i nie jest już dostępny.',
    'secret_revoked' => 'Ten sekret został unieważniony przez twórcę.',
    'secret_max_views' => 'Ten sekret osiągnął maksymalną liczbę wyświetleń i nie jest już dostępny.',
    'secret_unavailable_generic' => 'Ten sekret nie jest już dostępny.',
    'error_loading' => 'Wystąpił błąd podczas ładowania sekretu.',
    'error_connection' => 'Nie można załadować sekretu. Sprawdź połączenie.',

    // Crypto errors
    'crypto_not_supported' => 'Twoja przeglądarka nie obsługuje bezpiecznego szyfrowania',
    'crypto_key_missing' => 'Brak klucza deszyfrującego w URL',
    'crypto_fragment_invalid' => 'Nieprawidłowy format fragmentu',
    'crypto_passphrase_required' => 'Hasło jest wymagane',
    'crypto_passphrase_incorrect' => 'Nieprawidłowe hasło lub uszkodzone dane',
    'crypto_decryption_failed' => 'Deszyfrowanie nie powiodło się. Klucz może być nieprawidłowy.',
    'crypto_decryption_error' => 'Wystąpił błąd podczas deszyfrowania',
    'crypto_file_download_failed' => 'Nie można pobrać zaszyfrowanego pliku',
    'crypto_clipboard_failed' => 'Nie można skopiować do schowka',
    'crypto_enter_secret' => 'Proszę wprowadzić sekret',
    'crypto_select_file' => 'Proszę wybrać plik',
    'crypto_creation_error' => 'Błąd podczas tworzenia sekretu',

    // Loading
    'loading' => 'Ładowanie...',
    'loading_secret' => 'Ładowanie sekretu...',

    // Emails
    'email_secret_created_subject' => 'Twój sekret został utworzony',
    'email_secret_created_intro' => 'Utworzyłeś nowy sekret. Użyj poniższego linku, aby nim zarządzać (sprawdzić status, unieważnić, przedłużyć).',
    'email_type' => 'Typ',
    'email_expires' => 'Wygasa',
    'email_manage_secret' => 'Zarządzaj moim sekretem',
    'email_save_link_warning' => 'Zapisz ten link! To jedyny sposób na dostęp do administracji sekretem.',
    'email_link_label' => 'Lub skopiuj ten link:',
    'email_footer' => 'Ten e-mail został wysłany przez :app.',
    'type_text' => 'Tekst',
    'type_file' => 'Plik',

    // Footer
    'footer_manage' => 'Zarządzaj moimi sekretami',
    'footer_legal' => 'Informacje prawne',

    // Legal page
    'legal_title' => 'Informacje Prawne',
    'legal_editor_title' => 'Wydawca strony',
    'legal_editor_text' => 'Ta strona jest wydawana przez :name.',
    'legal_hosting_title' => 'Hosting',
    'legal_hosting_text' => 'Ta strona jest hostowana przez:',
    'legal_hosting_phone' => 'Telefon:',
    'legal_data_title' => 'Ochrona danych',
    'legal_data_text' => 'Secret Drop jest zaprojektowany zgodnie z zasadą "zero-knowledge". Sekrety są szyfrowane w przeglądarce przed wysłaniem na serwer. Serwer przechowuje tylko zaszyfrowane dane i nie ma dostępu do jawnej treści sekretów.',
    'legal_data_stored' => 'Przechowywane dane:',
    'legal_data_item_ciphertext' => 'Zaszyfrowane dane (ciphertext, IV, salt)',
    'legal_data_item_metadata' => 'Metadane (data utworzenia, wygaśnięcia, liczba odczytów)',
    'legal_data_item_file_meta' => 'Metadane pliku (oryginalna nazwa, typ, rozmiar)',
    'legal_data_item_email' => 'Hash e-maila (jeśli podano, dla dostępu administracyjnego)',
    'legal_data_not_stored' => 'Dane NIE przechowywane:',
    'legal_data_not_item_plaintext' => 'Jawna treść sekretów',
    'legal_data_not_item_key' => 'Klucze szyfrujące (przesyłane tylko przez fragment URL)',
    'legal_cookies_title' => 'Pliki cookie',
    'legal_cookies_text' => 'Ta strona używa tylko niezbędnych technicznych plików cookie (sesja, preferencje motywu). Nie używamy plików cookie śledzących ani reklamowych.',
    'legal_cookies_cnil' => 'Zgodnie z zaleceniami CNIL, te ściśle niezbędne pliki cookie są zwolnione z wymogów zgody.',
    'legal_contact_title' => 'Kontakt',
    'legal_contact_text' => 'W przypadku pytań dotyczących tej strony, skontaktuj się z nami pod adresem :email.',
    'legal_contact_prefix' => 'W przypadku pytań dotyczących tej strony, skontaktuj się z nami pod adresem',

    // Admin
    'admin_title' => 'Zarządzaj swoimi sekretami',
    'admin_description' => 'Wprowadź e-mail użyty podczas tworzenia sekretów, aby uzyskać dostęp do panelu administracyjnego.',
    'admin_email_placeholder' => 'twoj@email.pl',
    'admin_send_link' => 'Wyślij magiczny link',
    'admin_back_home' => 'Powrót na stronę główną',
    'admin_link_sent_title' => 'Sprawdź swoją skrzynkę',
    'admin_link_sent_description' => 'Jeśli istnieją sekrety dla tego e-maila, wysłano magiczny link.',
    'admin_link_sent_warning' => 'Link jest ważny przez 5 minut i może być użyty tylko raz.',
    'admin_invalid_link_title' => 'Nieprawidłowy lub wygasły link',
    'admin_invalid_link_description' => 'Ten magiczny link jest nieprawidłowy lub już został użyty. Poproś o nowy.',
    'admin_not_found_title' => 'Sekret nie znaleziony',
    'admin_not_found_description' => 'Ten sekret nie istnieje lub został usunięty.',
    'admin_dashboard_title' => 'Moje sekrety',
    'admin_secrets_count' => ':count sekret(ów)',
    'admin_logout' => 'Wyloguj',
    'admin_no_secrets' => 'Nie znaleziono sekretów',
    'admin_no_secrets_description' => 'Nie utworzyłeś żadnych sekretów z tym adresem e-mail.',
    'admin_status_active' => 'Aktywny',
    'admin_status_expired' => 'Wygasły',
    'admin_status_revoked' => 'Unieważniony',
    'admin_status_consumed' => 'Wykorzystany',
    'admin_created' => 'Utworzono',
    'admin_expires' => 'Wygasa',
    'admin_read_count' => 'Liczba odczytów',
    'admin_first_read' => 'Pierwszy odczyt',
    'admin_mode' => 'Tryb',
    'admin_single_use' => 'Jednorazowy',
    'admin_multi_use' => 'Wielokrotny',
    'admin_day' => 'dzień',
    'admin_days' => 'dni',
    'admin_extend' => 'Przedłuż',
    'admin_revoke' => 'Unieważnij',
    'admin_revoke_confirm' => 'Czy na pewno chcesz unieważnić ten sekret? Ta akcja jest nieodwracalna.',

    // Magic link email
    'email_magic_link_subject' => 'Twój link dostępu do Secret Drop',
    'email_magic_link_intro' => 'Poprosiłeś o dostęp do zarządzania sekretami. Kliknij poniższy przycisk, aby się zalogować.',
    'email_magic_link_button' => 'Otwórz moje sekrety',
    'email_magic_link_warning' => 'Ten link wygasa za 5 minut i może być użyty tylko raz.',

    // Super Admin
    'superadmin_title' => 'Super Admin',
    'superadmin_description' => '',
    'email_superadmin_subject' => 'Dostęp Super Admin - Secret Drop',
    'email_superadmin_intro' => 'Złożono prośbę o dostęp super admin. Kliknij poniższy przycisk, aby otworzyć panel.',
    'email_superadmin_button' => 'Otwórz panel',
    'superadmin_link_sent_description' => 'Jeśli ten e-mail jest autoryzowany, wysłano magiczny link.',
    'superadmin_dashboard_title' => 'Statystyki użycia',
    'superadmin_dashboard_subtitle' => 'Anonimowe dane użycia Twojej instancji Secret Drop.',

    // Periods
    'period_7d' => 'Ostatnie 7 dni',
    'period_30d' => 'Ostatnie 30 dni',
    'period_90d' => 'Ostatnie 90 dni',
    'period_1y' => 'Ostatni rok',
    'period_all' => 'Cały czas',

    // Stats
    'stat_secrets_created' => 'Utworzone sekrety',
    'stat_secrets_read' => 'Odczytane sekrety',
    'stat_files_shared' => 'Udostępnione pliki',
    'stat_volume' => 'Objętość',
    'stat_current_disk_usage' => 'Bieżące użycie dysku',
    'stat_text' => 'Tekst',
    'stat_file' => 'Plik',
    'stat_reads' => 'Odczyty',
    'stat_passphrase' => 'Z hasłem',
    'stat_single_use' => 'Jednorazowe',
    'stat_max_views' => 'Limit wyświetleń',
    'stat_read' => 'Odczytane',
    'stat_expired_unread' => 'Wygasłe nieodczytane',
    'stat_revoked' => 'Unieważnione',
    'stat_max_reached' => 'Osiągnięto max wyświetleń',
    'stat_magic_links_requested' => 'Żądane magiczne linki',
    'stat_magic_links_used' => 'Użyte magiczne linki',
    'stat_secrets_extended' => 'Przedłużone sekrety',

    // Charts
    'chart_secrets_created' => 'Utworzone sekrety',
    'chart_secrets_read' => 'Odczytane sekrety',
    'chart_secret_types' => 'Typy sekretów',
    'chart_secret_options' => 'Użyte opcje',
    'chart_secret_outcomes' => 'Wyniki sekretów',
    'chart_admin_activity' => 'Aktywność administracyjna',
    'chart_heatmap_created' => 'Mapa ciepła tworzenia (wg dnia/godziny)',
    'chart_heatmap_read' => 'Mapa ciepła odczytów (wg dnia/godziny)',

    // Stats
    'stat_avg_first_read' => 'Śr. czas do pierwszego odczytu',

    // Days
    'day_sunday' => 'Nd',
    'day_monday' => 'Pn',
    'day_tuesday' => 'Wt',
    'day_wednesday' => 'Śr',
    'day_thursday' => 'Cz',
    'day_friday' => 'Pt',
    'day_saturday' => 'Sb',

    // File size units
    'unit_bytes' => 'B',
    'unit_kilobytes' => 'KB',
    'unit_megabytes' => 'MB',
    'unit_gigabytes' => 'GB',

    // Accessibility
    'a11y_show_passphrase' => 'Pokaż hasło',
    'a11y_hide_passphrase' => 'Ukryj hasło',
    'a11y_back' => 'Wstecz',
    'a11y_period_selector' => 'Wybierz okres',
    'a11y_extend_days' => 'Liczba dni do przedłużenia',
    'a11y_expand_secret' => 'Pokaż szczegóły sekretu',

    // Split mode
    'split_mode' => 'Rozdziel link i klucz',
    'split_mode_hint' => 'Wyślij link i klucz różnymi kanałami dla lepszego bezpieczeństwa',
    'split_mode_tooltip' => 'Jeśli jeden kanał zostanie skompromitowany, atakujący ma tylko część informacji.',
    'share_link_label' => 'Link do udostępnienia',
    'share_key_label' => 'Klucz deszyfrujący',
    'split_mode_warning' => 'Wyślij klucz innym kanałem (SMS, telefon, osobiście...).',
    'enter_key_manually' => 'Wprowadź klucz deszyfrujący',
    'key_placeholder' => 'Klucz otrzymany osobno',
    'btn_unlock' => 'Odblokuj',

    // Stats
    'stat_split_mode' => 'Tryb rozdzielony',

    // Rate limiting & Captcha
    'rate_limit_exceeded' => 'Zbyt wiele żądań. Rozwiąż poniższe działanie, aby kontynuować.',
    'captcha_label' => 'Weryfikacja anty-robot',
    'captcha_placeholder' => 'Twoja odpowiedź',
    'captcha_hint' => 'Oblicz: :challenge = ?',
    'captcha_invalid' => 'Nieprawidłowa odpowiedź. Spróbuj ponownie.',

    // Labels
    'label_important' => 'Ważne:',
    'label_note' => 'Uwaga:',

    // Passphrase strength criteria
    'passphrase_min_length' => '12 znaków min.',
    'passphrase_lowercase' => 'Mała litera',
    'passphrase_uppercase' => 'Wielka litera',
    'passphrase_digit' => 'Cyfra',
    'passphrase_special' => 'Znak specjalny',
];
