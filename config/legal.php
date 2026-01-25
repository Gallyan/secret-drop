<?php

return [
    'editor_name' => env('LEGAL_EDITOR_NAME', 'Secret Drop'),
    'contact_email' => env('LEGAL_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'contact@example.com')),

    'hosting' => [
        'name' => env('LEGAL_HOSTING_NAME', 'OVHcloud'),
        'legal_form' => env('LEGAL_HOSTING_LEGAL_FORM', 'SAS au capital de 10 000 000 €'),
        'address' => env('LEGAL_HOSTING_ADDRESS', '2 rue Kellermann, 59100 Roubaix, France'),
        'rcs' => env('LEGAL_HOSTING_RCS', 'RCS Lille Métropole 424 761 419'),
    ],
];
