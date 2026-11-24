<?php

use App\Actions\Posts\PublishPost;
use App\Enums\AdminRole;
use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\OzBandungSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, OzBandungSeeder::class]);
    $this->station = Station::where('slug', 'oz-bandung')->first();
});

function publishedPost(Station $station, array $attributes = []): Post
{
    return Post::factory()->for($station)->create([
        'status' => PostStatus::Published,
        'published_at' => now()->subDay(),
        ...$attributes,
    ]);
}

it('shows the section, byline, caption, credit and source on an article', function () {
    $author = User::factory()->create(['name' => 'Sari Wulandari']);
    $post = publishedPost($this->station, [
        'author_id' => $author->id,
        'category' => PostCategory::Sports,
        'image_caption' => 'Persib fans fill the stands at GBLA.',
        'image_credit' => 'OZ Bandung/Raka',
        'source_name' => 'Persib.co.id',
        'source_url' => 'https://persib.co.id/news',
    ]);

    $this->get("/oz-bandung/news/{$post->slug}")->assertInertia(fn (Assert $page) => $page
        ->component('station/news/show')
        ->where('post.category', ['value' => 'sports', 'label' => 'Sports'])
        ->where('post.author', 'Sari Wulandari')
        ->where('post.image_caption', 'Persib fans fill the stands at GBLA.')
        ->where('post.image_credit', 'OZ Bandung/Raka')
        ->where('post.source', ['name' => 'Persib.co.id', 'url' => 'https://persib.co.id/news'])
        ->where('post.updated_at', null));
});

it('only links web sources and shows anything else as text', function () {
    $post = publishedPost($this->station, [
        'source_name' => 'Press release',
        'source_url' => 'javascript:alert(1)',
    ]);

    $this->get("/oz-bandung/news/{$post->slug}")->assertInertia(fn (Assert $page) => $page
        ->where('post.source', ['name' => 'Press release', 'url' => null]));
});

it('shows an updated time only when the article was edited after publishing', function () {
    $post = publishedPost($this->station, ['published_at' => now()->subHours(3)]);

    $this->get("/oz-bandung/news/{$post->slug}")->assertInertia(fn (Assert $page) => $page
        ->where('post.updated_at', null));

    $this->travel(1)->hours();
    $post->update(['title' => 'Corrected headline']);

    $this->get("/oz-bandung/news/{$post->slug}")->assertInertia(fn (Assert $page) => $page
        ->where('post.updated_at', $post->fresh()->updated_at->toIso8601String()));
});

it('recirculates more stories from the same section first', function () {
    $post = publishedPost($this->station, ['category' => PostCategory::Tech]);
    $sameSection = publishedPost($this->station, ['category' => PostCategory::Tech]);

    $this->get("/oz-bandung/news/{$post->slug}")->assertInertia(fn (Assert $page) => $page
        ->has('more', 4)
        ->where('more.0.id', $sameSection->id)
        ->where('more', fn ($more) => collect($more)->doesntContain('id', $post->id)));
});

it('ranks the most read stories of the last week', function () {
    [$popular, $steady, $old] = [publishedPost($this->station), publishedPost($this->station), publishedPost($this->station)];
    $today = now('Asia/Jakarta')->toDateString();

    PostView::insert([
        ['post_id' => $popular->id, 'date' => $today, 'views' => 50],
        ['post_id' => $steady->id, 'date' => $today, 'views' => 10],
        // A hit from a month ago doesn't count any more.
        ['post_id' => $old->id, 'date' => now('Asia/Jakarta')->subMonth()->toDateString(), 'views' => 999],
    ]);

    $this->get("/oz-bandung/news/{$steady->slug}")->assertInertia(fn (Assert $page) => $page
        ->has('mostRead', 2)
        ->where('mostRead.0.id', $popular->id)
        ->where('mostRead.1.id', $steady->id));
});

it('filters the news list by section and 404s unknown sections', function () {
    $sports = publishedPost($this->station, ['category' => PostCategory::Sports]);

    $this->get('/oz-bandung/news?category=sports')->assertInertia(fn (Assert $page) => $page
        ->where('category', ['value' => 'sports', 'label' => 'Sports'])
        ->has('categories', count(PostCategory::cases()))
        ->has('posts.data', 1)
        ->where('posts.data.0.id', $sports->id));

    $this->get('/oz-bandung/news?category=gossip')->assertNotFound();
});

it('bylines an article to the admin who publishes it when no author was picked', function () {
    $editor = User::factory()->create();
    $editor->assignRole(AdminRole::Dev->value);
    $post = Post::factory()->for($this->station)->create(['author_id' => null]);

    $this->actingAs($editor);
    app(PublishPost::class)->handle($post);

    expect($post->fresh()->author_id)->toBe($editor->id);
});
