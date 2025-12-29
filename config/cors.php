<?php

return [

    'paths' => [
        'api/*',
        'croose/api/*',
        'ordiio/api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    // 'allowed_origins' => [
    //     'https://app.joincroose.com',
    //     'https://api.joincroose.com',
    //     'https://app.ordiio.com',
    //     'https://api.ordiio.com',
    //     'http://localhost:3000',
    // ],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
