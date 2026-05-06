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

    'wompi' => [
        'api_url' => env('WOMPI_API_URL', 'https://sandbox.wompi.co/v1'),
        'public_key' => env('WOMPI_PUBLIC_KEY'),
        'private_key' => env('WOMPI_PRIVATE_KEY'),
        'integrity_secret' => env('WOMPI_INTEGRITY_SECRET'),
        'events_secret' => env('WOMPI_EVENTS_SECRET'),
    ],

    'evolution' => [
        'url'      => env('EVOLUTION_API_URL'),
        'key'      => env('EVOLUTION_API_KEY'),
        'instance' => env('EVOLUTION_INSTANCE'),
        'business_number' => env('WHATSAPP_BUSINESS_NUMBER'),
    ],

    'siigo' => [
        'username'            => env('SIIGO_USERNAME'),
        'access_key'          => env('SIIGO_ACCESS_KEY'),
        'partner_id'          => env('SIIGO_PARTNER_ID', 'the-market'),
        'api_url'             => env('SIIGO_API_URL', 'https://api.siigo.com'),
        'company_key'         => env('SIIGO_COMPANY_KEY'),
        'webhook_url'         => env('SIIGO_WEBHOOK_URL'),
        'invoice_document_id' => env('SIIGO_INVOICE_DOCUMENT_ID'),
        'payment_type_id'     => env('SIIGO_PAYMENT_TYPE_ID'),
    ],

];
