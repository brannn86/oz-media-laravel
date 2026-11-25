<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes per-visitor analytics rows past the retention the privacy page
 * promises. Anonymous totals (minutes, samples, reads, reactions) stay.
 */
class PruneVisitorAnalytics extends Command
{
    protected $signature = 'analytics:prune-visitors';

    protected $description = 'Delete per-visitor analytics rows older than the stated retention';

    public function handle(): int
    {
        $cutoff = now()->subMonths((int) config('oz.retention.visitor_analytics_months'));
        $sessions = 0;

        // In batches, so a large backlog doesn't hold one long delete.
        do {
            $ids = DB::table('audience_sessions')->where('last_seen_at', '<', $cutoff)->limit(5000)->pluck('id');
            $sessions += DB::table('audience_sessions')->whereIn('id', $ids)->delete();
        } while ($ids->isNotEmpty());

        $visitors = DB::table('audience_visitors')->where('date', '<', $cutoff->toDateString())->delete();

        $this->components->info("Deleted {$sessions} sessions and {$visitors} daily visitor rows before {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
