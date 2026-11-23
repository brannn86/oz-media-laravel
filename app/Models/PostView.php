<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Views of one article on one WIB day (written by the RecordPostView job).
 *
 * @property int $post_id
 * @property string $date
 * @property int $views
 */
#[Table('post_views')]
#[Fillable(['post_id', 'date', 'views'])]
#[WithoutIncrementing]
#[WithoutTimestamps]
class PostView extends Model
{
    protected $primaryKey = 'post_id';

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
