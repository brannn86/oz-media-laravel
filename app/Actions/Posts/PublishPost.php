<?php

namespace App\Actions\Posts;

use App\Enums\PostStatus;
use App\Models\Post;

class PublishPost
{
    /**
     * Publishes immediately unless a future publish date was already set.
     */
    public function handle(Post $post): Post
    {
        $post->status = PostStatus::Published;
        $post->published_at ??= now();
        $post->save();

        return $post;
    }

    public function unpublish(Post $post): Post
    {
        $post->status = PostStatus::Draft;
        $post->save();

        return $post;
    }
}
