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

    'paths' => [
        '/emes/*',
        '/emes/api/*',
        '/api/*',
        'sanctum/csrf-cookie',
        'build/*',
        'public/*',
        'livewire/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],
    // 'allowed_origins' => [
    // 'http://localhost:8000',    // Se você usa 'php artisan serve'
    // 'http://127.0.0.1:8000',   // Alternativa para o artisan serve
    // 'http://localhost:3000',    // Comum para front-ends React/Node
    // 'http://localhost:5173',    // Comum para front-ends Vite (Vue/React)
    // ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
