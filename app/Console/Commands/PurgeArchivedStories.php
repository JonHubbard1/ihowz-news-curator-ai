<?php

namespace App\Console\Commands;

use App\Models\Story;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('news:purge-archived')]
#[Description('Delete scrapped stories archived for more than 90 days, and assistant-skipped stories older than 7 days')]
class PurgeArchivedStories extends Command
{
    public function handle(): int
    {
        $deleted = Story::forStatus(Story::STATUS_SCRAPPED)
            ->whereNotNull('archived_at')
            ->where('archived_at', '<', now()->subDays(90))
            ->delete();

        $this->info("Deleted {$deleted} archived scrapped stories.");

        $skipped = Story::forStatus(Story::STATUS_SKIPPED)
            ->where('created_at', '<', now()->subDays(7))
            ->delete();

        $this->info("Deleted {$skipped} assistant-skipped stories.");

        return self::SUCCESS;
    }
}
