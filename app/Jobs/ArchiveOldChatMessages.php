<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the live chat table small:
 * - moderated (soft-deleted) messages are removed for good after a while;
 * - everything older than the archive age moves to chat_message_archives.
 *
 * Works in chunks so it never locks the table for long during a live show.
 */
class ArchiveOldChatMessages implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    private const CHUNK = 1000;

    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue(config('oz.queues.maintenance'));
    }

    public function handle(): void
    {
        $this->purgeDeleted();
        $this->archiveOld();
    }

    private function purgeDeleted(): void
    {
        $cutoff = now()->subDays((int) config('oz.retention.purge_deleted_chat_after_days'));

        do {
            $ids = ChatMessage::onlyTrashed()->where('deleted_at', '<', $cutoff)->limit(self::CHUNK)->pluck('id');
            ChatMessage::onlyTrashed()->whereKey($ids)->forceDelete();
        } while ($ids->count() === self::CHUNK);
    }

    private function archiveOld(): void
    {
        $cutoff = now()->subDays((int) config('oz.retention.archive_chat_after_days'));
        $columns = ['id', 'station_id', 'user_id', 'body', 'is_pinned', 'deleted_at', 'created_at', 'updated_at'];

        do {
            $ids = ChatMessage::withTrashed()
                ->where('created_at', '<', $cutoff)
                ->where('is_pinned', false)
                ->orderBy('id')
                ->limit(self::CHUNK)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            DB::transaction(function () use ($ids, $columns): void {
                DB::table('chat_message_archives')->insertUsing(
                    $columns,
                    DB::table('chat_messages')->select($columns)->whereIn('id', $ids),
                );
                DB::table('chat_messages')->whereIn('id', $ids)->delete();
            });
        } while ($ids->count() === self::CHUNK);
    }
}
