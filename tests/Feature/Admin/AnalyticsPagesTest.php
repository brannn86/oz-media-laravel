<?php

use App\Actions\Analytics\AnalyticsMetrics;
use App\Enums\AdminRole;
use App\Enums\PointReason;
use App\Enums\PostCategory;
use App\Filament\Analytics\Widgets\AudienceStats;
use App\Filament\Analytics\Widgets\TopListenersTable;
use App\Filament\Analytics\Widgets\TopSongsTable;
use App\Filament\Pages\Analytics\AudienceAnalytics;
use App\Filament\Pages\Analytics\EngagementAnalytics;
use App\Filament\Pages\Analytics\NewsAnalytics;
use App\Filament\Pages\Analytics\ProgramsAnalytics;
use App\Filament\Pages\Analytics\RadioAnalytics;
use App\Filament\Pages\Analytics\VideoAnalytics;
use App\Models\PointTransaction;
use App\Models\Post;
use App\Models\Station;
use App\Models\TrackPlay;
use App\Models\User;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);
    $this->station = Station::where('slug', 'oz-bandung')->first();
});

it('opens every analytics page for every admin role', function (AdminRole $role, string $page) {
    $this->actingAs(User::factory()->create()->assignRole($role->value));

    $this->get($page::getUrl())->assertOk();
})->with([AdminRole::Dev, AdminRole::Editorial, AdminRole::SocialMedia])->with([
    'audience' => AudienceAnalytics::class,
    'radio' => RadioAnalytics::class,
    'video' => VideoAnalytics::class,
    'programs' => ProgramsAnalytics::class,
    'news' => NewsAnalytics::class,
    'engagement' => EngagementAnalytics::class,
]);

it('keeps analytics away from listeners', function () {
    $this->actingAs(User::factory()->create());

    $this->get(AudienceAnalytics::getUrl())->assertForbidden();
});

it('counts listener accounts, sign-ups and who actually listened', function () {
    $admin = User::factory()->create()->assignRole(AdminRole::Dev->value);
    $listener = User::factory()->create(['name' => 'Rani Listener']);
    User::factory()->create(['created_at' => now()->subMonths(2)]);

    foreach (range(1, 90) as $minute) {
        PointTransaction::query()->create([
            'user_id' => $listener->id,
            'station_id' => $this->station->id,
            'points' => 1,
            'reason' => PointReason::Listening,
        ]);
    }

    $metrics = app(AnalyticsMetrics::class);
    $accounts = User::query()->doesntHave('roles')->count();

    expect($metrics->listenerAccounts())->toBe($accounts)
        ->and($metrics->activeListeners(7))->toBe(1)
        ->and($metrics->listeningMinutes(7))->toBe(90);

    $this->actingAs($admin);
    Livewire::test(AudienceStats::class)->assertSee('Listener accounts');
    Livewire::test(TopListenersTable::class)
        ->assertCanSeeTableRecords([$listener])
        ->assertSee('1 h 30 min');
});

it('ranks the most played songs with their Icecast audience', function () {
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::Dev->value));

    foreach ([12, 18] as $i => $listeners) {
        TrackPlay::query()->create([
            'station_id' => $this->station->id,
            'title' => 'Hatchu!!',
            'artist' => 'Salma Salsabil',
            'raw_title' => 'Hatchu!! - Salma Salsabil',
            'listeners' => $listeners,
            'started_at' => now()->subHours($i + 1),
            'ended_at' => now()->subHours($i + 1)->addMinutes(3),
        ]);
    }

    Livewire::test(TopSongsTable::class)
        ->assertSee('Hatchu!!')
        ->assertSee('15.0');
});

it('records live reactions per day and type for the video page', function () {
    $this->station->update(['stream_video_url' => 'https://www.youtube.com/watch?v=abcdefghijk']);

    $this->postJson('/api/v1/stations/oz-bandung/reactions', ['type' => 'fire'])->assertNoContent();
    $this->postJson('/api/v1/stations/oz-bandung/reactions', ['type' => 'heart'])->assertNoContent();

    $metrics = app(AnalyticsMetrics::class);

    $series = $metrics->reactionSeries(7);

    expect($metrics->reactions(1))->toBe(2)
        ->and(end($series))->toBe(['fire' => 1, 'heart' => 1]);
});

it('splits article views by section', function () {
    $sports = Post::factory()->for($this->station)->published()->create(['category' => PostCategory::Sports]);
    $tech = Post::factory()->for($this->station)->published()->create(['category' => PostCategory::Tech]);
    $today = now('Asia/Jakarta')->toDateString();

    DB::table('post_views')->insert([
        ['post_id' => $sports->id, 'date' => $today, 'views' => 7],
        ['post_id' => $tech->id, 'date' => $today, 'views' => 3],
    ]);

    expect(app(AnalyticsMetrics::class)->viewsByCategory(30))->toBe(['Sports' => 7, 'Tech' => 3]);
});
