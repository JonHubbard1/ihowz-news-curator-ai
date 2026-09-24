<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriageScrapReasonTest extends TestCase
{
    use RefreshDatabase;

    private function pendingStory(array $attributes = []): Story
    {
        return Story::create(array_merge([
            'url' => 'https://example.com/scrap-reason',
            'headline' => 'Section 21 Reform Delayed Again',
            'source' => 'Property Wire',
            'status' => Story::STATUS_PENDING,
            'trigger_keyword' => 'Section 21',
        ], $attributes));
    }

    public function test_scrap_stores_the_selected_reason_chip(): void
    {
        $story = $this->pendingStory();

        $this->actingAs(User::factory()->create())
            ->post(route('triage.scrap', $story), ['scrap_reason' => 'not_uk'])
            ->assertSessionHas('success');

        $story->refresh();
        $this->assertSame(Story::STATUS_SCRAPPED, $story->status);
        $this->assertSame('not_uk', $story->scrap_reason);
        $this->assertNull($story->scrap_reason_text);
    }

    public function test_scrap_without_a_reason_still_works(): void
    {
        $story = $this->pendingStory();

        $this->actingAs(User::factory()->create())
            ->post(route('triage.scrap', $story), ['scrap_reason' => ''])
            ->assertSessionHas('success');

        $story->refresh();
        $this->assertSame(Story::STATUS_SCRAPPED, $story->status);
        $this->assertNull($story->scrap_reason);
    }

    public function test_other_reason_requires_free_text(): void
    {
        $story = $this->pendingStory();

        $this->actingAs(User::factory()->create())
            ->post(route('triage.scrap', $story), ['scrap_reason' => 'other'])
            ->assertSessionHasErrors('scrap_reason_text');

        $this->assertSame(Story::STATUS_PENDING, $story->fresh()->status);
    }

    public function test_other_reason_stores_free_text(): void
    {
        $story = $this->pendingStory();

        $this->actingAs(User::factory()->create())
            ->post(route('triage.scrap', $story), [
                'scrap_reason' => 'other',
                'scrap_reason_text' => 'Syndicated copy of a story we ran yesterday',
            ])
            ->assertSessionHas('success');

        $story->refresh();
        $this->assertSame('other', $story->scrap_reason);
        $this->assertSame('Syndicated copy of a story we ran yesterday', $story->scrap_reason_text);
    }

    public function test_invalid_reason_is_rejected(): void
    {
        $story = $this->pendingStory();

        $this->actingAs(User::factory()->create())
            ->post(route('triage.scrap', $story), ['scrap_reason' => 'definitely_uk'])
            ->assertSessionHasErrors('scrap_reason');

        $this->assertSame(Story::STATUS_PENDING, $story->fresh()->status);
    }

    public function test_already_triaged_story_cannot_be_scrapped(): void
    {
        $story = $this->pendingStory(['status' => Story::STATUS_USED]);

        $this->actingAs(User::factory()->create())
            ->post(route('triage.scrap', $story), ['scrap_reason' => 'off_topic'])
            ->assertSessionHas('error');

        $this->assertSame(Story::STATUS_USED, $story->fresh()->status);
        $this->assertNull($story->fresh()->scrap_reason);
    }
}
