<?php

namespace App\Console\Commands;

use App\Actions\Analytics\RecordAudience;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('analytics:flush-audience')]
#[Description('Save the last minutes of website listeners and viewers (from the player pings) to the database')]
class FlushAudienceCommand extends Command
{
    public function handle(RecordAudience $audience): int
    {
        $this->components->info($audience->flush().' minute rows saved.');

        return self::SUCCESS;
    }
}
