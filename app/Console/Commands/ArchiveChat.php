<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveOldChatMessages;
use Illuminate\Console\Command;

class ArchiveChat extends Command
{
    protected $signature = 'oz:archive-chat {--now : Run here instead of on the maintenance queue}';

    protected $description = 'Archive old live-chat messages and purge long-deleted ones';

    public function handle(): int
    {
        if ($this->option('now')) {
            ArchiveOldChatMessages::dispatchSync();
            $this->components->info('Chat archived.');
        } else {
            ArchiveOldChatMessages::dispatch();
            $this->components->info('Queued on the '.config('oz.queues.maintenance').' queue.');
        }

        return self::SUCCESS;
    }
}
