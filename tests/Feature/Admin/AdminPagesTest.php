<?php

use App\Enums\AdminRole;
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
use Illuminate\Support\Facades\Event as EventFacade;
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
    $this->get('/admin')->assertOk();

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

it('lets social media toggle the stream mode from the stations table', function () {
    EventFacade::fake();

    $social = User::factory()->create()->assignRole(AdminRole::SocialMedia->value);

    $this->actingAs($social);

    Livewire::test(ListStations::class)
        ->callAction(TestAction::make('toggleStreamMode')->table($this->station));

    expect($this->station->refresh()->is_video_live)->toBeTrue();
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
