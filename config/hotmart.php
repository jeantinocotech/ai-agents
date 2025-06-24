<?php

return [
    'client_id' => env('HOTMART_CLIENT_ID'),
    'client_secret' => env('HOTMART_CLIENT_SECRET'),
    'access_token' => env('HOTMART_ACCESS_TOKEN'),
    'basic_auth' => env('HOTMART_BASIC_AUTH'),
    'webhook_token' => env('HOTMART_WEBHOOK_TOKEN'),
    'sandbox' => env('HOTMART_SANDBOX', false),
];