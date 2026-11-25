<?php

namespace Database\Seeders;

use App\Enums\StationSiteType;
use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * OZ Radio Bali at /oz-bali: a basic site (the radio player only). Its
 * stream URL is set in the admin under Station settings → Streaming.
 * Safe to re-run: an existing station is left as it is.
 */
class OzBaliSeeder extends Seeder
{
    public function run(): void
    {
        Station::query()->firstOrCreate(['slug' => 'oz-bali'], [
            'name' => 'OZ Bali',
            'tagline' => 'The island\'s OZ station, 101.2 FM.',
            'site_type' => StationSiteType::Basic,
            'is_active' => true,
            'privacy_policy_content' => file_get_contents(database_path('content/privacy-measurement.html')),
        ]);
    }
}
