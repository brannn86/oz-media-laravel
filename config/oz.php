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

    /*
    |--------------------------------------------------------------------------
    | OZ Poin, Song Requests, Polls & Reactions
    |--------------------------------------------------------------------------
    |
    | Listening earns per_heartbeat points for each accepted heartbeat (one a
    | minute while the player runs). Heartbeats are accepted at most once every
    | heartbeat_interval_seconds per *account*, so extra tabs or devices earn
    | nothing more, and listening points stop at daily_listening_cap (WIB day).
    | The first listen of each day adds streak_bonus_per_day x streak length,
    | up to streak_bonus_max_days.
    |
    */

    'points' => [
        'per_heartbeat' => 1,
        'heartbeat_interval_seconds' => 55,
        'daily_listening_cap' => 120,
        'streak_bonus_per_day' => 5,
        'streak_bonus_max_days' => 7,
        'song_request_approved' => 25,
        'poll_vote' => 5,
    ],

    'requests' => [
        // Open (waiting or coming up) requests one listener may have per station.
        'max_open_per_listener' => 2,
        'cooldown_seconds' => 300,
    ],

    'reactions' => [
        // Per listener: at most this many reactions every `per_seconds`.
        'max' => 3,
        'per_seconds' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Queues (Horizon-ready)
    |--------------------------------------------------------------------------
    |
    | Heavy or deferrable work runs on named queues so a Horizon supervisor (or
    | separate `queue:work --queue=...` workers) can scale them independently:
    | points (ledger writes), media (WebP conversions), analytics (article
    | views), maintenance (chat archiving). `default` keeps everything else.
    |
    */

    'queues' => [
        'points' => env('QUEUE_POINTS', 'points'),
        'media' => env('QUEUE_MEDIA', 'media'),
        'analytics' => env('QUEUE_ANALYTICS', 'analytics'),
        'maintenance' => env('QUEUE_MAINTENANCE', 'maintenance'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    */

    'retention' => [
        // Chat older than this moves to chat_message_archives.
        'archive_chat_after_days' => 90,
        // Moderated (deleted) chat is removed for good after this.
        'purge_deleted_chat_after_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Analytics (self-hosted Umami)
    |--------------------------------------------------------------------------
    |
    | script_url + website_id add the tracking script to the public site.
    | share_url is a Umami "share" link shown inside the admin dashboard.
    |
    */

    'analytics' => [
        'umami' => [
            'script_url' => env('UMAMI_SCRIPT_URL'),
            'website_id' => env('UMAMI_WEBSITE_ID'),
            'share_url' => env('UMAMI_SHARE_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Now Playing
    |--------------------------------------------------------------------------
    |
    | `php artisan stations:sync-now-playing` (scheduled every 15 seconds)
    | reads each station's Icecast status-json.xsl, derived from its audio
    | stream URL. A song change is recorded in track_plays, pushed to players
    | over Reverb, and marks matching open song requests as played.
    |
    */

    'now_playing' => [
        'timeout_seconds' => 5,
        // How many past songs the site shows under "Recently played".
        'recent_limit' => 10,
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
