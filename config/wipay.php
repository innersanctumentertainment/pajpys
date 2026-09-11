<?php

return [
    'account_number' => env('WIPAY_ACCOUNT_NUMBER'),
    'api_key' => env('WIPAY_API_KEY'),
    'webhook_secret' => env('WIPAY_WEBHOOK_SECRET'),
    'environment' => env('WIPAY_ENVIRONMENT', 'sandbox'),
    'base_url' => env('WIPAY_BASE_URL', 'https://sandbox.wipayfinancial.com'),
    'response_url' => env('WIPAY_RESPONSE_URL'),
    'webhook_url' => env('WIPAY_WEBHOOK_URL'),
    'default_currency' => env('WIPAY_DEFAULT_CURRENCY', 'TTD'),
    'timeout' => (int) env('WIPAY_TIMEOUT', 30),
];
