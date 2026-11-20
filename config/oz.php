<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Dev Account
    |--------------------------------------------------------------------------
    |
    | Used by RolesAndPermissionsSeeder to create (or promote) the first `dev`
    | admin. If ADMIN_PASSWORD is empty, local environments fall back to
    | "password" and production gets a random password (reset it afterwards).
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@ozmedianet.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Partners
    |--------------------------------------------------------------------------
    |
    | Stations in the OZ network whose sites are run elsewhere (shown as
    | external links on the corporate landing page). A partner without a URL
    | is listed as "coming soon".
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Live Chat
    |--------------------------------------------------------------------------
    |
    | min_interval_seconds is the floor between two messages from one listener;
    | a station's chat_slow_mode_seconds can raise it, never lower it. Cooldowns
    | live in the rate limiter's cache store (Redis in production).
    |
    | Blocked words are masked (first letter kept) rather than rejected, so a
    | message is never silently lost. Matching is whole-word, case-insensitive
    | and tolerates stretched letters ("anjiiing"). The live list is edited in
    | the admin (Live Chat > Blocked Words); default_blocked_words only seeds it
    | on a fresh install.
    |
    */

    'chat' => [
        'min_interval_seconds' => 3,
        'max_length' => 280,
        'history_size' => 50,
        'slow_mode_options' => [0, 5, 10, 30, 60],
        'mute_options_minutes' => [10, 60, 1440],
        'default_blocked_words' => [
            // Indonesian / Sundanese
            // (Everyday words like "babi" and Sundanese "sia" are left out on purpose.)
            'anjing', 'anjir', 'anjrit', 'bangsat', 'bajingan', 'kontol', 'memek',
            'ngentot', 'entot', 'goblok', 'goblog', 'tolol', 'jancok', 'jancuk', 'asu',
            'tai', 'kampret', 'pepek', 'lonte', 'perek', 'kehed',
            // English
            'fuck', 'fucking', 'shit', 'bitch', 'asshole', 'cunt', 'dick', 'bastard', 'motherfucker',
        ],
    ],

    'network' => [
        'partners' => [
            [
                'name' => 'OZ Bali',
                'tagline' => 'The island\'s OZ station, run by our Bali partner team.',
                'url' => env('OZ_BALI_URL'),
            ],
        ],
    ],

];
