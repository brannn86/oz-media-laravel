<?php

use App\Actions\Broadcast\GetCurrentBroadcastAction;
use App\Enums\AdminRole;
use App\Enums\DayOfWeek;
use App\Filament\Widgets\ListenersChart;
use App\Filament\Widgets\StationStatsOverview;
use App\Filament\Widgets\TopArticlesWidget;
use App\Filament\Widgets\UmamiWidget;
use App\Jobs\ArchiveOldChatMessages;
use App\Jobs\RecordPostView;
use App\Models\Announcer;
use App\Models\ChatMessage;
use App\Models\Post;
use App\Models\Program;
use App\Models\ProgramSchedule;
use App\Models\Reward;
use App\Models\Station;
use App\Models\User;
use App\Support\Icecast\IcecastSource;
use App\Support\Icecast\ListenerCount;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

beforeEach(function () {
    config(['broadcasting.default' => 'null']);
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
});

describe('station cache', function () {
    it('serves the schedule from cache and drops it the moment the schedule changes', function () {
        $this->travelTo(Carbon::parse('2026-11-16 07:30', 'Asia/Jakarta')); // Monday
        $program = Program::factory()->for($this->station)->create(['title' => 'Breakfast Club']);
        $slot = ProgramSchedule::factory()->for($program)->at(DayOfWeek::Monday, '06:00', '10:00')->create();
        $action = app(GetCurrentBroadcastAction::class);

        expect($action->handle($this->station)->current->program->title)->toBe('Breakfast Club');

        // Cached: a raw DB change (no model events) is not seen yet...
        DB::table('programs')->where('id', $program->id)->update(['title' => 'Renamed quietly']);
        expect($action->handle($this->station)->current->program->title)->toBe('Breakfast Club');

        // ...but any edit through the app invalidates it at once.
        $slot->update(['end_time' => '09:00']);
        expect($action->handle($this->station)->current->program->title)->toBe('Renamed quietly');
    });

    it('refreshes public pages after an edit in the admin', function () {
        $host = Announcer::factory()->for($this->station)->create(['stage_name' => 'Raka']);

        $this->get('/oz-bandung/announcers')->assertInertia(fn (Assert $page) => $page->where('announcers.0.display_name', 'Raka'));

        $host->update(['stage_name' => 'Raka OZ']);

        $this->get('/oz-bandung/announcers')->assertInertia(fn (Assert $page) => $page->where('announcers.0.display_name', 'Raka OZ'));
    });

    it('keeps stations separate', function () {
        $other = Station::factory()->create(['slug' => 'oz-bali']);
        Announcer::factory()->for($other)->create(['stage_name' => 'Bali host']);
        Announcer::factory()->for($this->station)->create(['stage_name' => 'Bandung host']);

        $this->get('/oz-bandung/announcers')->assertInertia(fn (Assert $page) => $page->has('announcers', 1)->where('announcers.0.display_name', 'Bandung host'));
        $this->get('/oz-bali/announcers')->assertInertia(fn (Assert $page) => $page->has('announcers', 1)->where('announcers.0.display_name', 'Bali host'));
    });
});

describe('background work', function () {
    it('counts an article view once per visitor per half hour, on the analytics queue', function () {
        Queue::fake();
        $post = Post::factory()->for($this->station)->published()->create();
        // The same browser session, as a real visitor would keep it.
        $this->withCookie(config('session.cookie'), str_repeat('a', 40));

        $this->get("/oz-bandung/news/{$post->slug}")->assertOk();
        $this->get("/oz-bandung/news/{$post->slug}")->assertOk();

        Queue::assertPushedOn('analytics', RecordPostView::class);
        Queue::assertPushed(RecordPostView::class, 1);

        // A different visitor counts again.
        $this->withCookie(config('session.cookie'), str_repeat('b', 40));
        $this->get("/oz-bandung/news/{$post->slug}")->assertOk();
        Queue::assertPushed(RecordPostView::class, 2);
    });

    it('adds up daily views', function () {
        $post = Post::factory()->for($this->station)->published()->create();

        (new RecordPostView($post->id, '2026-11-16'))->handle();
        (new RecordPostView($post->id, '2026-11-16'))->handle();
        (new RecordPostView($post->id, '2026-11-17'))->handle();

        expect(DB::table('post_views')->where('post_id', $post->id)->orderBy('date')->pluck('views')->all())->toBe([2, 1]);
    });

    it('archives old chat, purges long-deleted chat and keeps recent and pinned messages', function () {
        $old = ChatMessage::factory()->for($this->station)->create(['created_at' => now()->subDays(100)]);
        $oldPinned = ChatMessage::factory()->for($this->station)->pinned()->create(['created_at' => now()->subDays(100)]);
        $recent = ChatMessage::factory()->for($this->station)->create();
        $deleted = ChatMessage::factory()->for($this->station)->create();
        $deleted->delete();
        ChatMessage::withTrashed()->whereKey($deleted->id)->update(['deleted_at' => now()->subDays(40)]);

        (new ArchiveOldChatMessages)->handle();

        expect(ChatMessage::withTrashed()->pluck('id')->sort()->values()->all())->toBe([$oldPinned->id, $recent->id])
            ->and(DB::table('chat_message_archives')->pluck('id')->all())->toBe([$old->id]);
    });

    it('schedules the maintenance commands', function () {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('oz:sample-listeners')
            ->expectsOutputToContain('oz:prune-redis-keys')
            ->expectsOutputToContain('oz:archive-chat');
    });

    it('prunes nothing when the cache is not Redis', function () {
        $this->artisan('oz:prune-redis-keys')->expectsOutputToContain('not Redis')->assertSuccessful();
    });

    it('queues chat archiving on the maintenance queue', function () {
        Queue::fake();

        $this->artisan('oz:archive-chat')->assertSuccessful();

        Queue::assertPushedOn('maintenance', ArchiveOldChatMessages::class);
    });
});

describe('images', function () {
    it('converts uploads to WebP and falls back to the old column', function () {
        Storage::fake('public');
        $reward = Reward::factory()->create(['image' => 'rewards/legacy.jpg']);

        expect($reward->webpImageUrl('image'))->toEndWith('/storage/rewards/legacy.jpg');

        $reward->addMedia(UploadedFile::fake()->image('tumbler.jpg', 1600, 1200))->toMediaCollection('image');
        $media = $reward->refresh()->getFirstMedia('image');

        // Queue is sync in tests, so the conversion has already run.
        expect($media->hasGeneratedConversion('webp'))->toBeTrue()
            ->and($reward->webpImageUrl('image'))->toEndWith('.webp');
    });
});

describe('dashboard', function () {
    beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

    it('renders every widget for every staff role', function (AdminRole $role) {
        $this->actingAs(User::factory()->create()->assignRole($role->value));

        Livewire::test(StationStatsOverview::class)->assertSee('Signed-in listeners today');
        Livewire::test(ListenersChart::class)->assertSee('Daily listeners');
        Livewire::test(TopArticlesWidget::class)->assertSee('Top OZ News');
        Livewire::test(UmamiWidget::class)->assertSee('UMAMI_SHARE_URL');
    })->with([AdminRole::Dev, AdminRole::Editorial, AdminRole::SocialMedia]);

    it('shows the Icecast listeners and peak, apart from the site presence count', function () {
        $this->actingAs(User::factory()->create()->assignRole(AdminRole::Dev->value));

        Livewire::test(StationStatsOverview::class)
            ->assertSee('Listeners (Icecast)')
            ->assertSee('Icecast is not reporting');

        ListenerCount::put($this->station, new IcecastSource(
            title: 'Hatchu!! - Salma Salsabil',
            listeners: 16,
            bitrate: 96,
            listenerPeak: 22,
            streamStartedAt: CarbonImmutable::parse('2026-11-24T13:50:46+08:00'),
        ));

        Livewire::test(StationStatsOverview::class)
            ->assertSeeInOrder(['Listeners (Icecast)', '16', "{$this->station->name} 16"])
            ->assertSeeInOrder(['Peak listeners (Icecast)', '22', 'Since the stream started 24 Nov, 12:50 WIB']);
    });

    it('ranks the most-read articles of the last week', function () {
        $this->actingAs(User::factory()->create()->assignRole(AdminRole::Dev->value));
        $hit = Post::factory()->for($this->station)->published()->create(['title' => 'Big story']);
        $old = Post::factory()->for($this->station)->published()->create(['title' => 'Old news']);
        $today = now('Asia/Jakarta')->toDateString();
        DB::table('post_views')->insert([
            ['post_id' => $hit->id, 'date' => $today, 'views' => 40],
            ['post_id' => $old->id, 'date' => now('Asia/Jakarta')->subDays(20)->toDateString(), 'views' => 999],
        ]);

        Livewire::test(TopArticlesWidget::class)
            ->assertCanSeeTableRecords([$hit])
            ->assertCanNotSeeTableRecords([$old]);
    });

    it('embeds Umami when configured and tracks only public pages', function () {
        config([
            'oz.analytics.umami.share_url' => 'https://umami.example.com/share/abc',
            'oz.analytics.umami.script_url' => 'https://umami.example.com/script.js',
            'oz.analytics.umami.website_id' => 'site-123',
        ]);
        $this->actingAs(User::factory()->create()->assignRole(AdminRole::Dev->value));

        Livewire::test(UmamiWidget::class)->assertSeeHtml('src="https://umami.example.com/share/abc"');

        expect($this->get('/oz-bandung')->getContent())->toContain('data-website-id="site-123"')
            ->and($this->get('/admin')->getContent())->not->toContain('data-website-id');
    });
});
