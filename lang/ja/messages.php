<?php

return [
    // App
    'app_name' => 'Secret Drop',
    'app_description' => 'エンドツーエンド暗号化で機密情報を安全に共有。',

    // Features
    'feature_encryption' => 'ブラウザで軍事レベルの暗号化',
    'feature_zero_knowledge' => 'サーバーは平文データを一切見ません',
    'feature_auto_destroy' => '読み取り後に自動削除',
    'feature_expiration' => '設定可能な自動有効期限',

    // Form labels
    'your_secret' => 'シークレット',
    'your_file' => 'ファイル',
    'expires_in' => '有効期限',
    'expires_in_hint' => 'シークレットは読まれていなくても、この時間後に自動的に削除されます。',
    'max_reads' => '最大閲覧数',
    'max_reads_hint' => '閲覧回数を制限します。上限に達すると自動的に削除されます。',
    'advanced_options' => '詳細オプション',
    'passphrase' => 'パスフレーズ',
    'passphrase_hint' => '追加の保護。受信者は復号に必要です。サーバーには送信されません。',
    'passphrase_placeholder' => '追加の保護',
    'your_email' => 'メールアドレス',
    'email_placeholder' => 'your@email.com',
    'email_hint' => '後でシークレットを管理するため（取り消し、延長）',

    // Form tabs
    'tab_text' => 'テキスト',
    'tab_file' => 'ファイル',

    // Form placeholders
    'secret_placeholder' => '機密メッセージを入力...',
    'max_reads_placeholder' => '無制限',

    // Expiration options
    'expiration_1h' => '1時間',
    'expiration_1d' => '1日',
    'expiration_7d' => '7日',
    'expiration_30d' => '30日',
    'expiration_90d' => '90日',

    // File upload
    'file_drop_click' => 'クリックして選択',
    'file_drop_or_drag' => 'またはドラッグ',
    'file_max_size' => '最大100MB',
    'file_too_large' => 'ファイルが大きすぎます（最大100MB）',

    // Buttons
    'btn_encrypt' => '暗号化してリンク作成',
    'btn_encrypting' => '暗号化中...',
    'btn_encrypting_upload' => '暗号化＆アップロード中...',
    'btn_copy' => 'コピー',
    'btn_copied' => 'コピー完了！',
    'btn_decrypt' => '復号化',
    'btn_decrypting' => '復号化中...',
    'btn_retry' => '再試行',
    'btn_cancel' => 'キャンセル',
    'btn_create_new' => '新しいシークレットを作成',

    // Success
    'secret_created' => 'シークレット作成完了',
    'share_link_instruction' => 'このリンクを受信者に共有してください',
    'warning_link_contains_key' => 'このリンクには復号化キーが含まれています。意図した受信者のみに共有してください。',
    'warning_passphrase_required' => '受信者はシークレットを復号化するためにパスフレーズを入力する必要があります。',
    'success_admin_hint' => 'ページ下部の「:link」リンクからこのシークレットを管理（取り消し、延長）できます。',

    // QR Code
    'show_qr_code' => 'QRコードを表示',
    'hide_qr_code' => 'QRコードを隠す',
    'download_qr_code' => 'QRコードをダウンロード',
    'qr_code_hint' => 'このQRコードをスキャンして、別のデバイスで共有リンクを開きます。',
    'qr_code_alt' => '共有リンクを含むQRコード',
    'qr_generation_failed' => 'QRコードの生成に失敗しました。',

    // View secret
    'view_secret_title' => 'シークレットを表示',
    'secret_message' => 'シークレットメッセージ',
    'secret_file' => 'シークレットファイル',
    'encrypted_end_to_end_message' => 'このメッセージはエンドツーエンドで暗号化されています',
    'encrypted_end_to_end_file' => 'このファイルはエンドツーエンドで暗号化されています',
    'passphrase_protected' => 'このシークレットはパスフレーズで保護されています。',
    'passphrase_input_placeholder' => 'パスフレーズを入力',
    'decrypting_message' => '復号化中...',
    'decrypting_file' => 'ダウンロード＆復号化中...',
    'file_decrypted' => 'ファイル復号化完了',
    'file_encrypted_info' => '暗号化されたファイル',
    'note_destroyed_text' => 'このシークレットは読み取り後に削除されるよう設定されていました。もうアクセスできません。',
    'note_destroyed_file' => 'このファイルは読み取り後に削除されるよう設定されていました。サーバー上でアクセスできなくなりました。',
    'last_read_warning_title' => 'これが最後の閲覧です',
    'last_read_warning_text' => '表示後、このシークレットは完全に削除されます。閲覧する準備ができていることを確認してください。',
    'last_read_warning_short' => 'これが最後の閲覧です。表示後、シークレットは完全に削除されます。',
    'btn_reveal_secret' => 'シークレットを表示',

    // Errors
    'error_not_found' => 'シークレットが見つかりません',
    'error_unavailable' => 'シークレットは利用できません',
    'error_generic' => 'エラー',
    'secret_not_exist' => 'このシークレットは存在しないか、削除された可能性があります。',
    'secret_expired' => 'このシークレットは期限切れで、もうアクセスできません。',
    'secret_revoked' => 'このシークレットは作成者によって取り消されました。',
    'secret_max_views' => 'このシークレットは最大閲覧数に達し、もうアクセスできません。',
    'secret_unavailable_generic' => 'このシークレットはもうアクセスできません。',
    'error_loading' => 'シークレットの読み込み中にエラーが発生しました。',
    'error_connection' => 'シークレットを読み込めません。接続を確認してください。',

    // Crypto errors
    'crypto_not_supported' => 'お使いのブラウザは安全な暗号化をサポートしていません',
    'crypto_key_missing' => 'URLに復号化キーがありません',
    'crypto_fragment_invalid' => '無効なフラグメント形式',
    'crypto_passphrase_required' => 'パスフレーズが必要です',
    'crypto_passphrase_incorrect' => 'パスフレーズが間違っているか、データが改変されています',
    'crypto_decryption_failed' => '復号化に失敗しました。キーが間違っている可能性があります。',
    'crypto_decryption_error' => '復号化中にエラーが発生しました',
    'crypto_file_download_failed' => '暗号化ファイルをダウンロードできません',
    'crypto_clipboard_failed' => 'クリップボードにコピーできません',
    'crypto_enter_secret' => 'シークレットを入力してください',
    'crypto_select_file' => 'ファイルを選択してください',
    'crypto_creation_error' => 'シークレット作成エラー',

    // Loading
    'loading' => '読み込み中...',
    'loading_secret' => 'シークレット読み込み中...',

    // Emails
    'email_secret_created_subject' => 'シークレットが作成されました',
    'email_secret_created_intro' => '新しいシークレットを作成しました。以下のリンクで管理（ステータス確認、取り消し、延長）できます。',
    'email_type' => 'タイプ',
    'email_expires' => '有効期限',
    'email_manage_secret' => 'シークレットを管理',
    'email_save_link_warning' => 'このリンクを保存してください！シークレットの管理画面にアクセスする唯一の方法です。',
    'email_link_label' => 'またはこのリンクをコピー：',
    'email_footer' => 'このメールは :app から送信されました。',
    'type_text' => 'テキスト',
    'type_file' => 'ファイル',

    // Footer
    'footer_manage' => 'シークレットを管理',
    'footer_legal' => '法的情報',

    // Legal page
    'legal_title' => '法的情報',
    'legal_editor_title' => 'サイト運営者',
    'legal_editor_text' => 'このサイトは :name によって運営されています。',
    'legal_hosting_title' => 'ホスティング',
    'legal_hosting_text' => 'このサイトのホスティング：',
    'legal_hosting_phone' => '電話：',
    'legal_data_title' => 'データ保護',
    'legal_data_text' => 'Secret Dropは「ゼロナレッジ」原則で設計されています。シークレットはサーバーに送信される前にブラウザで暗号化されます。サーバーは暗号化されたデータのみを保存し、シークレットの平文にアクセスできません。',
    'legal_data_stored' => '保存されるデータ：',
    'legal_data_item_ciphertext' => '暗号化データ（内容と暗号化パラメータ）',
    'legal_data_item_metadata' => 'メタデータ（作成日、有効期限、閲覧数）',
    'legal_data_item_file_meta' => '暗号化されたファイルサイズ（統計用）',
    'legal_data_item_email' => 'メールのフィンガープリント（管理者アクセス用に提供された場合）',
    'legal_data_not_stored' => '保存されないデータ：',
    'legal_data_not_item_plaintext' => 'シークレットの平文',
    'legal_data_not_item_key' => '暗号化キー（リンクのプライベート部分でのみ送信）',
    'legal_data_not_item_file_meta' => 'ファイル名、タイプ、サイズ（コンテンツと共に暗号化）',
    'legal_cookies_title' => 'Cookie',
    'legal_cookies_text' => 'このサイトは必須の技術的Cookieのみを使用しています（セッション、テーマ設定）。トラッキングや広告Cookieは使用していません。',
    'legal_cookies_cnil' => 'CNIL勧告に従い、これらの厳密に必要なCookieは同意要件から免除されています。',
    'legal_contact_title' => 'お問い合わせ',
    'legal_contact_text' => 'このサイトに関するご質問は :email までお問い合わせください。',
    'legal_contact_prefix' => 'このサイトに関するご質問はこちらまでお問い合わせください：',

    // Admin
    'admin_title' => 'シークレットを管理',
    'admin_description' => 'シークレット作成時に使用したメールアドレスを入力して、管理パネルにアクセスしてください。',
    'admin_email_placeholder' => 'your@email.com',
    'admin_send_link' => 'マジックリンクを送信',
    'admin_back_home' => 'ホームに戻る',
    'admin_link_sent_title' => '受信トレイを確認',
    'admin_link_sent_description' => 'このメールアドレスにシークレットが存在する場合、マジックリンクが送信されました。',
    'admin_link_sent_warning' => 'リンクは5分間有効で、1回のみ使用できます。',
    'admin_invalid_link_title' => '無効または期限切れのリンク',
    'admin_invalid_link_description' => 'このマジックリンクは無効か、すでに使用されています。新しいものをリクエストしてください。',
    'admin_not_found_title' => 'シークレットが見つかりません',
    'admin_not_found_description' => 'このシークレットは存在しないか、削除されました。',
    'admin_dashboard_title' => 'マイシークレット',
    'admin_secrets_count' => ':count 件のシークレット',
    'admin_logout' => 'ログアウト',
    'admin_no_secrets' => 'シークレットが見つかりません',
    'admin_no_secrets_description' => 'このメールアドレスでシークレットを作成していません。',
    'admin_status_active' => '有効',
    'admin_status_expired' => '期限切れ',
    'admin_status_revoked' => '取り消し済み',
    'admin_status_consumed' => '使用済み',
    'admin_created' => '作成日',
    'admin_expires' => '有効期限',
    'admin_read_count' => '閲覧数',
    'admin_first_read' => '初回閲覧',
    'admin_mode' => 'モード',
    'admin_limited_views' => '最大:count回閲覧',
    'admin_unlimited' => '無制限',
    'admin_day' => '日',
    'admin_days' => '日',
    'admin_extend' => '延長',
    'admin_revoke' => '取り消し',
    'admin_revoke_confirm' => 'このシークレットを取り消しますか？この操作は元に戻せません。',

    // Magic link email
    'email_magic_link_subject' => 'Secret Dropアクセスリンク',
    'email_magic_link_intro' => 'シークレット管理へのアクセスをリクエストしました。以下のボタンをクリックしてログインしてください。',
    'email_magic_link_button' => 'シークレットにアクセス',
    'email_magic_link_warning' => 'このリンクは5分で期限切れになり、1回のみ使用できます。',

    // Super Admin
    'superadmin_title' => 'スーパー管理者',
    'superadmin_description' => '',
    'email_superadmin_subject' => 'スーパー管理者アクセス - Secret Drop',
    'email_superadmin_intro' => 'スーパー管理者アクセスがリクエストされました。以下のボタンをクリックしてダッシュボードにアクセスしてください。',
    'email_superadmin_button' => 'ダッシュボードにアクセス',
    'superadmin_link_sent_description' => 'このメールアドレスが認証されている場合、マジックリンクが送信されました。',
    'superadmin_dashboard_title' => '使用統計',
    'superadmin_dashboard_subtitle' => 'Secret Dropインスタンスの匿名使用データ。',

    // Periods
    'period_7d' => '過去7日間',
    'period_30d' => '過去30日間',
    'period_90d' => '過去90日間',
    'period_1y' => '過去1年',
    'period_all' => '全期間',

    // Stats
    'stat_secrets_created' => '作成されたシークレット',
    'stat_secrets_read' => '閲覧されたシークレット',
    'stat_files_shared' => '共有されたファイル',
    'stat_volume' => '容量',
    'stat_current_disk_usage' => '現在のディスク使用量',
    'stat_text' => 'テキスト',
    'stat_file' => 'ファイル',
    'stat_reads' => '閲覧数',
    'stat_passphrase' => 'パスフレーズ付き',
    'stat_max_views' => '最大閲覧数制限',
    'stat_read' => '閲覧済み',
    'stat_expired_unread' => '未読で期限切れ',
    'stat_revoked' => '取り消し済み',
    'stat_max_reached' => '最大閲覧数到達',
    'stat_magic_links_requested' => 'リクエストされたマジックリンク',
    'stat_magic_links_used' => '使用されたマジックリンク',
    'stat_secrets_extended' => '延長されたシークレット',

    // Charts
    'chart_secrets_created' => '作成されたシークレット',
    'chart_secrets_read' => '閲覧されたシークレット',
    'chart_secret_types' => 'シークレットの種類',
    'chart_secret_options' => '使用されたオプション',
    'chart_secret_outcomes' => 'シークレットの結果',
    'chart_admin_activity' => '管理者アクティビティ',
    'chart_heatmap_created' => '作成ヒートマップ（曜日/時間別）',
    'chart_heatmap_read' => '閲覧ヒートマップ（曜日/時間別）',

    // Stats
    'stat_avg_first_read' => '平均初回閲覧時間',

    // Days
    'day_sunday' => '日',
    'day_monday' => '月',
    'day_tuesday' => '火',
    'day_wednesday' => '水',
    'day_thursday' => '木',
    'day_friday' => '金',
    'day_saturday' => '土',

    // File size units
    'unit_bytes' => 'B',
    'unit_kilobytes' => 'KB',
    'unit_megabytes' => 'MB',
    'unit_gigabytes' => 'GB',

    // Accessibility
    'a11y_show_passphrase' => 'パスフレーズを表示',
    'a11y_hide_passphrase' => 'パスフレーズを非表示',
    'a11y_back' => '戻る',
    'a11y_period_selector' => '期間を選択',
    'a11y_extend_days' => '延長する日数',
    'a11y_expand_secret' => 'シークレットの詳細を表示',

    // Split mode
    'split_mode' => 'リンクとキーを分離',
    'split_mode_hint' => 'セキュリティ向上のため、リンクとキーを別々のチャネルで送信',
    'split_mode_tooltip' => '1つのチャネルが侵害されても、攻撃者は情報の一部しか得られません。',
    'share_link_label' => '共有リンク',
    'share_key_label' => '復号化キー',
    'split_mode_warning' => 'キーは別のチャネル（SMS、電話、対面など）で送信してください。',
    'enter_key_manually' => '復号化キーを入力',
    'key_placeholder' => '別途受け取ったキー',
    'btn_unlock' => 'ロック解除',

    // Stats
    'stat_split_mode' => '分離モード',

    // Rate limiting & Captcha
    'rate_limit_exceeded' => 'リクエストが多すぎます。続行するには以下の計算を解いてください。',
    'captcha_label' => 'ロボット検証',
    'captcha_placeholder' => '答え',
    'captcha_hint' => '計算：:challenge = ?',
    'captcha_invalid' => '答えが間違っています。もう一度お試しください。',

    // Labels
    'label_important' => '重要：',
    'label_note' => '注：',

    // Passphrase strength criteria
    'passphrase_min_length' => '12文字以上',
    'passphrase_lowercase' => '小文字',
    'passphrase_uppercase' => '大文字',
    'passphrase_digit' => '数字',
    'passphrase_special' => '特殊文字',
];
