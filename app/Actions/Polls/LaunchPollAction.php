<?php

namespace App\Actions\Polls;

use App\Actions\Concerns\BroadcastsSafely;
use App\Enums\PollStatus;
use App\Enums\PollType;
use App\Events\Live\PollUpdated;
use App\Models\Poll;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Puts a poll on air. One live poll per station: launching closes the
 * previous one first.
 */
class LaunchPollAction
{
    use BroadcastsSafely;

    public function __construct(private readonly ClosePollAction $close) {}

    /**
     * @param  list<array{label: string, is_correct?: bool}>  $options  2 to 4 answers
     *
     * @throws ValidationException
     */
    public function handle(Station $station, ?User $creator, string $question, PollType $type, array $options): Poll
    {
        Validator::make(['question' => $question, 'options' => $options], [
            'question' => ['required', 'string', 'max:200'],
            'options' => ['required', 'array', 'min:2', 'max:4'],
            'options.*.label' => ['required', 'string', 'max:120', 'distinct:ignore_case'],
        ])->validate();

        $correct = collect($options)->filter(fn (array $option): bool => (bool) ($option['is_correct'] ?? false))->count();

        if ($type === PollType::Trivia && $correct !== 1) {
            throw ValidationException::withMessages(['options' => 'A trivia question needs exactly one correct answer.']);
        }

        $station->polls()->live()->get()->each(fn (Poll $live) => $this->close->handle($live));

        $poll = DB::transaction(function () use ($station, $creator, $question, $type, $options): Poll {
            $poll = $station->polls()->create([
                'created_by' => $creator?->id,
                'question' => trim($question),
                'type' => $type,
                'status' => PollStatus::Live,
                'opened_at' => now(),
            ]);

            foreach ($options as $index => $option) {
                $poll->options()->create([
                    'label' => trim($option['label']),
                    'is_correct' => $type === PollType::Trivia && ($option['is_correct'] ?? false),
                    'sort_order' => $index,
                ]);
            }

            return $poll;
        });

        $poll->setRelation('station', $station)->load('options');

        $this->broadcastSafely(new PollUpdated($poll));

        return $poll;
    }
}
