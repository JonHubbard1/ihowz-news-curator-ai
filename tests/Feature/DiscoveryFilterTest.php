<?php

namespace Tests\Feature;

use App\Jobs\FilterDiscoveredStory;
use App\Models\AiCostLog;
use App\Models\AiSetting;
use App\Models\Story;
use App\Services\AiFactoryService;
use App\Services\DiscoveryFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function pendingStory(array $attributes = []): Story
    {
        return Story::create(array_merge([
            'url' => 'https://example.com/filter-'.uniqid(),
            'headline' => 'Renters Rights Bill clears another Commons stage',
            'source' => 'Landlord Today',
            'snippet' => 'The bill moved to committee with amendments on ground possession.',
            'status' => Story::STATUS_PENDING,
            'trigger_keyword' => 'Renters Rights Bill',
        ], $attributes));
    }

    private function respondWith(array $decision): void
    {
        $this->mock(AiFactoryService::class)
            ->shouldReceive('chatArray')
            ->andReturn([
                'choices' => [['message' => ['content' => json_encode($decision)]]],
                'usage' => ['prompt_tokens' => 400, 'completion_tokens' => 30],
            ]);
    }

    private function runFilter(Story $story): void
    {
        (new FilterDiscoveredStory($story->id))->handle(app(DiscoveryFilterService::class));
    }

    public function test_enforce_mode_skips_a_rejected_story(): void
    {
        AiSetting::current()->update(['discovery_filter_mode' => 'enforce']);
        $story = $this->pendingStory();
        $this->respondWith(['keep' => false, 'uk_relevant' => true, 'confidence' => 0.9, 'reason' => 'Consultancy funding round, not sector news']);

        $this->runFilter($story);

        $story->refresh();
        $this->assertSame(Story::STATUS_SKIPPED, $story->status);
        $this->assertSame('skip', $story->filter_decision);
        $this->assertSame('llm', $story->filter_source);
        $this->assertSame('Consultancy funding round, not sector news', $story->filter_reason);
        $this->assertSame(0.9, $story->filter_confidence);
        $this->assertTrue($story->uk_relevant);
        $this->assertDatabaseHas('ai_cost_logs', [
            'story_id' => $story->id,
            'operation' => 'discovery_filter',
        ]);
    }

    public function test_advisory_mode_records_without_hiding(): void
    {
        AiSetting::current()->update(['discovery_filter_mode' => 'advisory']);
        $story = $this->pendingStory();
        $this->respondWith(['keep' => false, 'uk_relevant' => true, 'confidence' => 0.7, 'reason' => 'US rental market story']);

        $this->runFilter($story);

        $story->refresh();
        $this->assertSame(Story::STATUS_PENDING, $story->status);
        $this->assertSame('skip', $story->filter_decision);
        $this->assertSame('llm', $story->filter_source);
    }

    public function test_non_uk_story_is_skipped_even_when_kept(): void
    {
        AiSetting::current()->update(['discovery_filter_mode' => 'enforce']);
        $story = $this->pendingStory();
        $this->respondWith(['keep' => true, 'uk_relevant' => false, 'confidence' => 0.95, 'reason' => 'Texas landlord law']);

        $this->runFilter($story);

        $story->refresh();
        $this->assertSame(Story::STATUS_SKIPPED, $story->status);
        $this->assertSame('skip', $story->filter_decision);
        $this->assertFalse($story->uk_relevant);
    }

    public function test_kept_story_stays_pending_in_enforce_mode(): void
    {
        AiSetting::current()->update(['discovery_filter_mode' => 'enforce']);
        $story = $this->pendingStory();
        $this->respondWith(['keep' => true, 'uk_relevant' => true, 'confidence' => 0.9, 'reason' => 'Core PRS legislation']);

        $this->runFilter($story);

        $story->refresh();
        $this->assertSame(Story::STATUS_PENDING, $story->status);
        $this->assertSame('keep', $story->filter_decision);
    }

    public function test_fenced_json_response_still_parses(): void
    {
        AiSetting::current()->update(['discovery_filter_mode' => 'enforce']);
        $story = $this->pendingStory();

        $this->mock(AiFactoryService::class)
            ->shouldReceive('chatArray')
            ->andReturn([
                'choices' => [['message' => ['content' => "```json\n{\"keep\": true, \"uk_relevant\": true, \"confidence\": 0.8, \"reason\": \"On target\"}\n```"]]],
                'usage' => ['prompt_tokens' => 400, 'completion_tokens' => 30],
            ]);

        $this->runFilter($story);

        $story->refresh();
        $this->assertSame(Story::STATUS_PENDING, $story->status);
        $this->assertSame('keep', $story->filter_decision);
        $this->assertSame('llm', $story->filter_source);
    }

    public function test_unparseable_response_fails_open_but_still_costs(): void
    {
        AiSetting::current()->update(['discovery_filter_mode' => 'enforce']);
        $story = $this->pendingStory();

        $this->mock(AiFactoryService::class)
            ->shouldReceive('chatArray')
            ->andReturn([
                'choices' => [['message' => ['content' => 'Sorry, I cannot judge this story.']]],
                'usage' => ['prompt_tokens' => 400, 'completion_tokens' => 12],
            ]);

        $this->runFilter($story);

        $story->refresh();
        $this->assertSame(Story::STATUS_PENDING, $story->status);
        $this->assertSame('keep', $story->filter_decision);
        $this->assertSame('llm_parse_error', $story->filter_source);
        $this->assertDatabaseHas('ai_cost_logs', [
            'story_id' => $story->id,
            'operation' => 'discovery_filter',
        ]);
    }

    public function test_transport_error_fails_open_with_no_cost_row(): void
    {
        AiSetting::current()->update(['discovery_filter_mode' => 'enforce']);
        $story = $this->pendingStory();

        $this->mock(AiFactoryService::class)
            ->shouldReceive('chatArray')
            ->andThrow(new \RuntimeException('Connection timed out'));

        $this->runFilter($story);

        $story->refresh();
        $this->assertSame(Story::STATUS_PENDING, $story->status);
        $this->assertSame('keep', $story->filter_decision);
        $this->assertSame('llm_error', $story->filter_source);
        $this->assertSame(0, AiCostLog::where('operation', 'discovery_filter')->count());
    }

    public function test_off_mode_never_calls_the_model_or_writes(): void
    {
        AiSetting::current()->update(['discovery_filter_mode' => 'off']);
        $story = $this->pendingStory();

        $this->mock(AiFactoryService::class)->shouldNotReceive('chatArray');

        $this->runFilter($story);

        $story->refresh();
        $this->assertNull($story->filter_decision);
        $this->assertSame(Story::STATUS_PENDING, $story->status);
    }
}
