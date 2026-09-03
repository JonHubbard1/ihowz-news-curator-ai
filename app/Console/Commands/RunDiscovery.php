<?php

namespace App\Console\Commands;

use App\Services\DiscoveryService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('news:discover')]
#[Description('Scrape Google News and RSS feeds for PRS stories')]
class RunDiscovery extends Command
{
    public function handle(DiscoveryService $discovery): int
    {
        $this->info('Starting discovery...');
        $stories = $discovery->discover();
        $saved = $discovery->save($stories);
        $this->info("Discovered ".count($stories)." stories, saved {$saved} new ones.");

        return self::SUCCESS;
    }
}
