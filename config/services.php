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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'moodle' => [
        'url' => env('MOODLE_URL'),
        'token' => env('MOODLE_TOKEN'),
        'enabled' => env('MOODLE_INTEGRATION_ENABLED'),
        'student_role_id' => env('MOODLE_STUDENT_ROLE_ID'),
        'teacher_role_id' => env('MOODLE_TEACHER_ROLE_ID'),
        'admin_role_id' => env('MOODLE_ADMIN_ROLE_ID'),
    ],

    'nominatim' => [
        'search_url' => env('NOMINATIM_SEARCH_URL', 'https://nominatim.openstreetmap.org/search'),
    ],

    'tjes_ldap' => [
        'enabled' => env('TJES_LDAP_ENABLED', false),
        'scheme' => env('TJES_LDAP_SCHEME', 'ldap'),
        'host' => env('TJES_LDAP_HOST', '10.100.192.107'),
        'port' => env('TJES_LDAP_PORT', 389),
        'base_dn' => env('TJES_LDAP_BASE_DN', 'ou=ID,o=tjes'),
        'service_dn' => env('TJES_LDAP_SERVICE_DN'),
        'service_password' => env('TJES_LDAP_SERVICE_PASSWORD'),
        'timeout' => env('TJES_LDAP_TIMEOUT', 5),
        'start_tls' => env('TJES_LDAP_START_TLS', false),
        'require_portal_access' => env('TJES_LDAP_REQUIRE_PORTAL_ACCESS', true),
    ],

];
