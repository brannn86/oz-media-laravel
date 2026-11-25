<?php

namespace App\Actions\Analytics;

use App\Enums\AudienceMode;
use App\Models\Announcer;
use App\Models\ProgramSchedule;
use App\Models\Station;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Audience per show and per host over the last N days: every Icecast sample
 * (15 min) and every minute of website listening is credited to the show on
 * air at that moment, by the current weekly schedule (WIB). A show moved in
 * the schedule since is credited by where it is now.
 *
 * Cached for an hour: it walks every sample and minute of the window.
 */
class ProgramAudience
{
    private const TTL = 3600;

    private const MINUTES_PER_WEEK = 10080;

    /**
     * @return list<array{program_id: int, program: string, station: string, hosts: string, airtime_hours: float, avg_icecast: float|null, peak_icecast: int|null, avg_website: float, website_hours: float, tune_outs_per_hour: float, early_tune_outs: int}>
     */
    public function programs(int $days = 30): array
    {
        return Cache::remember("analytics:program-audience:v2:{$days}", self::TTL, function () use ($days): array {
            $rows = [];

            foreach (Station::query()->orderBy('name')->get() as $station) {
                foreach ($this->forStation($station, $days) as $row) {
                    $rows[] = $row;
                }
            }

            usort($rows, fn (array $a, array $b): int => ($b['avg_icecast'] ?? -1) <=> ($a['avg_icecast'] ?? -1)
                ?: $b['avg_website'] <=> $a['avg_website']);

            return $rows;
        });
    }

    /**
     * Each host's shows together, weighted by airtime.
     *
     * @return list<array{host: string, shows: string, airtime_hours: float, avg_icecast: float|null, avg_website: float, website_hours: float}>
     */
    public function hosts(int $days = 30): array
    {
        return Cache::remember("analytics:host-audience:v2:{$days}", self::TTL, function () use ($days): array {
            $programs = collect($this->programs($days))->keyBy('program_id');
            $hosts = [];

            $announcers = Announcer::query()
                ->where('is_active', true)
                ->with(['programs' => fn ($query) => $query->where('is_active', true)])
                ->get();

            foreach ($announcers as $announcer) {
                $shows = $announcer->programs->map(fn ($program) => $programs->get($program->id))->filter();

                if ($shows->isEmpty()) {
                    continue;
                }

                $airtime = $shows->sum('airtime_hours');
                $withIcecast = $shows->filter(fn (array $show): bool => $show['avg_icecast'] !== null);
                $icecastAirtime = $withIcecast->sum('airtime_hours');

                $hosts[] = [
                    'host' => $announcer->stage_name ?: $announcer->name,
                    'shows' => $shows->pluck('program')->implode(', '),
                    'airtime_hours' => round($airtime, 1),
                    'avg_icecast' => $icecastAirtime > 0
                        ? round($withIcecast->sum(fn (array $show): float => $show['avg_icecast'] * $show['airtime_hours']) / $icecastAirtime, 1)
                        : null,
                    'avg_website' => $airtime > 0 ? round($shows->sum('website_hours') * 60 / ($airtime * 60), 2) : 0.0,
                    'website_hours' => round($shows->sum('website_hours'), 1),
                ];
            }

            usort($hosts, fn (array $a, array $b): int => ($b['avg_icecast'] ?? -1) <=> ($a['avg_icecast'] ?? -1)
                ?: $b['avg_website'] <=> $a['avg_website']);

            return $hosts;
        });
    }

    /**
     * @return list<array{program_id: int, program: string, station: string, hosts: string, airtime_hours: float, avg_icecast: float|null, peak_icecast: int|null, avg_website: float, website_hours: float, tune_outs_per_hour: float, early_tune_outs: int}>
     */
    private function forStation(Station $station, int $days): array
    {
        $slots = $this->slots($station);

        if ($slots->isEmpty()) {
            return [];
        }

        // Minute of the week (Sunday 00:00 = 0, WIB) => program id, and how far into its slot.
        $week = array_fill(0, self::MINUTES_PER_WEEK, null);
        $into = array_fill(0, self::MINUTES_PER_WEEK, 0);

        foreach ($slots as $slot) {
            $offset = $slot->day_of_week->value * 1440;

            for ($minute = $slot->startMinutes(); $minute < $slot->endMinutes(); $minute++) {
                $week[$offset + $minute] = $slot->program_id;
                $into[$offset + $minute] = $minute - $slot->startMinutes();
            }
        }

        $now = CarbonImmutable::now();
        $from = CarbonImmutable::now(DashboardMetrics::TIMEZONE)->startOfDay()->subDays($days - 1);
        $weekMinute = function (mixed $at): int {
            $wib = CarbonImmutable::parse((string) $at, config('app.timezone'))->setTimezone(DashboardMetrics::TIMEZONE);

            return $wib->dayOfWeek * 1440 + $wib->hour * 60 + $wib->minute;
        };
        $programOf = fn (mixed $at): ?int => $week[$weekMinute($at)];

        $stats = [];
        $blank = ['airtime' => 0, 'samples' => 0, 'sample_sum' => 0, 'peak' => null, 'listener_minutes' => 0, 'tune_outs' => 0, 'early_tune_outs' => 0];

        // Airtime: every scheduled minute of the window that has already passed.
        for ($day = $from; $day->lessThanOrEqualTo($now); $day = $day->addDay()) {
            foreach ($slots as $slot) {
                if ($slot->day_of_week->value !== $day->dayOfWeek) {
                    continue;
                }

                $start = $day->addMinutes($slot->startMinutes());
                $end = $day->addMinutes($slot->endMinutes())->min($now);

                if ($end->greaterThan($start)) {
                    $stats[$slot->program_id] ??= $blank;
                    $stats[$slot->program_id]['airtime'] += (int) $start->diffInMinutes($end);
                }
            }
        }

        $range = [$from->setTimezone(config('app.timezone')), $now];

        foreach (DB::table('icecast_samples')->where('station_id', $station->id)->whereBetween('sampled_at', $range)->cursor() as $sample) {
            if ($program = $programOf($sample->sampled_at)) {
                $stats[$program] ??= $blank;
                $stats[$program]['samples']++;
                $stats[$program]['sample_sum'] += (int) $sample->listeners;
                $stats[$program]['peak'] = max($stats[$program]['peak'] ?? 0, (int) $sample->listeners);
            }
        }

        foreach (DB::table('audience_minutes')->where('station_id', $station->id)->where('mode', AudienceMode::Audio->value)
            ->whereBetween('minute_at', $range)->cursor() as $minute) {
            if ($program = $programOf($minute->minute_at)) {
                $stats[$program] ??= $blank;
                $stats[$program]['listener_minutes'] += (int) $minute->listeners;
            }
        }

        // Tune-outs: finished radio sessions, by the show on air when they ended.
        foreach (DB::table('audience_sessions')->where('station_id', $station->id)->where('mode', AudienceMode::Audio->value)
            ->whereBetween('last_seen_at', [$range[0], $now->subSeconds(RecordAudience::SESSION_GAP_SECONDS)])
            ->select('last_seen_at')->cursor() as $session) {
            $minute = $weekMinute($session->last_seen_at);

            if ($program = $week[$minute]) {
                $stats[$program] ??= $blank;
                $stats[$program]['tune_outs']++;

                if ($into[$minute] < 10) {
                    $stats[$program]['early_tune_outs']++;
                }
            }
        }

        $programs = $slots->pluck('program')->unique('id')->keyBy('id');
        $rows = [];

        foreach ($stats as $programId => $stat) {
            $program = $programs->get($programId);

            if ($program === null || $stat['airtime'] === 0) {
                continue;
            }

            $rows[] = [
                'program_id' => (int) $programId,
                'program' => $program->title,
                'station' => $station->name,
                'hosts' => $program->announcers->map(fn (Announcer $host): string => $host->stage_name ?: $host->name)->implode(', '),
                'airtime_hours' => round($stat['airtime'] / 60, 1),
                'avg_icecast' => $stat['samples'] > 0 ? round($stat['sample_sum'] / $stat['samples'], 1) : null,
                'peak_icecast' => $stat['peak'],
                'avg_website' => round($stat['listener_minutes'] / $stat['airtime'], 2),
                'website_hours' => round($stat['listener_minutes'] / 60, 1),
                'tune_outs_per_hour' => round($stat['tune_outs'] / ($stat['airtime'] / 60), 2),
                'early_tune_outs' => $stat['early_tune_outs'],
            ];
        }

        return $rows;
    }

    /**
     * @return Collection<int, ProgramSchedule>
     */
    private function slots(Station $station): Collection
    {
        return ProgramSchedule::query()
            ->whereHas('program', fn (Builder $query) => $query->where('station_id', $station->id)->where('is_active', true))
            ->with(['program.announcers' => fn ($query) => $query->where('is_active', true)])
            ->get();
    }
}
