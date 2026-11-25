<?php

namespace App\Actions\Analytics;

use App\Enums\AgeRange;
use App\Enums\Gender;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Who the listeners are, from the sign-up details (age range, gender,
 * location): how many accounts have each value, and how many of them
 * listened or watched in the window (their sessions carry a copy of the
 * details, see RecordAudience). Guests and listeners without details are
 * not in these numbers; coverage() says how much of the listening that is.
 */
class ListenerProfileMetrics
{
    private const TTL = 900;

    /** Areas with fewer people than this (accounts and listeners) go into "Other areas", so nobody is singled out. */
    public const MIN_PEOPLE_PER_AREA = 5;

    /** Breakdowns: column => heading. */
    public const DIMENSIONS = [
        'age_range' => 'Age',
        'gender' => 'Gender',
    ];

    /**
     * One row per option (in the enum's order, empty ones included).
     *
     * @return list<array{label: string, accounts: int, listeners: int, hours: float}>
     */
    public function breakdown(string $dimension, int $days): array
    {
        $enum = match ($dimension) {
            'age_range' => AgeRange::class,
            'gender' => Gender::class,
            default => null,
        };

        if ($enum === null) {
            return [];
        }

        return Cache::remember("analytics:profile:{$dimension}:{$days}", self::TTL, function () use ($dimension, $enum, $days): array {
            $accounts = $this->accounts($dimension);
            $listening = $this->listening($dimension, $days);

            return array_map(fn ($case): array => [
                'label' => $case->getLabel(),
                'accounts' => $accounts[$case->value] ?? 0,
                'listeners' => $listening[$case->value]['listeners'] ?? 0,
                'hours' => $listening[$case->value]['hours'] ?? 0.0,
            ], $enum::cases());
        });
    }

    /**
     * Areas by people (accounts or listeners, whichever is more), small ones
     * merged into "Other areas" at the end.
     *
     * @return list<array{label: string, accounts: int, listeners: int, hours: float}>
     */
    public function locations(int $days): array
    {
        return Cache::remember("analytics:profile:location:{$days}", self::TTL, function () use ($days): array {
            $accounts = $this->accounts('location');
            $listening = $this->listening('location', $days);
            $rows = [];
            $other = ['label' => 'Other areas', 'accounts' => 0, 'listeners' => 0, 'hours' => 0.0];

            foreach (array_unique([...array_keys($accounts), ...array_keys($listening)]) as $place) {
                $row = [
                    'label' => (string) $place,
                    'accounts' => $accounts[$place] ?? 0,
                    'listeners' => $listening[$place]['listeners'] ?? 0,
                    'hours' => $listening[$place]['hours'] ?? 0.0,
                ];

                if (max($row['accounts'], $row['listeners']) < self::MIN_PEOPLE_PER_AREA) {
                    $other['accounts'] += $row['accounts'];
                    $other['listeners'] += $row['listeners'];
                    $other['hours'] += $row['hours'];
                } else {
                    $rows[] = $row;
                }
            }

            usort($rows, fn (array $a, array $b): int => [$b['listeners'], $b['accounts']] <=> [$a['listeners'], $a['accounts']]);

            if ($other['accounts'] + $other['listeners'] > 0) {
                $rows[] = [...$other, 'hours' => round($other['hours'], 1)];
            }

            return $rows;
        });
    }

    /**
     * How much of the window's listening the breakdowns cover: sessions by
     * signed-in listeners with details, out of all sessions.
     *
     * @return array{sessions: int, profiled: int}
     */
    public function coverage(int $days): array
    {
        return Cache::remember("analytics:profile:coverage:{$days}", self::TTL, function () use ($days): array {
            $row = DB::table('audience_sessions')
                ->where('started_at', '>=', $this->since($days))
                ->selectRaw('COUNT(*) as sessions, COUNT(age_range) as profiled')
                ->first();

            return ['sessions' => (int) ($row->sessions ?? 0), 'profiled' => (int) ($row->profiled ?? 0)];
        });
    }

    /**
     * Listener accounts (not staff) per value of $column.
     *
     * @return array<string, int>
     */
    private function accounts(string $column): array
    {
        return User::query()
            ->whereDoesntHave('roles')
            ->whereNotNull($column)
            ->groupBy($column)
            ->select([$column, DB::raw('COUNT(*) as total')])
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->{$column} => (int) $row->total])
            ->all();
    }

    /**
     * People and hours per value of $column, from the window's sessions.
     *
     * @return array<string, array{listeners: int, hours: float}>
     */
    private function listening(string $column, int $days): array
    {
        return DB::table('audience_sessions')
            ->where('started_at', '>=', $this->since($days))
            ->whereNotNull($column)
            ->groupBy($column)
            ->select([$column, DB::raw('COUNT(DISTINCT visitor) as listeners'), DB::raw('SUM(minutes) as minutes')])
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->{$column} => [
                'listeners' => (int) $row->listeners,
                'hours' => round((int) $row->minutes / 60, 1),
            ]])
            ->all();
    }

    private function since(int $days): CarbonImmutable
    {
        return CarbonImmutable::now()->subDays($days);
    }
}
