<?php

namespace Tests\Feature;

use App\Models\AiCostLog;
use App\Models\AiSetting;
use App\Models\Story;
use App\Services\AiFactoryService;
use App\Services\PreferenceDigestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferenceDigestTest extends TestCase
{
    use RefreshDatabase;

    private function story(string $status, string $headline, array $attributes = []): Story
    {
        return Story::create(array_merge([
            'url' => 'https://example.com/digest-'.uniqid(),
            'headline' => $headline,
            'source' => 'Property Wire',
            'status' => $status,
        ], $attributes));
    }

    private function fakeProfile(): void
    {
        $this->mock(AiFactoryService::class)
            ->shouldReceive('chatArray')
            ->once()
            ->andReturn([
                'choices' => [['message' => ['content' => "```text\nMUST HAVE: England possession reform.\nAVOID: US domestic housing stories.\n```"]]],
                'usage' => ['prompt_tokens' => 1000, 'completion_tokens' => 500],
            ]);
    }

    public function test_digest_writes_a_profile_and_one_billed_cost_row(): void
    {
        AiSetting::current()->update([
            'llm_input_cost_per_1k' => 0.01,
            'llm_output_cost_per_1k' => 0.02,
            'cost_markup_multiplier' => 5,
        ]);

        $this->story(Story::STATUS_USED, 'Section 21 Repeal Timeline Backed By Ministers');
        $this->story(Story::STATUS_PENDING, 'Boring Oversight Story Rescued By Editor', [
            'filter_source' => 'rescued',
        ]);
        $this->story(Story::STATUS_SCRAPPED, 'California Cap Splits State Court', [
            'scrap_reason' => 'not_uk',
            'archived_at' => now(),
        ]);
        // A scrapped story with no reason chip teaches nothing and must be excluded.
        $this->story(Story::STATUS_SCRAPPED, 'Mystery Scrap Without A Reason', [
            'archived_at' => now(),
        ]);

        $this->fakeProfile();

        $profile = app(PreferenceDigestService::class)->run();

        $this->assertNotNull($profile);
        $this->assertSame(3, $profile->story_count);
        $this->assertStringContainsString('MUST HAVE', $profile->profile);
        $this->assertStringNotContainsString('```', $profile->profile);
        $this->assertDatabaseCount('preference_profiles', 1);

        $cost = AiCostLog::where('operation', 'preference_digest')->sole();
        $this->assertNull($cost->story_id);
        // (1000/1000 × 0.01 + 500/1000 × 0.02) × 5 markup = 0.10
        $this->assertEqualsWithDelta(0.1, (float) $cost->cost_usd, 0.0001);
    }

    public function test_no_history_means_no_llm_call_and_no_profile(): void
    {
        $this->story(Story::STATUS_PENDING, 'Fresh Unjudged Story');

        $this->mock(AiFactoryService::class)->shouldNotReceive('chatArray');

        $this->assertNull(app(PreferenceDigestService::class)->run());
        $this->assertDatabaseCount('preference_profiles', 0);
        $this->assertDatabaseCount('ai_cost_logs', 0);
    }

    public function test_command_exits_cleanly_and_records_a_profile(): void
    {
        AiSetting::current()->update(['discovery_filter_model' => 'gpt-4.1-mini']);
        $this->story(Story::STATUS_USED, 'Right To Rent Scheme Extended');

        $this->fakeProfile();

        $this->artisan('news:preference-digest')->assertExitCode(0);

        $this->assertDatabaseHas('preference_profiles', [
            'story_count' => 1,
            'model' => 'gpt-4.1-mini',
        ]);
    }

    public function test_command_is_content_with_an_empty_window(): void
    {
        $this->mock(AiFactoryService::class)->shouldNotReceive('chatArray');

        $this->artisan('news:preference-digest')->assertExitCode(0);

        $this->assertDatabaseCount('preference_profiles', 0);
    }
}
