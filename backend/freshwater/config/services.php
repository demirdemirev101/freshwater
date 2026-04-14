<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

   'econt' => [
        'enabled' => env('ECONT_ENABLED'),
        'sandbox' => env('ECONT_SANDBOX'),
        'verify_ssl' => env('ECONT_VERIFY_SSL'),
        
        'base_url' => env('ECONT_BASE_URL', 'https://demo.econt.com/ee/services'),
        // За реална среда: 'https://ee.econt.com/services'
        'track_url' => env('ECONT_TRACK_URL', null),
        
        'username' => env('ECONT_USERNAME'),
        'password' => env('ECONT_PASSWORD'),
        
        // Данни на изпращача
        'sender' => [
            'name' => env('ECONT_SENDER_NAME', 'Freshwater'),
            'phone' => env('ECONT_SENDER_PHONE', '+359888888888'),

            // Office (production)
            'office_code' => env('ECONT_SENDER_OFFICE', null),

            // Address (sandbox / validate)
            'city' => env('ECONT_SENDER_CITY', 'Стара Загора'),
            'postcode' => env('ECONT_SENDER_POSTCODE', '6000'),
            'street' => env('ECONT_SENDER_STREET', 'бул. Руски 1'),
            'num' => env('ECONT_SENDER_NUM', '1'),
        ],
    ],

    'bank_transfer' => [
        'company_name' => env('BANK_TRANSFER_COMPANY', 'Freshwater EOOD'),
        'iban' => env('BANK_TRANSFER_IBAN', 'BG00XXXX00000000000000'),
        'bank_name' => env('BANK_TRANSFER_BANK', 'Demo Bank'),
        'bic' => env('BANK_TRANSFER_BIC', 'DEMOXXX'),
        'currency' => env('BANK_TRANSFER_CURRENCY', 'EUR'),
    ],

];
