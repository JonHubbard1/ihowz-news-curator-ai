<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Models\SearchTerm;
use App\Models\Story;
use App\Services\TriageStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordStatsTest extends TestCase
{
    use RefreshDatabase;

    private function story(string $keyword, string $status): Story
    {
        return Story::create([
            'url' => 'https://example.com/'.uniqid(),
            'headline' => 'Story about '.$keyword,
            'status' => $status,
            'trigger_keyword' => $keyword,
        ]);
    }

    public function test_accept_rate_counts_only_judged_stories(): void
    {
        $this->story('Section 21', Story::STATUS_USED);
        $this->story('Section 21', Story::STATUS_PUBLISHED);
        $this->story('Section 21', Story::STATUS_DRAFT);
        $this->story('Section 21', Story::STATUS_SCRAPPED);
        $this->story('Section 21', Story::STATUS_PENDING);
        $this->story('Section 21', Story::STATUS_SKIPPED);

        $stats = app(TriageStatsService::class)->keywordStats();

        $this->assertArrayHasKey('section 21', $stats);
        $this->assertSame(3, $stats['section 21']['used']);
        $this->assertSame(1, $stats['section 21']['scrapped']);
        $this->assertSame(4, $stats['section 21']['judged']);
        $this->assertEqualsWithDelta(0.75, $stats['section 21']['accept_rate'], 0.001);
    }

    public function test_tune_command_sets_priority_from_accept_rate(): void
    {
        AiSetting::current()->update(['auto_tune_search_terms' => true]);
        $term = SearchTerm::create(['phrase' => 'Section 21', 'is_active' => true, 'priority' => 1]);

        for ($i = 0; $i < 8; $i++) {
            $this->story('Section 21', Story::STATUS_USED);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->story('Section 21', Story::STATUS_SCRAPPED);
        }

        $this->artisan('news:tune-search-terms')->assertExitCode(0);

        $this->assertSame(80, (int) $term->fresh()->priority);
    }

    public function test_tune_command_leaves_terms_below_minimum_samples_alone(): void
    {
        AiSetting::current()->update(['auto_tune_search_terms' => true]);
        $term = SearchTerm::create(['phrase' => 'HMO Licence', 'is_active' => true, 'priority' => 42]);

        $this->story('HMO Licence', Story::STATUS_USED);
        $this->story('HMO Licence', Story::STATUS_USED);
        $this->story('HMO Licence', Story::STATUS_SCRAPPED);

        $this->artisan('news:tune-search-terms')->assertExitCode(0);

        $this->assertSame(42, (int) $term->fresh()->priority);
    }

    public function test_tune_command_is_a_noop_when_auto_tuning_is_off(): void
    {
        $term = SearchTerm::create(['phrase' => 'Section 21', 'is_active' => true, 'priority' => 7]);

        foreach (range(1, 10) as $i) {
            $this->story('Section 21', $i <= 9 ? Story::STATUS_USED : Story::STATUS_SCRAPPED);
        }

        $this->artisan('news:tune-search-terms')->assertExitCode(0);

        $this->assertSame(7, (int) $term->fresh()->priority);
    }
}
