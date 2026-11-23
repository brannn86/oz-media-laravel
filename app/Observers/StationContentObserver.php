<?php

namespace App\Observers;

use App\Models\Announcer;
use App\Models\Program;
use App\Models\ProgramSchedule;
use App\Models\Station;
use App\Support\StationCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Clears a station's public cache whenever something it shows changes:
 * the station itself, programs, schedule slots, hosts, news, events and
 * social highlights (registered in AppServiceProvider).
 */
class StationContentObserver
{
    public function saved(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    private function flush(Model $model): void
    {
        $stationId = match (true) {
            $model instanceof Station => $model->getKey(),
            $model instanceof ProgramSchedule => $model->program?->station_id,
            default => $model->getAttribute('station_id'),
        };

        StationCache::flush(is_numeric($stationId) ? (int) $stationId : null);

        // Moving a program or host to another station affects the old one too.
        if (($model instanceof Program || $model instanceof Announcer) && $model->wasChanged('station_id')) {
            $original = $model->getOriginal('station_id');
            StationCache::flush(is_numeric($original) ? (int) $original : null);
        }
    }
}
