<?php

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Station;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);
    $this->station = Station::where('slug', 'oz-bandung')->first();
});

it('renders every station page inside the station shell', function (string $path, string $component) {
    $this->get("/oz-bandung{$path}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->where('station.slug', 'oz-bandung')
            ->where('station.channel', 'station.oz-bandung')
            ->has('station.stream')
            ->has('broadcast')
            ->has('meta.title'));
})->with([
    'home' => ['', 'station/home'],
    'programs' => ['/programs', 'station/programs'],
    'announcers' => ['/announcers', 'station/announcers'],
    'events' => ['/events', 'station/events'],
    'news' => ['/news', 'station/news/index'],
    'about' => ['/about', 'station/about'],
    'privacy' => ['/privacy', 'station/privacy'],
]);

it('shares what is on air now (WIB) with the player', function () {
    // Monday 07:30 WIB: the seeded OZ Breakfast Club with Raka and Nadia.
    $this->travelTo(Carbon::parse('2026-11-16 07:30', 'Asia/Jakarta'));

    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page
        ->where('broadcast.current.program.title', 'OZ Breakfast Club')
        ->where('broadcast.current.start', '06:00')
        ->where('broadcast.current.end', '10:00')
        ->has('broadcast.current.program.announcers', 2)
        ->where('broadcast.next.program.title', 'Midday Mix'));
});

it('lists programs (with schedule and hosts) and announcers on the home page', function () {
    $hidden = $this->station->programs()->where('title', '!=', 'OZ Breakfast Club')->first();
    $hidden->update(['is_active' => false]);
    $offAir = $this->station->announcers()->first();
    $offAir->update(['is_active' => false]);

    $this->get('/oz-bandung')->assertInertia(fn (Assert $page) => $page
        ->has('programs', $this->station->programs()->active()->count())
        ->where('programs', fn ($programs) => collect($programs)->doesntContain('id', $hidden->id)
            // Earliest show of the day first.
            && collect($programs)->first()['title'] === 'OZ Breakfast Club')
        ->has('programs.0.schedules.0', fn (Assert $slot) => $slot
            ->where('start', '06:00')
            ->etc())
        ->where('programs.0.announcers', fn ($hosts) => collect($hosts)->doesntContain('id', $offAir->id))
        ->has('announcers', $this->station->announcers()->active()->count())
        ->has('announcers.0.programs'));
});

it('only shows published news and 404s drafts and scheduled posts', function () {
    $draft = $this->station->posts()->where('status', PostStatus::Draft)->first();
    $scheduled = $this->station->posts()->where('published_at', '>', now())->first();
    $live = $this->station->posts()->published()->first();

    $this->get("/oz-bandung/news/{$draft->slug}")->assertNotFound();
    $this->get("/oz-bandung/news/{$scheduled->slug}")->assertNotFound();
    $this->get("/oz-bandung/news/{$live->slug}")->assertOk();

    $this->get('/oz-bandung/news')->assertInertia(fn (Assert $page) => $page
        ->has('posts.data', 6)
        ->where('posts.meta.current_page', 1));
});

it('renders OpenGraph tags server-side for articles', function () {
    $post = $this->station->posts()->published()->first();
    $post->update(['meta_description' => 'Share preview text', 'featured_image' => 'posts/cover.jpg']);

    $html = $this->get("/oz-bandung/news/{$post->slug}")->assertOk()->getContent();

    expect($html)
        ->toContain('<meta property="og:type" content="article">')
        ->toContain('<meta property="og:title" content="'.e($post->title).' | OZ Bandung">')
        ->toContain('<meta name="description" content="Share preview text">')
        ->toContain('/storage/posts/cover.jpg')
        ->toContain('article:published_time')
        // The tag's attributes differ with and without SSR; the text must not.
        ->toMatch('#<title[^>]*>'.preg_quote(e($post->title), '#').' \| OZ Bandung</title>#');
});

it('strips scripts from CMS HTML before it reaches the page', function () {
    $post = Post::factory()->for($this->station)->published()->create([
        'body' => '<p>Hello</p><script>alert(1)</script><img src="x" onerror="alert(2)">',
    ]);

    $this->get("/oz-bandung/news/{$post->slug}")->assertInertia(fn (Assert $page) => $page
        ->where('post.body', fn (string $body) => str_contains($body, '<p>Hello</p>')
            && ! str_contains($body, '<script')
            && ! str_contains($body, 'onerror')));
});

it('404s unknown and inactive stations', function () {
    $this->get('/oz-nowhere')->assertNotFound();

    $this->station->update(['is_active' => false]);
    $this->get('/oz-bandung')->assertNotFound();
});

it('does not let the station route swallow app paths', function () {
    $this->get('/admin/login')->assertOk();
    $this->get('/login')->assertOk();
    $this->get('/up')->assertOk();
});

it('renders the corporate landing page with the network', function () {
    config(['oz.network.partners' => [['name' => 'OZ Bali', 'tagline' => 'Bali', 'url' => null]]]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('corporate/home')
            ->where('stations.0.slug', 'oz-bandung')
            ->where('partners.0.name', 'OZ Bali')
            ->where('partners.0.url', null)
            ->has('stories', 4)
            ->where('stories.0.station.slug', 'oz-bandung'));
});

it('tells the privacy page what the site measures and for how long', function () {
    config(['oz.retention.visitor_analytics_months' => 13, 'oz.analytics.umami.script_url' => null]);

    $this->get('/oz-bandung/privacy')->assertInertia(fn (Assert $page) => $page
        ->component('station/privacy')
        ->where('measurement.retentionMonths', 13)
        ->where('measurement.webAnalytics', false));
});
