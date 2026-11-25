<?php

use App\Enums\AdminRole;
use App\Filament\Analytics\CsvExport;
use App\Filament\Pages\Analytics\AudienceAnalytics;
use App\Filament\Pages\Analytics\EngagementAnalytics;
use App\Filament\Pages\Analytics\NewsAnalytics;
use App\Filament\Pages\Analytics\ProgramsAnalytics;
use App\Filament\Pages\Analytics\RadioAnalytics;
use App\Filament\Pages\Analytics\VideoAnalytics;
use App\Models\Station;
use App\Models\TrackPlay;
use App\Models\User;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);
    $this->travelTo(Carbon::parse('2026-11-25 12:00', 'Asia/Jakarta'));
    $this->actingAs(User::factory()->create()->assignRole(AdminRole::Editorial->value));
});

it('exports every dataset of every analytics page as CSV', function (string $page) {
    $component = Livewire::test($page);

    foreach ((fn (): array => $this->exports())->call(new $page) as $label => $data) {
        $component
            ->callAction(TestAction::make('export_'.str($label)->slug('_')))
            ->assertFileDownloaded((new $page)->exportFilename($label));
    }
})->with([
    'audience' => AudienceAnalytics::class,
    'radio' => RadioAnalytics::class,
    'video' => VideoAnalytics::class,
    'programs' => ProgramsAnalytics::class,
    'news' => NewsAnalytics::class,
    'engagement' => EngagementAnalytics::class,
]);

it('writes the rows behind the table, with a header and Excel-friendly encoding', function () {
    $station = Station::where('slug', 'oz-bandung')->first();

    foreach ([12, 18] as $i => $listeners) {
        TrackPlay::query()->create([
            'station_id' => $station->id,
            'title' => 'Hatchu!!',
            'artist' => 'Salma Salsabil',
            'raw_title' => 'Hatchu!! - Salma Salsabil',
            'listeners' => $listeners,
            'started_at' => now()->subHours($i + 1),
        ]);
    }

    Livewire::test(RadioAnalytics::class)
        ->callAction(TestAction::make('export_most_played_songs_7_days'))
        ->assertFileDownloaded(
            'oz-radio-analytics-most-played-songs-7-days-2026-11-25.csv',
            "\xEF\xBB\xBFTitle,Artist,Plays,\"Avg. Icecast listeners\"\nHatchu!!,\"Salma Salsabil\",2,15\n",
        );
});

it('keeps spreadsheet formulas in exported text from running', function () {
    $response = CsvExport::download('x.csv', ['Name'], [['=HYPERLINK("http://evil")'], ['-5 listeners'], ['Rani']]);

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    expect($csv)->toContain("\"'=HYPERLINK(\"\"http://evil\"\")\"")
        ->toContain("'-5 listeners")
        ->toContain("Rani\n");
});
