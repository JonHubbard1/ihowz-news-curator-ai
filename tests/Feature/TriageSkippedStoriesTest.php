<?php

namespace Tests\Feature;

use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriageSkippedStoriesTest extends TestCase
{
    use RefreshDatabase;

    private function story(string $status, string $headline, array $attributes = []): Story
    {
        return Story::create(array_merge([
            'url' => 'https://example.com/skipped-'.uniqid(),
            'headline' => $headline,
            'status' => $status,
        ], $attributes));
    }

    public function test_skipped_stories_hide_behind_the_count_disclosure(): void
    {
        $this->actingAs(User::factory()->create());
        $this->story(Story::STATUS_PENDING, 'Landlord Wins Possession Appeal');
        $this->story(Story::STATUS_SKIPPED, 'NFL Owner Sells Stadium District Homes', [
            'filter_reason' => 'US domestic story',
        ]);

        $response = $this->get(route('triage'));

        $response->assertOk()
            ->assertSee('Landlord Wins Possession Appeal')
            ->assertSee('Assistant skipped 1 story')
            ->assertDontSee('NFL Owner Sells Stadium District Homes');
    }

    public function test_disclosure_lists_skipped_stories_when_requested(): void
    {
        $this->actingAs(User::factory()->create());
        $this->story(Story::STATUS_SKIPPED, 'NFL Owner Sells Stadium District Homes', [
            'filter_reason' => 'US domestic story',
        ]);

        $this->get(route('triage', ['show_skipped' => 1]))
            ->assertOk()
            ->assertSee('NFL Owner Sells Stadium District Homes')
            ->assertSee('US domestic story');
    }

    public function test_nothing_is_shown_about_skipped_when_the_list_is_empty(): void
    {
        $this->actingAs(User::factory()->create());
        $this->story(Story::STATUS_PENDING, 'Renters Rights Bill Reaches Report Stage');

        $this->get(route('triage'))
            ->assertOk()
            ->assertDontSee('Assistant skipped');
    }

    public function test_rescuing_returns_the_story_to_pending_and_marks_it(): void
    {
        $this->actingAs(User::factory()->create());
        $story = $this->story(Story::STATUS_SKIPPED, 'Overshadowed But Relevant Story', [
            'filter_decision' => 'skip',
            'filter_source' => 'llm',
        ]);

        $this->post(route('triage.rescue', $story))
            ->assertSessionHas('success');

        $story->refresh();
        $this->assertSame(Story::STATUS_PENDING, $story->status);
        $this->assertSame('rescued', $story->filter_source);
    }

    public function test_rescue_rejects_a_story_that_was_not_skipped(): void
    {
        $this->actingAs(User::factory()->create());
        $pending = $this->story(Story::STATUS_PENDING, 'Still Awaiting Triage');

        $this->post(route('triage.rescue', $pending))
            ->assertSessionHas('error');

        $this->assertSame(Story::STATUS_PENDING, $pending->fresh()->status);
    }
}
