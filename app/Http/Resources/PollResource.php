<?php

namespace App\Http\Resources;

use App\Enums\PollType;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A poll with live results. For trivia, the right answer is only revealed
 * once the poll has closed.
 *
 * @mixin Poll
 */
class PollResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total = (int) $this->options->sum('votes_count');
        $reveal = $this->type === PollType::Trivia && ! $this->isLive();

        return [
            'id' => $this->id,
            'question' => $this->question,
            'type' => $this->type->value,
            'type_label' => $this->type->getLabel(),
            'status' => $this->status->value,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'total_votes' => $total,
            'options' => $this->options->map(fn (PollOption $option): array => [
                'id' => $option->id,
                'label' => $option->label,
                'votes' => $option->votes_count,
                'percent' => $total > 0 ? (int) round($option->votes_count / $total * 100) : 0,
                'is_correct' => $reveal ? $option->is_correct : null,
            ])->values()->all(),
        ];
    }
}
