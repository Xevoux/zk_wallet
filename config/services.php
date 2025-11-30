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
        'token' => env('POSTMARK_TOKEN'),
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

    /*
    |--------------------------------------------------------------------------
    | Polygon Blockchain Configuration
    |--------------------------------------------------------------------------
    */
    'polygon' => [
        'network' => env('POLYGON_NETWORK', 'testnet'),
        'rpc_url' => env('POLYGON_RPC_URL', 'https://rpc-amoy.polygon.technology/'),
        'chain_id' => env('POLYGON_CHAIN_ID', 80002),
        'contract_address' => env('POLYGON_CONTRACT_ADDRESS'),
        'private_key' => env('POLYGON_PRIVATE_KEY'),
        'master_wallet' => env('POLYGON_MASTER_WALLET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Midtrans Payment Gateway Configuration
    |--------------------------------------------------------------------------
    */
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | ZK-SNARK Configuration
    |--------------------------------------------------------------------------
    */
    'zk_snark' => [
        'enabled' => env('ZK_ENABLED', true),
        'circuit_path' => env('ZK_CIRCUIT_PATH', storage_path('circuits/')),
        'proving_key_path' => env('ZK_PROVING_KEY_PATH', storage_path('keys/proving_key.json')),
        'verification_key_path' => env('ZK_VERIFICATION_KEY_PATH', storage_path('keys/verification_key.json')),
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Code Configuration
    |--------------------------------------------------------------------------
    */
    'qr_code' => [
        'size' => env('QR_CODE_SIZE', 300),
        'format' => env('QR_CODE_FORMAT', 'png'),
        'error_correction' => env('QR_CODE_ERROR_CORRECTION', 'M'),
    ],

];
