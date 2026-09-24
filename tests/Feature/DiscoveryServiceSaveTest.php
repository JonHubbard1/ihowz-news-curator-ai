<?php

namespace Tests\Feature;

use App\Jobs\FilterDiscoveredStory;
use App\Models\AiSetting;
use App\Models\Story;
use App\Services\DiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DiscoveryServiceSaveTest extends TestCase
{
    use RefreshDatabase;

    private function candidate(string $url): array
    {
        return [
            'url' => $url,
            'headline' => 'Renters Rights Bill update',
            'snippet' => 'Committee stage begins this week.',
            'source' => 'Property Wire',
            'trigger_keyword' => 'Renters Rights Bill',
            'cluster_id' => 'abc123def456',
        ];
    }

    public function test_nothing_is_screened_when_the_mode_is_off(): void
    {
        Queue::fake();
        AiSetting::current()->update(['discovery_filter_mode' => 'off']);

        $saved = app(DiscoveryService::class)->save([$this->candidate('https://example.com/off-mode')]);

        $this->assertSame(1, $saved);
        Queue::assertNothingPushed();
    }

    public function test_every_new_story_is_queued_for_screening_in_enforce_mode(): void
    {
        Queue::fake();
        AiSetting::current()->update(['discovery_filter_mode' => 'enforce']);

        $saved = app(DiscoveryService::class)->save([
            $this->candidate('https://example.com/new-one'),
            $this->candidate('https://example.com/new-two'),
        ]);

        $this->assertSame(2, $saved);
        Queue::assertPushed(FilterDiscoveredStory::class, 2);
    }

    public function test_existing_urls_are_not_saved_or_screened_again(): void
    {
        Queue::fake();
        AiSetting::current()->update(['discovery_filter_mode' => 'advisory']);
        Story::create([
            'url' => 'https://example.com/already-seen',
            'headline' => 'Previously discovered',
            'status' => Story::STATUS_PENDING,
        ]);

        $saved = app(DiscoveryService::class)->save([$this->candidate('https://example.com/already-seen')]);

        $this->assertSame(0, $saved);
        Queue::assertNotPushed(FilterDiscoveredStory::class);
    }
}
