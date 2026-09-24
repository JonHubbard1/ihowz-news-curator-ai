<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Models\Story;
use App\Services\AiFactoryService;
use App\Services\DiscoveryFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryUsPrescreenTest extends TestCase
{
    use RefreshDatabase;

    private function pendingStory(string $headline, string $snippet = ''): Story
    {
        return Story::create([
            'url' => 'https://example.com/us-prescreen-'.uniqid(),
            'headline' => $headline,
            'source' => 'AP News',
            'snippet' => $snippet,
            'status' => Story::STATUS_PENDING,
            'trigger_keyword' => 'PRS',
        ]);
    }

    public function test_obvious_us_story_is_skipped_without_calling_the_model(): void
    {
        AiSetting::current()->update(['discovery_filter_mode' => 'enforce']);
        $story = $this->pendingStory('White House Pushes New Housing Bill Through Congress');

        $this->mock(AiFactoryService::class)->shouldNotReceive('chatArray');

        app(DiscoveryFilterService::class)->filterStory($story, 'enforce');

        $story->refresh();
        $this->assertSame(Story::STATUS_SKIPPED, $story->status);
        $this->assertSame('skip', $story->filter_decision);
        $this->assertSame('deterministic', $story->filter_source);
        $this->assertFalse($story->uk_relevant);
        $this->assertStringContainsString('White House', $story->filter_reason);
    }

    public function test_prescreen_runs_in_advisory_mode_too_but_hides_nothing(): void
    {
        $story = $this->pendingStory('California Rent Control Scheme Clears State Senate', 'New rules apply to buildings in Los Angeles.');

        $this->mock(AiFactoryService::class)->shouldNotReceive('chatArray');

        app(DiscoveryFilterService::class)->filterStory($story, 'advisory');

        $story->refresh();
        $this->assertSame(Story::STATUS_PENDING, $story->status);
        $this->assertSame('skip', $story->filter_decision);
        $this->assertSame('deterministic', $story->filter_source);
    }

    public function test_uk_headline_falls_through_to_the_model(): void
    {
        $story = $this->pendingStory('Section 21 Abolition Date Confirmed for England');

        $this->mock(AiFactoryService::class)
            ->shouldReceive('chatArray')
            ->once()
            ->andReturn([
                'choices' => [['message' => ['content' => json_encode(['keep' => true, 'uk_relevant' => true, 'confidence' => 0.9, 'reason' => 'Core PRS'])]]],
                'usage' => ['prompt_tokens' => 400, 'completion_tokens' => 20],
            ]);

        app(DiscoveryFilterService::class)->filterStory($story, 'enforce');

        $this->assertSame('llm', $story->fresh()->filter_source);
    }

    public function test_detector_matches_signals_in_the_snippet_and_returns_the_signal(): void
    {
        $signal = DiscoveryFilterService::deterministicUsSignal(
            'Landlord trade body hits back at ministers',
            'The row echoes a fight over rent caps in the US Senate.'
        );

        $this->assertSame('US Senate', $signal);
    }

    public function test_detector_ignores_plain_uk_copy(): void
    {
        $this->assertNull(DiscoveryFilterService::deterministicUsSignal(
            'HMO Licensing Boom Costs Councils Dear',
            'More than 40 local authorities now run selective licensing schemes.'
        ));
    }
}
