<?php

use App\Enums\AdminRole;
use App\Filament\Pages\Analytics\CampaignLinks;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);
    $this->station = Station::where('slug', 'oz-bandung')->first();
});

it('opens for every admin role', function (AdminRole $role) {
    $this->actingAs(User::factory()->create()->assignRole($role->value));

    $this->get(CampaignLinks::getUrl())->assertOk()->assertSee('Your link');
})->with([AdminRole::Dev, AdminRole::Editorial, AdminRole::SocialMedia]);

it('builds a tagged link, slugging what was typed', function () {
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::SocialMedia->value));
    $post = $this->station->posts()->published()->first();

    $link = (new CampaignLinks)->link($this->station->id, 'article', $post->slug, null, 'instagram', 'story', 'Konser Amal 2026');

    expect($link)->toBe(route('station.news.show', [$this->station, $post->slug])
        .'?utm_source=instagram&utm_medium=story&utm_campaign=konser-amal-2026');

    Livewire::test(CampaignLinks::class)
        ->fillForm(['page' => 'events', 'source' => 'tiktok', 'medium' => 'reel', 'campaign' => 'drive-time'])
        ->assertSee(route('station.events', $this->station).'?utm_source=tiktok&amp;utm_medium=reel&amp;utm_campaign=drive-time', escape: false);
});

it('waits for the article or event before giving a link', function () {
    expect((new CampaignLinks)->link($this->station->id, 'article', null, null, 'instagram', null, null))->toBeNull()
        ->and((new CampaignLinks)->link($this->station->id, 'home', null, null, null, null, null))->toBe(route('station.home', $this->station));
});
