<?php

return [
    // App
    'app_name' => 'Secret Drop',
    'app_description' => 'Compartilhe informações sensíveis com segurança usando criptografia de ponta a ponta.',

    // Features
    'feature_encryption' => 'Criptografia de nível militar no seu navegador',
    'feature_zero_knowledge' => 'O servidor nunca vê seus dados em texto claro',
    'feature_auto_destroy' => 'Auto-destruição após leitura',
    'feature_expiration' => 'Expiração automática configurável',

    // Form labels
    'your_secret' => 'Seu segredo',
    'your_file' => 'Seu arquivo',
    'expires_in' => 'Expira em',
    'expires_in_hint' => 'O segredo será automaticamente excluído após este tempo, mesmo se não for lido.',
    'max_reads' => 'Máximo de leituras',
    'max_reads_hint' => 'Limita quantas vezes o segredo pode ser visualizado. Ao atingir, é automaticamente excluído.',
    'advanced_options' => 'Opções avançadas',
    'passphrase' => 'Frase secreta',
    'passphrase_hint' => 'Proteção adicional. O destinatário deve conhecê-la. Nunca é enviada ao servidor.',
    'passphrase_placeholder' => 'Proteção adicional',
    'your_email' => 'Seu e-mail',
    'email_placeholder' => 'seu@email.com',
    'email_hint' => 'Para gerenciar seus segredos depois (revogar, estender)',

    // Form tabs
    'tab_text' => 'Texto',
    'tab_file' => 'Arquivo',

    // Form placeholders
    'secret_placeholder' => 'Digite sua mensagem confidencial...',
    'max_reads_placeholder' => 'Ilimitado',

    // Expiration options
    'expiration_1h' => '1 hora',
    'expiration_1d' => '1 dia',
    'expiration_7d' => '7 dias',
    'expiration_30d' => '30 dias',
    'expiration_90d' => '90 dias',

    // File upload
    'file_drop_click' => 'Clique para escolher',
    'file_drop_or_drag' => 'ou arraste um arquivo',
    'file_max_size' => 'Máximo 100 MB',
    'file_too_large' => 'Arquivo muito grande (máx. 100 MB)',

    // Buttons
    'btn_encrypt' => 'Criptografar e criar link',
    'btn_encrypting' => 'Criptografando...',
    'btn_encrypting_upload' => 'Criptografando e enviando...',
    'btn_copy' => 'Copiar',
    'btn_copied' => 'Copiado!',
    'btn_decrypt' => 'Descriptografar',
    'btn_decrypting' => 'Descriptografando...',
    'btn_retry' => 'Tentar novamente',
    'btn_cancel' => 'Cancelar',
    'btn_create_new' => 'Criar um novo segredo',

    // Success
    'secret_created' => 'Segredo criado',
    'share_link_instruction' => 'Compartilhe este link com seu destinatário',
    'warning_link_contains_key' => 'Este link contém a chave de descriptografia. Compartilhe apenas com o destinatário pretendido.',
    'warning_passphrase_required' => 'O destinatário precisará inserir a frase secreta para descriptografar o segredo.',
    'success_admin_hint' => 'Você pode gerenciar este segredo (revogar, estender) pelo link ":link" no final da página.',

    // QR Code
    'show_qr_code' => 'Mostrar QR code',
    'hide_qr_code' => 'Ocultar QR code',
    'download_qr_code' => 'Baixar QR code',
    'qr_code_hint' => 'Escaneie este QR code para abrir o link de compartilhamento em outro dispositivo.',
    'qr_code_alt' => 'QR code contendo o link de compartilhamento',
    'qr_generation_failed' => 'Falha ao gerar o QR code.',

    // View secret
    'secret_message' => 'Mensagem secreta',
    'secret_file' => 'Arquivo secreto',
    'encrypted_end_to_end_message' => 'Esta mensagem foi criptografada de ponta a ponta',
    'encrypted_end_to_end_file' => 'Este arquivo foi criptografado de ponta a ponta',
    'passphrase_protected' => 'Este segredo é protegido por uma frase secreta.',
    'passphrase_input_placeholder' => 'Digite a frase secreta',
    'decrypting_message' => 'Descriptografando...',
    'decrypting_file' => 'Baixando e descriptografando...',
    'file_decrypted' => 'Arquivo descriptografado',
    'file_encrypted_info' => 'Arquivo criptografado',
    'note_destroyed_text' => 'Este segredo foi configurado para ser destruído após a leitura. Não está mais acessível.',
    'note_destroyed_file' => 'Este arquivo foi configurado para ser destruído após a leitura. Não está mais acessível no servidor.',
    'last_read_warning_title' => 'Esta é a última leitura disponível',
    'last_read_warning_text' => 'Uma vez exibido, este segredo será excluído permanentemente. Certifique-se de estar pronto para visualizá-lo.',
    'last_read_warning_short' => 'Esta é a última leitura disponível. O segredo será excluído permanentemente após a visualização.',
    'btn_reveal_secret' => 'Revelar o segredo',

    // Errors
    'error_not_found' => 'Segredo não encontrado',
    'error_unavailable' => 'Segredo indisponível',
    'error_generic' => 'Erro',
    'secret_not_exist' => 'Este segredo não existe ou pode ter sido excluído.',
    'secret_expired' => 'Este segredo expirou e não está mais acessível.',
    'secret_revoked' => 'Este segredo foi revogado pelo seu criador.',
    'secret_max_views' => 'Este segredo atingiu o número máximo de visualizações e não está mais acessível.',
    'secret_unavailable_generic' => 'Este segredo não está mais acessível.',
    'error_loading' => 'Ocorreu um erro ao carregar o segredo.',
    'error_connection' => 'Não foi possível carregar o segredo. Verifique sua conexão.',

    // Crypto errors
    'crypto_not_supported' => 'Seu navegador não suporta criptografia segura',
    'crypto_key_missing' => 'Chave de descriptografia ausente na URL',
    'crypto_fragment_invalid' => 'Formato de fragmento inválido',
    'crypto_passphrase_required' => 'Frase secreta é obrigatória',
    'crypto_passphrase_incorrect' => 'Frase secreta incorreta ou dados alterados',
    'crypto_decryption_failed' => 'Falha na descriptografia. A chave pode estar incorreta.',
    'crypto_decryption_error' => 'Ocorreu um erro durante a descriptografia',
    'crypto_file_download_failed' => 'Não foi possível baixar o arquivo criptografado',
    'crypto_clipboard_failed' => 'Não foi possível copiar para a área de transferência',
    'crypto_enter_secret' => 'Por favor, digite um segredo',
    'crypto_select_file' => 'Por favor, selecione um arquivo',
    'crypto_creation_error' => 'Erro ao criar segredo',

    // Loading
    'loading' => 'Carregando...',
    'loading_secret' => 'Carregando segredo...',

    // Emails
    'email_secret_created_subject' => 'Seu segredo foi criado',
    'email_secret_created_intro' => 'Você criou um novo segredo. Use o link abaixo para gerenciá-lo (ver status, revogar, estender).',
    'email_type' => 'Tipo',
    'email_expires' => 'Expira',
    'email_manage_secret' => 'Gerenciar meu segredo',
    'email_save_link_warning' => 'Guarde este link! É a única forma de acessar a administração do seu segredo.',
    'email_link_label' => 'Ou copie este link:',
    'email_footer' => 'Este e-mail foi enviado por :app.',
    'type_text' => 'Texto',
    'type_file' => 'Arquivo',

    // Footer
    'footer_manage' => 'Gerenciar meus segredos',
    'footer_legal' => 'Aviso legal',

    // Legal page
    'legal_title' => 'Aviso Legal',
    'legal_editor_title' => 'Editor do Site',
    'legal_editor_text' => 'Este site é publicado por :name.',
    'legal_hosting_title' => 'Hospedagem',
    'legal_hosting_text' => 'Este site é hospedado por:',
    'legal_hosting_phone' => 'Telefone:',
    'legal_data_title' => 'Proteção de Dados',
    'legal_data_text' => 'Secret Drop é projetado com o princípio de "conhecimento zero". Os segredos são criptografados no seu navegador antes de serem enviados ao servidor. O servidor armazena apenas dados criptografados e não pode acessar o conteúdo em texto claro dos seus segredos.',
    'legal_data_stored' => 'Dados armazenados:',
    'legal_data_item_ciphertext' => 'Dados criptografados (conteúdo e parâmetros de criptografia)',
    'legal_data_item_metadata' => 'Metadados (data de criação, expiração, contagem de leituras)',
    'legal_data_item_file_meta' => 'Tamanho do arquivo criptografado (para estatísticas)',
    'legal_data_item_email' => 'Impressão digital do e-mail (se fornecido, para acesso administrativo)',
    'legal_data_not_stored' => 'Dados NÃO armazenados:',
    'legal_data_not_item_plaintext' => 'Conteúdo em texto claro dos segredos',
    'legal_data_not_item_key' => 'Chaves de criptografia (transmitidas apenas via parte privada do link)',
    'legal_data_not_item_file_meta' => 'Nome, tipo e tamanho do arquivo (criptografados com o conteúdo)',
    'legal_cookies_title' => 'Cookies',
    'legal_cookies_text' => 'Este site usa apenas cookies técnicos essenciais (sessão, preferência de tema). Nenhum cookie de rastreamento ou publicidade é usado.',
    'legal_cookies_cnil' => 'De acordo com as recomendações da CNIL, estes cookies estritamente necessários estão isentos de requisitos de consentimento.',
    'legal_contact_title' => 'Contato',
    'legal_contact_text' => 'Para qualquer dúvida sobre este site, você pode nos contatar em :email.',
    'legal_contact_prefix' => 'Para qualquer dúvida sobre este site, você pode nos contatar em',

    // Admin
    'admin_title' => 'Gerenciar seus segredos',
    'admin_description' => 'Digite o e-mail que você usou ao criar seus segredos para acessar o painel de administração.',
    'admin_email_placeholder' => 'seu@email.com',
    'admin_send_link' => 'Enviar link mágico',
    'admin_back_home' => 'Voltar ao início',
    'admin_link_sent_title' => 'Verifique sua caixa de entrada',
    'admin_link_sent_description' => 'Se existirem segredos para este e-mail, um link mágico foi enviado.',
    'admin_link_sent_warning' => 'O link é válido por 5 minutos e só pode ser usado uma vez.',
    'admin_invalid_link_title' => 'Link inválido ou expirado',
    'admin_invalid_link_description' => 'Este link mágico é inválido ou já foi usado. Por favor, solicite um novo.',
    'admin_not_found_title' => 'Segredo não encontrado',
    'admin_not_found_description' => 'Este segredo não existe ou foi excluído.',
    'admin_dashboard_title' => 'Meus segredos',
    'admin_secrets_count' => ':count segredo(s)',
    'admin_logout' => 'Sair',
    'admin_no_secrets' => 'Nenhum segredo encontrado',
    'admin_no_secrets_description' => 'Você não criou nenhum segredo com este endereço de e-mail.',
    'admin_status_active' => 'Ativo',
    'admin_status_expired' => 'Expirado',
    'admin_status_revoked' => 'Revogado',
    'admin_status_consumed' => 'Consumido',
    'admin_created' => 'Criado',
    'admin_expires' => 'Expira',
    'admin_read_count' => 'Contagem de leituras',
    'admin_first_read' => 'Primeira leitura',
    'admin_mode' => 'Modo',
    'admin_limited_views' => ':count visualização(ões) máx',
    'admin_unlimited' => 'Ilimitado',
    'admin_day' => 'dia',
    'admin_days' => 'dias',
    'admin_extend' => 'Estender',
    'admin_revoke' => 'Revogar',
    'admin_revoke_confirm' => 'Tem certeza que deseja revogar este segredo? Esta ação é irreversível.',

    // Magic link email
    'email_magic_link_subject' => 'Seu link de acesso ao Secret Drop',
    'email_magic_link_intro' => 'Você solicitou acesso para gerenciar seus segredos. Clique no botão abaixo para entrar.',
    'email_magic_link_button' => 'Acessar meus segredos',
    'email_magic_link_warning' => 'Este link expira em 5 minutos e só pode ser usado uma vez.',

    // Super Admin
    'superadmin_title' => 'Super Admin',
    'superadmin_description' => '',
    'email_superadmin_subject' => 'Acesso Super Admin - Secret Drop',
    'email_superadmin_intro' => 'Uma solicitação de acesso super admin foi feita. Clique no botão abaixo para acessar o painel.',
    'email_superadmin_button' => 'Acessar painel',
    'superadmin_link_sent_description' => 'Se este e-mail for autorizado, um link mágico foi enviado.',
    'superadmin_dashboard_title' => 'Estatísticas de Uso',
    'superadmin_dashboard_subtitle' => 'Dados de uso anônimos da sua instância Secret Drop.',

    // Periods
    'period_7d' => 'Últimos 7 dias',
    'period_30d' => 'Últimos 30 dias',
    'period_90d' => 'Últimos 90 dias',
    'period_1y' => 'Último ano',
    'period_all' => 'Todo o período',

    // Stats
    'stat_secrets_created' => 'Segredos criados',
    'stat_secrets_read' => 'Segredos lidos',
    'stat_files_shared' => 'Arquivos compartilhados',
    'stat_volume' => 'Volume',
    'stat_current_disk_usage' => 'Uso atual do disco',
    'stat_text' => 'Texto',
    'stat_file' => 'Arquivo',
    'stat_reads' => 'Leituras',
    'stat_passphrase' => 'Com frase secreta',
    'stat_max_views' => 'Limite de visualizações',
    'stat_read' => 'Lido',
    'stat_expired_unread' => 'Expirado não lido',
    'stat_revoked' => 'Revogado',
    'stat_max_reached' => 'Máximo atingido',
    'stat_magic_links_requested' => 'Links mágicos solicitados',
    'stat_magic_links_used' => 'Links mágicos usados',
    'stat_secrets_extended' => 'Segredos estendidos',

    // Charts
    'chart_secrets_created' => 'Segredos criados',
    'chart_secrets_read' => 'Segredos lidos',
    'chart_secret_types' => 'Tipos de segredo',
    'chart_secret_options' => 'Opções usadas',
    'chart_secret_outcomes' => 'Resultados dos segredos',
    'chart_admin_activity' => 'Atividade administrativa',
    'chart_heatmap_created' => 'Mapa de calor de criação (por dia/hora)',
    'chart_heatmap_read' => 'Mapa de calor de leitura (por dia/hora)',

    // Stats
    'stat_avg_first_read' => 'Tempo médio até primeira leitura',

    // Days
    'day_sunday' => 'Dom',
    'day_monday' => 'Seg',
    'day_tuesday' => 'Ter',
    'day_wednesday' => 'Qua',
    'day_thursday' => 'Qui',
    'day_friday' => 'Sex',
    'day_saturday' => 'Sáb',

    // File size units
    'unit_bytes' => 'B',
    'unit_kilobytes' => 'KB',
    'unit_megabytes' => 'MB',
    'unit_gigabytes' => 'GB',

    // Accessibility
    'a11y_show_passphrase' => 'Mostrar frase secreta',
    'a11y_hide_passphrase' => 'Ocultar frase secreta',
    'a11y_back' => 'Voltar',
    'a11y_period_selector' => 'Selecionar período',
    'a11y_extend_days' => 'Número de dias para estender',
    'a11y_expand_secret' => 'Mostrar detalhes do segredo',

    // Split mode
    'split_mode' => 'Separar link e chave',
    'split_mode_hint' => 'Envie o link e a chave por canais diferentes para maior segurança',
    'split_mode_tooltip' => 'Se um canal for comprometido, o atacante terá apenas parte da informação.',
    'share_link_label' => 'Link de compartilhamento',
    'share_key_label' => 'Chave de descriptografia',
    'split_mode_warning' => 'Envie a chave por um canal diferente (SMS, ligação, pessoalmente...).',
    'enter_key_manually' => 'Digite a chave de descriptografia',
    'key_placeholder' => 'Chave recebida separadamente',
    'btn_unlock' => 'Desbloquear',

    // Stats
    'stat_split_mode' => 'Modo separado',

    // Rate limiting & Captcha
    'rate_limit_exceeded' => 'Muitas solicitações. Por favor, resolva o cálculo abaixo para continuar.',
    'captcha_label' => 'Verificação anti-robô',
    'captcha_placeholder' => 'Sua resposta',
    'captcha_hint' => 'Resolva: :challenge = ?',
    'captcha_invalid' => 'Resposta incorreta. Por favor, tente novamente.',

    // Labels
    'label_important' => 'Importante:',
    'label_note' => 'Nota:',

    // Passphrase strength criteria
    'passphrase_min_length' => '12 caracteres mín.',
    'passphrase_lowercase' => 'Minúscula',
    'passphrase_uppercase' => 'Maiúscula',
    'passphrase_digit' => 'Dígito',
    'passphrase_special' => 'Caractere especial',
];
