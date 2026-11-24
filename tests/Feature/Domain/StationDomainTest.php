<?php

use App\Actions\Schedules\FindScheduleConflicts;
use App\Enums\DayOfWeek;
use App\Enums\PostStatus;
use App\Enums\SocialPlatform;
use App\Events\StationStreamStatusUpdated;
use App\Models\Event;
use App\Models\Post;
use App\Models\Program;
use App\Models\ProgramSchedule;
use App\Models\Station;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event as EventFacade;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;

describe('stream mode', function () {
    it('offers video exactly while a video stream URL is set', function () {
        EventFacade::fake([StationStreamStatusUpdated::class]);
        $station = Station::factory()->create(['stream_video_url' => null]);

        expect($station->hasVideoStream())->toBeFalse();

        $station->update(['stream_video_url' => 'https://video.example.com/live']);

        expect($station->hasVideoStream())->toBeTrue();
        EventFacade::assertDispatched(StationStreamStatusUpdated::class);
    });

    it('keeps one intermission clip per station and only accepts video', function () {
        $station = Station::factory()->create();

        $station->addMedia(fakeMp4('first.mp4'))->toMediaCollection('intermission');
        $station->addMedia(fakeMp4('second.mp4'))->toMediaCollection('intermission');

        expect($station->refresh()->getMedia('intermission'))->toHaveCount(1)
            ->and($station->intermissionVideoUrl())->toEndWith('second.mp4');

        $station->addMedia(UploadedFile::fake()->image('poster.png'))->toMediaCollection('intermission');
    })->throws(FileUnacceptableForCollection::class);
});

describe('schedule conflicts', function () {
    beforeEach(function () {
        $this->program = Program::factory()->create();
        ProgramSchedule::factory()->for($this->program)->at(DayOfWeek::Monday, '06:00', '10:00')->create();
        ProgramSchedule::factory()->for($this->program)->at(DayOfWeek::Monday, '22:00', '00:00')->create();
    });

    it('detects overlaps on the same station and day', function (string $start, string $end, int $expected) {
        $conflicts = app(FindScheduleConflicts::class)->handle($this->program, DayOfWeek::Monday, $start, $end);

        expect($conflicts)->toHaveCount($expected);
    })->with([
        'inside' => ['07:00', '08:00', 1],
        'straddling start' => ['05:00', '07:00', 1],
        'back to back' => ['10:00', '12:00', 0],
        'before midnight slot' => ['21:00', '22:00', 0],
        'into midnight slot' => ['23:00', '00:00', 1],
    ]);

    it('ignores other days and other stations', function () {
        $otherStationProgram = Program::factory()->create();

        expect(app(FindScheduleConflicts::class)->handle($this->program, DayOfWeek::Tuesday, '07:00', '08:00'))->toBeEmpty()
            ->and(app(FindScheduleConflicts::class)->handle($otherStationProgram, DayOfWeek::Monday, '07:00', '08:00'))->toBeEmpty();
    });

    it('knows when a slot is on air', function () {
        $slot = ProgramSchedule::factory()->for($this->program)->at(DayOfWeek::Friday, '22:00', '00:00')->make();

        // 2026-11-20 is a Friday.
        expect($slot->isOnAirAt(Carbon::parse('2026-11-20 23:30')))->toBeTrue()
            ->and($slot->isOnAirAt(Carbon::parse('2026-11-20 21:59')))->toBeFalse()
            ->and($slot->isOnAirAt(Carbon::parse('2026-11-21 23:30')))->toBeFalse();
    });
});

describe('slugs', function () {
    it('generates slugs unique per station', function () {
        $station = Station::factory()->create();

        $first = Program::factory()->for($station)->create(['title' => 'Drive Time']);
        $second = Program::factory()->for($station)->create(['title' => 'Drive Time']);
        $elsewhere = Program::factory()->create(['title' => 'Drive Time']);

        expect($first->slug)->toBe('drive-time')
            ->and($second->slug)->toBe('drive-time-2')
            ->and($elsewhere->slug)->toBe('drive-time');
    });

    it('keeps its own slug when saved again', function () {
        $program = Program::factory()->create(['title' => 'Night Session']);

        $program->update(['description' => 'Updated']);

        expect($program->refresh()->slug)->toBe('night-session');
    });
});

describe('content scopes', function () {
    it('only treats past-dated published posts as live', function () {
        $station = Station::factory()->create();
        Post::factory()->for($station)->published()->create();
        Post::factory()->for($station)->scheduled()->create();
        Post::factory()->for($station)->create();

        expect(Post::published()->count())->toBe(1);
    });

    it('stamps published_at when publishing without a date', function () {
        $post = Post::factory()->create(['status' => PostStatus::Published, 'published_at' => null]);

        expect($post->published_at)->not->toBeNull();
    });

    it('splits upcoming and past events', function () {
        $station = Station::factory()->create();
        Event::factory()->for($station)->count(2)->create();
        Event::factory()->for($station)->past()->create();

        expect(Event::upcoming()->count())->toBe(2)
            ->and(Event::past()->count())->toBe(1);
    });

    it('detects the social platform from a URL', function (string $url, ?SocialPlatform $platform) {
        expect(SocialPlatform::detectFromUrl($url))->toBe($platform);
    })->with([
        ['https://youtu.be/abc', SocialPlatform::YouTube],
        ['https://www.tiktok.com/@oz/video/1', SocialPlatform::TikTok],
        ['https://www.instagram.com/p/abc/', SocialPlatform::Instagram],
        ['https://example.com', null],
    ]);
});
