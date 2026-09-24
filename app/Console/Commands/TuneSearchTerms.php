<?php

namespace App\Console\Commands;

use App\Models\AiSetting;
use App\Models\SearchTerm;
use App\Services\TriageStatsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('news:tune-search-terms')]
#[Description('Set search term priorities from recent accept rates when auto-tuning is enabled')]
class TuneSearchTerms extends Command
{
    private const MIN_JUDGED_SAMPLES = 10;

    public function handle(TriageStatsService $stats): int
    {
        $settings = AiSetting::current();

        if (! $settings->auto_tune_search_terms) {
            $this->info('Auto-tuning of search terms is disabled. Nothing changed.');

            return self::SUCCESS;
        }

        $keywordStats = $stats->keywordStats();
        $tuned = 0;

        foreach (SearchTerm::all() as $term) {
            $stat = $keywordStats[strtolower($term->phrase)] ?? null;

            if (! $stat || $stat['judged'] < self::MIN_JUDGED_SAMPLES || $stat['accept_rate'] === null) {
                continue;
            }

            $priority = (int) max(0, min(9999, round($stat['accept_rate'] * 100)));

            if ((int) $term->priority !== $priority) {
                $term->update(['priority' => $priority]);
                $tuned++;
            }
        }

        $this->info("Tuned {$tuned} search term(s) from accept rates (minimum ".self::MIN_JUDGED_SAMPLES.' judged stories per term).');

        return self::SUCCESS;
    }
}
