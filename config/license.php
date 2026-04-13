<?php

return [
    /*
    |--------------------------------------------------------------------------
    | License Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the external license server that validates license keys.
    |
    */

    'server_url' => env('LICENSE_SERVER_URL', 'https://license-server.test'),

    'buy_url' => env('LICENSE_BUY_URL', 'https://license-server.test/buy'),

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, license activation will work locally without contacting
    | the external server. Useful for development and testing.
    |
    */
    'test_mode' => env('LICENSE_TEST_MODE', false),
];