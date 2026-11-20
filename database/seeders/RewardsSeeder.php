<?php

namespace Database\Seeders;

use App\Models\Reward;
use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * Sample OZ Poin rewards. Safe to re-run (matched by name).
 */
class RewardsSeeder extends Seeder
{
    public function run(): void
    {
        $bandung = Station::query()->where('slug', 'oz-bandung')->first();

        $rewards = [
            ['OZ Sticker Pack', 'Five vinyl stickers with Ozzy and the OZ logo. Collect at the studio or we post them to you.', 150, null, null, 1],
            ['OZ Tumbler', 'Keep your coffee hot through the Breakfast Club.', 400, 50, null, 2],
            ['OZ Bandung T-shirt', 'The yellow one. Tell us your size when you redeem.', 750, 30, $bandung?->id, 3],
            ['Meet & Greet with the Breakfast Club', 'Sit in on a live show and say hi to Raka and Nadia.', 1500, 5, $bandung?->id, 4],
            ['2 tickets: OZ Charity Night', 'A pair of tickets to the next OZ Charity Night.', 2500, 10, $bandung?->id, 5],
        ];

        foreach ($rewards as [$name, $description, $cost, $stock, $stationId, $sort]) {
            Reward::query()->firstOrCreate(['name' => $name], [
                'description' => $description,
                'points_cost' => $cost,
                'stock' => $stock,
                'station_id' => $stationId,
                'is_active' => true,
                'sort_order' => $sort,
            ]);
        }
    }
}
