<?php

namespace App\Console\Commands;

use App\Models\Story;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('news:purge-archived')]
#[Description('Delete scrapped stories that have been archived for more than 48 hours')]
class PurgeArchivedStories extends Command
{
    public function handle(): int
    {
        $cutoff = now()->subHours(48);

        $deleted = Story::forStatus(Story::STATUS_SCRAPPED)
            ->whereNotNull('archived_at')
            ->where('archived_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} archived scrapped stories.");

        return self::SUCCESS;
    }
}
