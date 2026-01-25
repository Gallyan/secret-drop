<?php

return [
    // App
    'app_name' => 'Secret Drop',
    'app_description' => 'Comparte información sensible de forma segura con cifrado de extremo a extremo.',

    // Features
    'feature_encryption' => 'Cifrado AES-256-GCM en tu navegador',
    'feature_zero_knowledge' => 'El servidor nunca ve tus datos en texto plano',
    'feature_auto_destroy' => 'Auto-destrucción después de la lectura',
    'feature_expiration' => 'Expiración automática configurable',

    // Form labels
    'your_secret' => 'Tu secreto',
    'your_file' => 'Tu archivo',
    'expires_in' => 'Expira en',
    'max_reads' => 'Lecturas máximas',
    'advanced_options' => 'Opciones avanzadas',
    'passphrase' => 'Contraseña',
    'passphrase_placeholder' => 'Protección adicional',
    'your_email' => 'Tu correo electrónico',
    'email_placeholder' => 'tu@email.com',
    'email_hint' => 'Para gestionar tus secretos más tarde (revocar, extender)',

    // Form tabs
    'tab_text' => 'Texto',
    'tab_file' => 'Archivo',

    // Form placeholders
    'secret_placeholder' => 'Escribe tu mensaje confidencial...',
    'max_reads_placeholder' => 'Ilimitado',

    // Expiration options
    'expiration_1h' => '1 hora',
    'expiration_1d' => '1 día',
    'expiration_7d' => '7 días',
    'expiration_30d' => '30 días',
    'expiration_90d' => '90 días',

    // File upload
    'file_drop_click' => 'Haz clic para elegir',
    'file_drop_or_drag' => 'o arrastra un archivo',
    'file_max_size' => 'Máximo 100 MB',
    'file_too_large' => 'El archivo es demasiado grande (máx. 100 MB)',

    // Buttons
    'btn_encrypt' => 'Cifrar y crear enlace',
    'btn_encrypting' => 'Cifrando...',
    'btn_encrypting_upload' => 'Cifrando y subiendo...',
    'btn_copy' => 'Copiar',
    'btn_copied' => '¡Copiado!',
    'btn_decrypt' => 'Descifrar',
    'btn_decrypting' => 'Descifrando...',
    'btn_retry' => 'Reintentar',
    'btn_cancel' => 'Cancelar',
    'btn_download_again' => 'Descargar de nuevo',
    'btn_create_new' => 'Crear un nuevo secreto',

    // Success
    'secret_created' => 'Secreto creado',
    'share_link_instruction' => 'Comparte este enlace con tu destinatario',
    'warning_link_contains_key' => 'Este enlace contiene la clave de descifrado. Compártelo solo con el destinatario previsto.',
    'warning_passphrase_required' => 'El destinatario necesitará introducir la contraseña para descifrar el secreto.',
    'success_admin_hint' => 'Puedes gestionar este secreto (revocar, extender) a través del enlace ":link" en la parte inferior de la página.',

    // QR Code
    'show_qr_code' => 'Mostrar código QR',
    'hide_qr_code' => 'Ocultar código QR',
    'download_qr_code' => 'Descargar código QR',
    'qr_code_hint' => 'Escanea este código QR para abrir el enlace en otro dispositivo.',
    'qr_code_alt' => 'Código QR con el enlace para compartir',
    'qr_generation_failed' => 'Error al generar el código QR.',

    // View secret
    'secret_message' => 'Mensaje secreto',
    'secret_file' => 'Archivo secreto',
    'encrypted_end_to_end_message' => 'Este mensaje fue cifrado de extremo a extremo',
    'encrypted_end_to_end_file' => 'Este archivo fue cifrado de extremo a extremo',
    'passphrase_protected' => 'Este secreto está protegido por una contraseña.',
    'passphrase_input_placeholder' => 'Introduce la contraseña',
    'decrypting_message' => 'Descifrando...',
    'decrypting_file' => 'Descargando y descifrando...',
    'file_decrypted' => 'Archivo descifrado',
    'note_destroyed_text' => 'Este secreto fue configurado para destruirse después de la lectura. Ya no es accesible.',
    'note_destroyed_file' => 'Este archivo fue configurado para destruirse después de la lectura. Ya no es accesible en el servidor.',

    // Errors
    'error_not_found' => 'Secreto no encontrado',
    'error_unavailable' => 'Secreto no disponible',
    'error_generic' => 'Error',
    'secret_not_exist' => 'Este secreto no existe o puede haber sido eliminado.',
    'secret_expired' => 'Este secreto ha expirado y ya no es accesible.',
    'secret_revoked' => 'Este secreto fue revocado por su creador.',
    'secret_max_views' => 'Este secreto ha alcanzado su número máximo de visualizaciones y ya no es accesible.',
    'secret_unavailable_generic' => 'Este secreto ya no es accesible.',
    'error_loading' => 'Se produjo un error al cargar el secreto.',
    'error_connection' => 'No se puede cargar el secreto. Verifica tu conexión.',

    // Crypto errors
    'crypto_not_supported' => 'Tu navegador no soporta cifrado seguro',
    'crypto_key_missing' => 'Falta la clave de descifrado en la URL',
    'crypto_fragment_invalid' => 'Formato de fragmento inválido',
    'crypto_passphrase_required' => 'Se requiere contraseña',
    'crypto_passphrase_incorrect' => 'Contraseña incorrecta o datos corruptos',
    'crypto_decryption_failed' => 'El descifrado falló. La clave puede ser incorrecta.',
    'crypto_decryption_error' => 'Se produjo un error durante el descifrado',
    'crypto_file_download_failed' => 'No se puede descargar el archivo cifrado',
    'crypto_clipboard_failed' => 'No se puede copiar al portapapeles',
    'crypto_enter_secret' => 'Por favor, introduce un secreto',
    'crypto_select_file' => 'Por favor, selecciona un archivo',
    'crypto_creation_error' => 'Error al crear el secreto',

    // Loading
    'loading' => 'Cargando...',
    'loading_secret' => 'Cargando secreto...',

    // Emails
    'email_secret_created_subject' => 'Tu secreto ha sido creado',
    'email_secret_created_intro' => 'Has creado un nuevo secreto. Usa el enlace de abajo para gestionarlo (ver estado, revocar, extender).',
    'email_type' => 'Tipo',
    'email_expires' => 'Expira',
    'email_manage_secret' => 'Gestionar mi secreto',
    'email_save_link_warning' => '¡Guarda este enlace! Es la única forma de acceder a la administración de tu secreto.',
    'email_link_label' => 'O copia este enlace:',
    'email_footer' => 'Este correo fue enviado por :app.',
    'type_text' => 'Texto',
    'type_file' => 'Archivo',

    // Footer
    'footer_manage' => 'Gestionar mis secretos',
    'footer_legal' => 'Aviso legal',

    // Legal page
    'legal_title' => 'Aviso Legal',
    'legal_editor_title' => 'Editor del sitio web',
    'legal_editor_text' => 'Este sitio web es publicado por :name.',
    'legal_hosting_title' => 'Alojamiento',
    'legal_hosting_text' => 'Este sitio web está alojado por:',
    'legal_hosting_phone' => 'Teléfono:',
    'legal_data_title' => 'Protección de datos',
    'legal_data_text' => 'Secret Drop está diseñado con el principio de "conocimiento cero". Los secretos se cifran en tu navegador antes de enviarse al servidor. El servidor solo almacena datos cifrados y no puede acceder al contenido en texto plano de tus secretos.',
    'legal_data_stored' => 'Datos almacenados:',
    'legal_data_item_ciphertext' => 'Datos cifrados (texto cifrado, IV, salt)',
    'legal_data_item_metadata' => 'Metadatos (fecha de creación, expiración, contador de lecturas)',
    'legal_data_item_email' => 'Hash del correo electrónico (si se proporciona, para acceso de administración)',
    'legal_data_not_stored' => 'Datos NO almacenados:',
    'legal_data_not_item_plaintext' => 'Contenido en texto plano de los secretos',
    'legal_data_not_item_key' => 'Claves de cifrado (transmitidas solo a través del fragmento de URL)',
    'legal_cookies_title' => 'Cookies',
    'legal_cookies_text' => 'Este sitio web solo utiliza cookies técnicas esenciales (sesión, preferencia de tema). No se utilizan cookies de seguimiento ni publicidad.',
    'legal_cookies_cnil' => 'De acuerdo con las recomendaciones de la CNIL, estas cookies estrictamente necesarias están exentas de requisitos de consentimiento.',
    'legal_contact_title' => 'Contacto',
    'legal_contact_text' => 'Para cualquier pregunta sobre este sitio web, puedes contactarnos en :email.',

    // Admin
    'admin_title' => 'Gestiona tus secretos',
    'admin_description' => 'Introduce el correo electrónico que usaste al crear tus secretos para acceder al panel de administración.',
    'admin_email_placeholder' => 'tu@email.com',
    'admin_send_link' => 'Enviar enlace mágico',
    'admin_back_home' => 'Volver al inicio',
    'admin_link_sent_title' => 'Revisa tu bandeja de entrada',
    'admin_link_sent_description' => 'Si existen secretos para este correo, se ha enviado un enlace mágico.',
    'admin_link_sent_warning' => 'El enlace es válido durante 5 minutos y solo puede usarse una vez.',
    'admin_invalid_link_title' => 'Enlace inválido o expirado',
    'admin_invalid_link_description' => 'Este enlace mágico es inválido o ya ha sido usado. Por favor, solicita uno nuevo.',
    'admin_not_found_title' => 'Secreto no encontrado',
    'admin_not_found_description' => 'Este secreto no existe o ha sido eliminado.',
    'admin_dashboard_title' => 'Mis secretos',
    'admin_secrets_count' => ':count secreto(s)',
    'admin_logout' => 'Cerrar sesión',
    'admin_no_secrets' => 'No se encontraron secretos',
    'admin_no_secrets_description' => 'No has creado ningún secreto con esta dirección de correo.',
    'admin_status_active' => 'Activo',
    'admin_status_expired' => 'Expirado',
    'admin_status_revoked' => 'Revocado',
    'admin_status_consumed' => 'Consumido',
    'admin_created' => 'Creado',
    'admin_expires' => 'Expira',
    'admin_read_count' => 'Contador de lecturas',
    'admin_first_read' => 'Primera lectura',
    'admin_mode' => 'Modo',
    'admin_limited_views' => ':count lectura(s) máx',
    'admin_unlimited' => 'Ilimitado',
    'admin_day' => 'día',
    'admin_days' => 'días',
    'admin_extend' => 'Extender',
    'admin_revoke' => 'Revocar',
    'admin_revoke_confirm' => '¿Estás seguro de que quieres revocar este secreto? Esta acción es irreversible.',

    // Magic link email
    'email_magic_link_subject' => 'Tu enlace de acceso a Secret Drop',
    'email_magic_link_intro' => 'Has solicitado acceso para gestionar tus secretos. Haz clic en el botón de abajo para iniciar sesión.',
    'email_magic_link_button' => 'Acceder a mis secretos',
    'email_magic_link_warning' => 'Este enlace expira en 5 minutos y solo puede usarse una vez.',

    // Super Admin
    'superadmin_title' => 'Super Admin',
    'superadmin_description' => '',
    'email_superadmin_subject' => 'Acceso Super Admin - Secret Drop',
    'email_superadmin_intro' => 'Se ha solicitado un acceso de super administrador. Haz clic en el botón de abajo para acceder al panel.',
    'email_superadmin_button' => 'Acceder al panel',
    'superadmin_link_sent_description' => 'Si este correo está autorizado, se ha enviado un enlace mágico.',
    'superadmin_dashboard_title' => 'Estadísticas de uso',
    'superadmin_dashboard_subtitle' => 'Datos de uso anónimos para tu instancia de Secret Drop.',

    // Periods
    'period_7d' => 'Últimos 7 días',
    'period_30d' => 'Últimos 30 días',
    'period_90d' => 'Últimos 90 días',
    'period_1y' => 'Último año',
    'period_all' => 'Todo el tiempo',

    // Stats
    'stat_secrets_created' => 'Secretos creados',
    'stat_secrets_read' => 'Secretos leídos',
    'stat_files_shared' => 'Archivos compartidos',
    'stat_volume' => 'Volumen',
    'stat_current_disk_usage' => 'Uso de disco actual',
    'stat_text' => 'Texto',
    'stat_file' => 'Archivo',
    'stat_reads' => 'Lecturas',
    'stat_passphrase' => 'Con contraseña',
    'stat_max_views' => 'Límite de vistas máximas',
    'stat_read' => 'Leído',
    'stat_expired_unread' => 'Expirado sin leer',
    'stat_revoked' => 'Revocado',
    'stat_max_reached' => 'Máximo de vistas alcanzado',
    'stat_magic_links_requested' => 'Enlaces mágicos solicitados',
    'stat_magic_links_used' => 'Enlaces mágicos usados',
    'stat_secrets_extended' => 'Secretos extendidos',

    // Charts
    'chart_secrets_created' => 'Secretos creados',
    'chart_secrets_read' => 'Secretos leídos',
    'chart_secret_types' => 'Tipos de secretos',
    'chart_secret_options' => 'Opciones usadas',
    'chart_secret_outcomes' => 'Resultados de secretos',
    'chart_admin_activity' => 'Actividad de administración',
    'chart_heatmap_created' => 'Mapa de calor de creación (por día/hora)',
    'chart_heatmap_read' => 'Mapa de calor de lectura (por día/hora)',

    // Stats
    'stat_avg_first_read' => 'Retraso promedio de primera lectura',

    // Days
    'day_sunday' => 'Dom',
    'day_monday' => 'Lun',
    'day_tuesday' => 'Mar',
    'day_wednesday' => 'Mié',
    'day_thursday' => 'Jue',
    'day_friday' => 'Vie',
    'day_saturday' => 'Sáb',

    // File size units
    'unit_bytes' => 'B',
    'unit_kilobytes' => 'KB',
    'unit_megabytes' => 'MB',
    'unit_gigabytes' => 'GB',

    // Accessibility
    'a11y_back' => 'Volver',
    'a11y_period_selector' => 'Seleccionar período',
    'a11y_extend_days' => 'Número de días a extender',
    'a11y_expand_secret' => 'Mostrar detalles del secreto',

    // Split mode
    'split_mode' => 'Separar enlace y clave',
    'split_mode_hint' => 'Envía el enlace y la clave por canales diferentes para mayor seguridad',
    'share_link_label' => 'Enlace para compartir',
    'share_key_label' => 'Clave de descifrado',
    'split_mode_warning' => 'Envía la clave por un canal diferente (SMS, llamada telefónica, en persona...).',
    'enter_key_manually' => 'Introduce la clave de descifrado',
    'key_placeholder' => 'Clave recibida por separado',
    'btn_unlock' => 'Desbloquear',

    // Stats
    'stat_split_mode' => 'Modo separado',

    // Rate limiting & Captcha
    'rate_limit_exceeded' => 'Demasiadas solicitudes. Por favor, resuelve el cálculo de abajo para continuar.',
    'captcha_label' => 'Verificación anti-robot',
    'captcha_placeholder' => 'Tu respuesta',
    'captcha_hint' => 'Resuelve: :challenge = ?',
    'captcha_invalid' => 'Respuesta incorrecta. Por favor, inténtalo de nuevo.',

    // Labels
    'label_important' => 'Importante:',
    'label_note' => 'Nota:',

    // Passphrase strength criteria
    'passphrase_min_length' => '12 caracteres mín.',
    'passphrase_lowercase' => 'Minúscula',
    'passphrase_uppercase' => 'Mayúscula',
    'passphrase_digit' => 'Dígito',
    'passphrase_special' => 'Carácter especial',
];
