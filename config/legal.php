<?php

return [
    'editor_name' => env('LEGAL_EDITOR_NAME', 'Secret Drop'),
    'hosting_name' => env('LEGAL_HOSTING_NAME', 'OVH'),
    'contact_email' => env('LEGAL_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'contact@example.com')),
];
