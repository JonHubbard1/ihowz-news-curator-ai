<?php

namespace App\Console\Commands;

use App\Services\PreferenceDigestService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('news:preference-digest {--window= : Days of used/scrapped history to learn from (default 30)}')]
#[Description('Rewrite the assistant taste profile from recent triage history')]
class RunPreferenceDigest extends Command
{
    public function handle(PreferenceDigestService $digest): int
    {
        $window = $this->option('window');
        $window = $window !== null ? (int) $window : null;

        $profile = $digest->run($window);

        if (! $profile) {
            $this->info('No used or scrapped stories in the window — taste profile left unchanged.');

            return self::SUCCESS;
        }

        $this->info("Recorded a new taste profile from {$profile->story_count} stories (window: {$profile->window_days} days, model: {$profile->model}).");

        return self::SUCCESS;
    }
}
