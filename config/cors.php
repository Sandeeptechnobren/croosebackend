<<<<<<< HEAD
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],
    // 'allowed_origins' => ['http://localhost:5173', 'https://68.183.108.227','http://localhost:3000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
||||||| parent of b872fe7 (Live code)
=======
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
>>>>>>> b872fe7 (Live code)
