<?php

return [
    // App
    'app_name' => 'Secret Drop',
    'app_description' => 'شارك المعلومات الحساسة بأمان مع التشفير من طرف إلى طرف.',

    // Features
    'feature_encryption' => 'تشفير بمستوى عسكري في متصفحك',
    'feature_zero_knowledge' => 'الخادم لا يرى بياناتك أبداً',
    'feature_auto_destroy' => 'التدمير الذاتي بعد القراءة',
    'feature_expiration' => 'انتهاء صلاحية قابل للتكوين',

    // Form labels
    'your_secret' => 'سرّك',
    'your_file' => 'ملفك',
    'expires_in' => 'ينتهي في',
    'expires_in_hint' => 'سيُحذف السر تلقائياً بعد هذا الوقت، حتى لو لم يُقرأ.',
    'max_reads' => 'الحد الأقصى للقراءات',
    'max_reads_hint' => 'يحدد عدد مرات عرض السر. عند الوصول للحد يُحذف تلقائياً.',
    'advanced_options' => 'خيارات متقدمة',
    'passphrase' => 'عبارة المرور',
    'passphrase_hint' => 'حماية إضافية. المستلم يحتاجها لفك التشفير. لا تُرسل أبداً للخادم.',
    'passphrase_placeholder' => 'حماية إضافية',
    'your_email' => 'بريدك الإلكتروني',
    'email_placeholder' => 'your@email.com',
    'email_hint' => 'لإدارة أسرارك لاحقاً (إلغاء، تمديد)',

    // Form tabs
    'tab_text' => 'نص',
    'tab_file' => 'ملف',

    // Form placeholders
    'secret_placeholder' => 'أدخل رسالتك السرية...',
    'max_reads_placeholder' => 'غير محدود',

    // Expiration options
    'expiration_1h' => 'ساعة واحدة',
    'expiration_1d' => 'يوم واحد',
    'expiration_7d' => '7 أيام',
    'expiration_30d' => '30 يوماً',
    'expiration_90d' => '90 يوماً',

    // File upload
    'file_drop_click' => 'انقر للاختيار',
    'file_drop_or_drag' => 'أو اسحب ملفاً',
    'file_max_size' => 'الحد الأقصى 100 ميجابايت',
    'file_too_large' => 'الملف كبير جداً (الحد الأقصى 100 ميجابايت)',

    // Buttons
    'btn_encrypt' => 'تشفير وإنشاء الرابط',
    'btn_encrypting' => 'جارٍ التشفير...',
    'btn_encrypting_upload' => 'جارٍ التشفير والرفع...',
    'btn_copy' => 'نسخ',
    'btn_copied' => 'تم النسخ!',
    'btn_decrypt' => 'فك التشفير',
    'btn_decrypting' => 'جارٍ فك التشفير...',
    'btn_retry' => 'إعادة المحاولة',
    'btn_cancel' => 'إلغاء',
    'btn_create_new' => 'إنشاء سر جديد',

    // Success
    'secret_created' => 'تم إنشاء السر',
    'share_link_instruction' => 'شارك هذا الرابط مع المستلم',
    'warning_link_contains_key' => 'يحتوي هذا الرابط على مفتاح فك التشفير. شاركه فقط مع المستلم.',
    'warning_passphrase_required' => 'سيحتاج المستلم إلى إدخال عبارة المرور لفك تشفير السر.',
    'success_admin_hint' => 'يمكنك إدارة هذا السر (إلغاء، تمديد) عبر رابط « :link » في أسفل الصفحة.',

    // QR Code
    'show_qr_code' => 'إظهار رمز QR',
    'hide_qr_code' => 'إخفاء رمز QR',
    'download_qr_code' => 'تحميل رمز QR',
    'qr_code_hint' => 'امسح رمز QR هذا لفتح رابط المشاركة على جهاز آخر.',
    'qr_code_alt' => 'رمز QR يحتوي على رابط المشاركة',
    'qr_generation_failed' => 'فشل في إنشاء رمز QR.',

    // View secret
    'secret_message' => 'رسالة سرية',
    'secret_file' => 'ملف سري',
    'encrypted_end_to_end_message' => 'تم تشفير هذه الرسالة من طرف إلى طرف',
    'encrypted_end_to_end_file' => 'تم تشفير هذا الملف من طرف إلى طرف',
    'passphrase_protected' => 'هذا السر محمي بعبارة مرور.',
    'passphrase_input_placeholder' => 'أدخل عبارة المرور',
    'decrypting_message' => 'جارٍ فك التشفير...',
    'decrypting_file' => 'جارٍ التحميل وفك التشفير...',
    'file_decrypted' => 'تم فك تشفير الملف',
    'file_encrypted_info' => 'ملف مشفر',
    'note_destroyed_text' => 'تم تكوين هذا السر ليتم تدميره بعد القراءة. لم يعد متاحاً.',
    'note_destroyed_file' => 'تم تكوين هذا الملف ليتم تدميره بعد القراءة. لم يعد متاحاً على الخادم.',
    'last_read_warning_title' => 'هذه آخر قراءة متاحة',
    'last_read_warning_text' => 'بمجرد العرض، سيتم حذف هذا السر نهائياً. تأكد من أنك مستعد لمشاهدته.',
    'last_read_warning_short' => 'هذه آخر قراءة متاحة. سيتم حذف السر نهائياً بعد العرض.',
    'btn_reveal_secret' => 'إظهار السر',

    // Errors
    'error_not_found' => 'السر غير موجود',
    'error_unavailable' => 'السر غير متاح',
    'error_generic' => 'خطأ',
    'secret_not_exist' => 'هذا السر غير موجود أو ربما تم حذفه.',
    'secret_expired' => 'انتهت صلاحية هذا السر ولم يعد متاحاً.',
    'secret_revoked' => 'تم إلغاء هذا السر من قبل منشئه.',
    'secret_max_views' => 'وصل هذا السر إلى الحد الأقصى لعدد القراءات ولم يعد متاحاً.',
    'secret_unavailable_generic' => 'هذا السر لم يعد متاحاً.',
    'error_loading' => 'حدث خطأ أثناء تحميل السر.',
    'error_connection' => 'تعذر تحميل السر. تحقق من اتصالك.',

    // Crypto errors
    'crypto_not_supported' => 'متصفحك لا يدعم التشفير الآمن',
    'crypto_key_missing' => 'مفتاح فك التشفير مفقود من الرابط',
    'crypto_fragment_invalid' => 'تنسيق الجزء غير صالح',
    'crypto_passphrase_required' => 'عبارة المرور مطلوبة',
    'crypto_passphrase_incorrect' => 'عبارة المرور غير صحيحة أو البيانات معدلة',
    'crypto_decryption_failed' => 'فشل فك التشفير. قد يكون المفتاح غير صحيح.',
    'crypto_decryption_error' => 'حدث خطأ أثناء فك التشفير',
    'crypto_file_download_failed' => 'تعذر تحميل الملف المشفر',
    'crypto_clipboard_failed' => 'تعذر النسخ إلى الحافظة',
    'crypto_enter_secret' => 'الرجاء إدخال سر',
    'crypto_select_file' => 'الرجاء اختيار ملف',
    'crypto_creation_error' => 'خطأ أثناء إنشاء السر',

    // Loading
    'loading' => 'جارٍ التحميل...',
    'loading_secret' => 'جارٍ تحميل السر...',

    // Emails
    'email_secret_created_subject' => 'تم إنشاء سرك',
    'email_secret_created_intro' => 'لقد أنشأت سراً جديداً. استخدم الرابط أدناه لإدارته (عرض الحالة، إلغاء، تمديد).',
    'email_type' => 'النوع',
    'email_expires' => 'انتهاء الصلاحية',
    'email_manage_secret' => 'إدارة سري',
    'email_save_link_warning' => 'احفظ هذا الرابط! إنه الطريقة الوحيدة للوصول إلى إدارة سرك.',
    'email_link_label' => 'أو انسخ هذا الرابط:',
    'email_footer' => 'تم إرسال هذا البريد الإلكتروني بواسطة :app.',
    'type_text' => 'نص',
    'type_file' => 'ملف',

    // Footer
    'footer_manage' => 'إدارة أسراري',
    'footer_legal' => 'الإشعارات القانونية',

    // Legal page
    'legal_title' => 'الإشعارات القانونية',
    'legal_editor_title' => 'ناشر الموقع',
    'legal_editor_text' => 'هذا الموقع منشور بواسطة :name',
    'legal_hosting_title' => 'الاستضافة',
    'legal_hosting_text' => 'الاستضافة مقدمة من:',
    'legal_hosting_phone' => 'الهاتف:',
    'legal_data_title' => 'حماية البيانات',
    'legal_data_text' => 'تم تصميم Secret Drop وفقاً لمبدأ "المعرفة الصفرية". يتم تشفير الأسرار في متصفحك قبل إرسالها إلى الخادم. يخزن الخادم فقط البيانات المشفرة ولا يمكنه الوصول إلى المحتوى النصي لأسرارك.',
    'legal_data_stored' => 'البيانات المخزنة:',
    'legal_data_item_ciphertext' => 'البيانات المشفرة (المحتوى ومعلمات التشفير)',
    'legal_data_item_metadata' => 'البيانات الوصفية (تاريخ الإنشاء، انتهاء الصلاحية، عدد القراءات)',
    'legal_data_item_file_meta' => 'حجم الملف المشفر (للإحصائيات)',
    'legal_data_item_email' => 'بصمة البريد الإلكتروني (إذا تم تقديمه، للوصول الإداري)',
    'legal_data_not_stored' => 'البيانات غير المخزنة:',
    'legal_data_not_item_plaintext' => 'المحتوى النصي للأسرار',
    'legal_data_not_item_key' => 'مفاتيح التشفير (تُنقل فقط عبر الجزء الخاص من الرابط)',
    'legal_data_not_item_file_meta' => 'اسم الملف ونوعه وحجمه (مشفرة مع المحتوى)',
    'legal_cookies_title' => 'ملفات تعريف الارتباط',
    'legal_cookies_text' => 'يستخدم هذا الموقع فقط ملفات تعريف الارتباط التقنية الأساسية للتشغيل (الجلسة، تفضيل السمة). لا تُستخدم ملفات تعريف الارتباط للتتبع أو الإعلان.',
    'legal_cookies_cnil' => 'وفقاً لتوصيات CNIL، فإن ملفات تعريف الارتباط الضرورية للغاية معفاة من جمع الموافقة.',
    'legal_contact_title' => 'الاتصال',
    'legal_contact_text' => 'لأي سؤال يتعلق بهذا الموقع، يمكنك الاتصال بنا على :email.',
    'legal_contact_prefix' => 'لأي سؤال يتعلق بهذا الموقع، يمكنك الاتصال بنا على',

    // Admin
    'admin_title' => 'إدارة أسرارك',
    'admin_description' => 'أدخل البريد الإلكتروني المستخدم عند إنشاء أسرارك للوصول إلى لوحة الإدارة.',
    'admin_email_placeholder' => 'your@email.com',
    'admin_send_link' => 'إرسال الرابط السحري',
    'admin_back_home' => 'العودة إلى الصفحة الرئيسية',
    'admin_link_sent_title' => 'تحقق من بريدك الإلكتروني',
    'admin_link_sent_description' => 'إذا كانت هناك أسرار مرتبطة بهذا البريد الإلكتروني، فقد تم إرسال رابط سحري.',
    'admin_link_sent_warning' => 'الرابط صالح لمدة 5 دقائق ويمكن استخدامه مرة واحدة فقط.',
    'admin_invalid_link_title' => 'رابط غير صالح أو منتهي الصلاحية',
    'admin_invalid_link_description' => 'هذا الرابط السحري غير صالح أو تم استخدامه بالفعل. يرجى طلب رابط جديد.',
    'admin_not_found_title' => 'السر غير موجود',
    'admin_not_found_description' => 'هذا السر غير موجود أو تم حذفه.',
    'admin_dashboard_title' => 'أسراري',
    'admin_secrets_count' => ':count سر(أسرار)',
    'admin_logout' => 'تسجيل الخروج',
    'admin_no_secrets' => 'لم يتم العثور على أسرار',
    'admin_no_secrets_description' => 'لم تقم بإنشاء أي أسرار باستخدام عنوان البريد الإلكتروني هذا.',
    'admin_status_active' => 'نشط',
    'admin_status_expired' => 'منتهي الصلاحية',
    'admin_status_revoked' => 'ملغي',
    'admin_status_consumed' => 'مستهلك',
    'admin_created' => 'تم الإنشاء',
    'admin_expires' => 'ينتهي',
    'admin_read_count' => 'القراءات',
    'admin_first_read' => 'القراءة الأولى',
    'admin_mode' => 'الوضع',
    'admin_limited_views' => ':count قراءة(قراءات) كحد أقصى',
    'admin_unlimited' => 'غير محدود',
    'admin_day' => 'يوم',
    'admin_days' => 'أيام',
    'admin_extend' => 'تمديد',
    'admin_revoke' => 'إلغاء',
    'admin_revoke_confirm' => 'هل أنت متأكد أنك تريد إلغاء هذا السر؟ هذا الإجراء لا يمكن التراجع عنه.',

    // Magic link email
    'email_magic_link_subject' => 'رابط الوصول إلى Secret Drop',
    'email_magic_link_intro' => 'لقد طلبت الوصول لإدارة أسرارك. انقر على الزر أدناه لتسجيل الدخول.',
    'email_magic_link_button' => 'الوصول إلى أسراري',
    'email_magic_link_warning' => 'هذا الرابط ينتهي في 5 دقائق ويمكن استخدامه مرة واحدة فقط.',

    // Super Admin
    'superadmin_title' => 'المسؤول الأعلى',
    'superadmin_description' => '',
    'email_superadmin_subject' => 'وصول المسؤول الأعلى - Secret Drop',
    'email_superadmin_intro' => 'تم طلب وصول المسؤول الأعلى. انقر على الزر أدناه للوصول إلى لوحة التحكم.',
    'email_superadmin_button' => 'الوصول إلى لوحة التحكم',
    'superadmin_link_sent_description' => 'إذا كان هذا البريد الإلكتروني مصرحاً به، فقد تم إرسال رابط سحري.',
    'superadmin_dashboard_title' => 'إحصائيات الاستخدام',
    'superadmin_dashboard_subtitle' => 'بيانات استخدام مجهولة الهوية لنسختك من Secret Drop.',

    // Periods
    'period_7d' => 'آخر 7 أيام',
    'period_30d' => 'آخر 30 يوماً',
    'period_90d' => 'آخر 90 يوماً',
    'period_1y' => 'السنة الماضية',
    'period_all' => 'منذ البداية',

    // Stats
    'stat_secrets_created' => 'الأسرار المنشأة',
    'stat_secrets_read' => 'الأسرار المقروءة',
    'stat_files_shared' => 'الملفات المشاركة',
    'stat_volume' => 'الحجم',
    'stat_current_disk_usage' => 'مساحة القرص الحالية',
    'stat_text' => 'نص',
    'stat_file' => 'ملف',
    'stat_reads' => 'القراءات',
    'stat_passphrase' => 'مع عبارة مرور',
    'stat_max_views' => 'حد القراءات',
    'stat_read' => 'مقروء',
    'stat_expired_unread' => 'منتهي الصلاحية غير مقروء',
    'stat_revoked' => 'ملغي',
    'stat_max_reached' => 'تم بلوغ الحد',
    'stat_magic_links_requested' => 'الروابط السحرية المطلوبة',
    'stat_magic_links_used' => 'الروابط السحرية المستخدمة',
    'stat_secrets_extended' => 'الأسرار الممددة',

    // Charts
    'chart_secrets_created' => 'الأسرار المنشأة',
    'chart_secrets_read' => 'الأسرار المقروءة',
    'chart_secret_types' => 'أنواع الأسرار',
    'chart_secret_options' => 'الخيارات المستخدمة',
    'chart_secret_outcomes' => 'مصير الأسرار',
    'chart_admin_activity' => 'نشاط الإدارة',
    'chart_heatmap_created' => 'خريطة حرارية للإنشاء (يوم/ساعة)',
    'chart_heatmap_read' => 'خريطة حرارية للقراءة (يوم/ساعة)',

    // Stats
    'stat_avg_first_read' => 'متوسط تأخير القراءة الأولى',

    // Days
    'day_sunday' => 'أحد',
    'day_monday' => 'إثنين',
    'day_tuesday' => 'ثلاثاء',
    'day_wednesday' => 'أربعاء',
    'day_thursday' => 'خميس',
    'day_friday' => 'جمعة',
    'day_saturday' => 'سبت',

    // File size units
    'unit_bytes' => 'بايت',
    'unit_kilobytes' => 'ك.ب',
    'unit_megabytes' => 'م.ب',
    'unit_gigabytes' => 'ج.ب',

    // Accessibility
    'a11y_show_passphrase' => 'إظهار عبارة المرور',
    'a11y_hide_passphrase' => 'إخفاء عبارة المرور',
    'a11y_back' => 'رجوع',
    'a11y_period_selector' => 'اختر الفترة',
    'a11y_extend_days' => 'عدد أيام التمديد',
    'a11y_expand_secret' => 'عرض تفاصيل السر',

    // Split mode
    'split_mode' => 'فصل الرابط والمفتاح',
    'split_mode_hint' => 'انقل الرابط والمفتاح عبر قنوات مختلفة لمزيد من الأمان',
    'split_mode_tooltip' => 'إذا تم اختراق قناة واحدة، يحصل المهاجم على جزء فقط من المعلومات.',
    'share_link_label' => 'رابط المشاركة',
    'share_key_label' => 'مفتاح فك التشفير',
    'split_mode_warning' => 'انقل المفتاح عبر قناة مختلفة (رسالة نصية، هاتف، شخصياً...).',
    'enter_key_manually' => 'أدخل مفتاح فك التشفير',
    'key_placeholder' => 'المفتاح المستلم بشكل منفصل',
    'btn_unlock' => 'فتح',

    // Stats
    'stat_split_mode' => 'وضع الفصل',

    // Rate limiting & Captcha
    'rate_limit_exceeded' => 'طلبات كثيرة جداً. يرجى حل العملية الحسابية أدناه للمتابعة.',
    'captcha_label' => 'التحقق من الروبوت',
    'captcha_placeholder' => 'إجابتك',
    'captcha_hint' => 'حل: :challenge = ؟',
    'captcha_invalid' => 'إجابة غير صحيحة. يرجى المحاولة مرة أخرى.',

    // Labels
    'label_important' => 'مهم:',
    'label_note' => 'ملاحظة:',

    // Passphrase strength criteria
    'passphrase_min_length' => '12 حرفاً كحد أدنى',
    'passphrase_lowercase' => 'حرف صغير',
    'passphrase_uppercase' => 'حرف كبير',
    'passphrase_digit' => 'رقم',
    'passphrase_special' => 'حرف خاص',
];
