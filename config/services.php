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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'niubiz_dev' => [
        'merchant_id' => env('VISA_DEV_MERCHANT_ID'),
        'api_user' => env('VISA_DEV_USER'),
        'api_password' => env('VISA_DEV_PWD'),
        'api_url_security' => env('VISA_DEV_URL_SECURITY'),
        'api_url_transaction' => env('VISA_DEV_URL_TRANSACTION'),
    ],
    
    'niubiz_prd' => [
        'merchant_id' => env('VISA_PRD_MERCHANT_ID'),
        'api_user' => env('VISA_PRD_USER'),
        'api_password' => env('VISA_PRD_PWD'),
        'api_url_security' => env('VISA_PRD_URL_SECURITY'),
        'api_url_transaction' => env('VISA_PRD_URL_TRANSACTION'),
    ],
];
