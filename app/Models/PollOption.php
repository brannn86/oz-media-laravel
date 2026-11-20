<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $poll_id
 * @property string $label
 * @property bool $is_correct Trivia only.
 * @property int $sort_order
 * @property int $votes_count
 */
#[Fillable(['poll_id', 'label', 'is_correct', 'sort_order'])]
class PollOption extends Model
{
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'sort_order' => 'integer',
            'votes_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Poll, $this>
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }
}
