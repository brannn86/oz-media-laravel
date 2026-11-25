<?php

use App\Events\StationStreamStatusUpdated;
use App\Filament\Pages\StationSettings;
use App\Filament\Resources\Announcers\AnnouncerResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\ProgramResource;
use App\Filament\Resources\Programs\RelationManagers\SchedulesRelationManager;
use App\Filament\Resources\ProgramSchedules\ProgramScheduleResource;
use App\Filament\Resources\SocialHighlights\SocialHighlightResource;
use App\Filament\Resources\Stations\Pages\ListStations;
use App\Filament\Resources\Stations\StationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Post;
use App\Models\Program;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);
    $this->dev = User::where('email', 'admin@ozmedianet.com')->first();
    $this->actingAs($this->dev);
    $this->station = Station::where('slug', 'oz-bandung')->first();
});

it('renders every list and create page with seeded content', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    fn () => ProgramResource::getUrl('index'),
    fn () => ProgramResource::getUrl('create'),
    fn () => ProgramScheduleResource::getUrl('index'),
    fn () => AnnouncerResource::getUrl('index'),
    fn () => AnnouncerResource::getUrl('create'),
    fn () => PostResource::getUrl('index'),
    fn () => PostResource::getUrl('create'),
    fn () => EventResource::getUrl('index'),
    fn () => EventResource::getUrl('create'),
    fn () => SocialHighlightResource::getUrl('index'),
    fn () => StationResource::getUrl('index'),
    fn () => StationResource::getUrl('create'),
    fn () => StationSettings::getUrl(['station' => 'oz-bandung']),
    fn () => UserResource::getUrl('index'),
    fn () => UserResource::getUrl('create'),
]);

it('shows each sidebar item exactly once for dev', function () {
    $this->get('/admin/dashboard')->assertOk();

    $items = collect(Filament::getNavigation())
        ->flatMap(fn ($group) => collect($group->getItems())->map(fn ($item) => ($group->getLabel() ?? '').' / '.$item->getLabel()));

    expect($items->duplicates())->toBeEmpty()
        ->and($items)->toContain('Access Control / Users', 'Access Control / Roles');
});

it('renders edit pages', function () {
    $this->get(ProgramResource::getUrl('edit', ['record' => Program::first()]))->assertOk();
    $this->get(PostResource::getUrl('edit', ['record' => Post::first()]))->assertOk();
    $this->get(AnnouncerResource::getUrl('edit', ['record' => $this->station->announcers()->first()]))->assertOk();
    $this->get(EventResource::getUrl('edit', ['record' => $this->station->events()->first()]))->assertOk();
});

it('saves station settings', function () {
    Livewire::test(StationSettings::class, ['stationSlug' => 'oz-bandung'])
        ->fillForm([
            'tagline' => 'New tagline',
            'contact_info.whatsapp' => '+6281111111111',
            'social_links.instagram' => 'https://www.instagram.com/oz.new',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->station->refresh();

    expect($this->station->tagline)->toBe('New tagline')
        ->and($this->station->contact_info['whatsapp'])->toBe('+6281111111111')
        ->and($this->station->contact_info['email'])->toBe('hello@ozmedianet.com')
        ->and($this->station->social_links['instagram'])->toBe('https://www.instagram.com/oz.new');
});

it('shows each station as Audio, or Audio and Video when it has a video URL', function () {
    $radioOnly = Station::factory()->create(['stream_video_url' => null]);
    $this->station->update(['stream_video_url' => 'https://www.youtube.com/watch?v=abcdefghijk']);

    Livewire::test(ListStations::class)
        ->assertTableColumnStateSet('live_mode', 'Audio and Video', $this->station)
        ->assertTableColumnStateSet('live_mode', 'Audio', $radioOnly)
        ->assertTableActionDoesNotExist('toggleStreamMode');
});

it('uploads an intermission video in station settings and tells open players', function () {
    Storage::fake('public');
    Storage::fake('local');
    EventFacade::fake([StationStreamStatusUpdated::class]);

    Livewire::test(StationSettings::class, ['stationSlug' => 'oz-bandung'])
        ->fillForm(['intermission' => fakeMp4()])
        ->call('save')
        ->assertHasNoFormErrors();

    $media = $this->station->refresh()->getFirstMedia('intermission');

    // On the public disk: the private `local` disk answers 403 to the site.
    expect($media->disk)->toBe('public')
        ->and($this->station->intermissionVideoUrl())->toEndWith('.mp4');
    Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
    EventFacade::assertDispatched(StationStreamStatusUpdated::class);
});

it('rejects overlapping schedule slots in the program relation manager', function () {
    $program = Program::where('slug', 'midday-mix')->first();

    Livewire::test(SchedulesRelationManager::class, [
        'ownerRecord' => $program,
        'pageClass' => EditProgram::class,
    ])
        ->callAction(TestAction::make('create')->table(), data: [
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ])
        ->assertHasFormErrors(['end_time']);
});

it('saves a sponsor banner and shows it under the player', function () {
    Storage::fake('public');
    Storage::fake('local');

    $this->get('/oz-bandung')->assertInertia(fn ($page) => $page
        ->where('sponsor.house_email', config('oz.advertising_email')));

    Livewire::test(StationSettings::class, ['stationSlug' => 'oz-bandung'])
        ->fillForm([
            'sponsor_banner' => UploadedFile::fake()->image('banner.png', 2400, 300),
            'sponsor_name' => 'Kopi Kita',
            'sponsor_url' => 'https://kopikita.example',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->station->refresh()->getFirstMedia('sponsor_banner'))->not->toBeNull();

    // The save clears the cached house ad.
    $this->get('/oz-bandung')->assertInertia(fn ($page) => $page
        ->where('sponsor.name', 'Kopi Kita')
        ->where('sponsor.url', 'https://kopikita.example')
        ->where('sponsor.image_url', fn (string $url) => str_contains($url, '/storage/'))
        ->where('sponsor.mobile_image_url', null));
});

it('hides the video settings for a basic station', function () {
    Livewire::test(StationSettings::class, ['stationSlug' => 'oz-bandung'])
        ->assertFormFieldVisible('stream_video_url')
        ->fillForm(['site_type' => 'basic'])
        ->assertFormFieldHidden('stream_video_url')
        ->assertFormFieldHidden('intermission');
});
