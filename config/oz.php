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
