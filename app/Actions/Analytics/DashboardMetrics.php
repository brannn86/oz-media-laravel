<?php

namespace App\Actions\Analytics;

use App\Actions\Broadcast\ListenerPresence;
use App\Enums\PointReason;
use App\Enums\SongRequestStatus;
use App\Models\PointTransaction;
use App\Models\SongRequest;
use App\Models\Station;
use App\Support\Icecast\ListenerCount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Network-wide numbers for the admin dashboard. Each figure is cached for a
 * minute so a busy studio dashboard never adds load during a live show.
 *
 * Days are WIB days. "Signed-in listeners" counts accounts that earned
 * listening points (one heartbeat = a real minute of playback); "peak live
 * listeners" comes from the presence channel and includes guests.
 */
class DashboardMetrics
{
    private const TTL = 60;

    public const TIMEZONE = 'Asia/Jakarta';

    public function __construct(private readonly ListenerPresence $presence) {}

    /**
     * Listening right now across all active stations; null if Reverb is down.
     */
    public function liveListeners(): ?int
    {
        $total = 0;

        foreach (Station::query()->active()->get() as $station) {
            $count = $this->presence->count($station);

            if ($count === null) {
                return null;
            }

            $total += $count;
        }

        return $total;
    }

    /**
     * Stream listeners according to Icecast (every player, not only the
     * site), per active station: now, and the peak since that stream
     * (re)started. Null when no station's Icecast is reporting.
     *
     * @return array{listeners: int, peak: int, stations: list<array{name: string, listeners: int, peak: int|null, since: string|null}>}|null
     */
    public function icecastAudience(): ?array
    {
        $stations = [];

        foreach (Station::query()->active()->orderBy('name')->get() as $station) {
            $stats = ListenerCount::stats($station);

            if ($stats !== null) {
                $stations[] = ['name' => $station->name, ...$stats];
            }
        }

        if ($stations === []) {
            return null;
        }

        return [
            'listeners' => array_sum(array_column($stations, 'listeners')),
            'peak' => array_sum(array_map(fn (array $station): int => $station['peak'] ?? $station['listeners'], $stations)),
            'stations' => $stations,
        ];
    }

    public function signedInListenersOn(CarbonImmutable $day): int
    {
        return Cache::remember("dashboard:listeners:{$day->toDateString()}", self::TTL, fn (): int => PointTransaction::query()
            ->where('reason', PointReason::Listening)
            ->whereBetween('created_at', $this->dayRange($day))
            ->distinct()
            ->count('user_id'));
    }

    public function peakListenersOn(CarbonImmutable $day): int
    {
        return Cache::remember("dashboard:peak:{$day->toDateString()}", self::TTL, fn (): int => (int) DB::table('station_daily_stats')
            ->where('date', $day->toDateString())
            ->sum('peak_listeners'));
    }

    public function pointsEarnedOn(CarbonImmutable $day): int
    {
        return Cache::remember("dashboard:points:{$day->toDateString()}", self::TTL, fn (): int => (int) PointTransaction::query()
            ->whereIn('reason', PointReason::earningValues())
            ->whereBetween('created_at', $this->dayRange($day))
            ->sum('points'));
    }

    public function pointsEarnedAllTime(): int
    {
        return Cache::remember('dashboard:points:all', self::TTL, fn (): int => (int) PointTransaction::query()
            ->whereIn('reason', PointReason::earningValues())
            ->sum('points'));
    }

    public function openSongRequests(): int
    {
        return Cache::remember('dashboard:requests:open', self::TTL, fn (): int => SongRequest::query()
            ->whereIn('status', SongRequestStatus::openValues())
            ->count());
    }

    /**
     * Last N days, oldest first.
     *
     * @return list<array{date: string, label: string, signed_in: int, peak: int}>
     */
    public function dailySeries(int $days = 14): array
    {
        $today = $this->today();
        $series = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $today->subDays($i);
            $series[] = [
                'date' => $day->toDateString(),
                'label' => $day->format('d M'),
                'signed_in' => $this->signedInListenersOn($day),
                'peak' => $this->peakListenersOn($day),
            ];
        }

        return $series;
    }

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dayRange(CarbonImmutable $day): array
    {
        $start = $day->startOfDay()->setTimezone(config('app.timezone'));

        return [$start, $start->addDay()->subSecond()];
    }
}
