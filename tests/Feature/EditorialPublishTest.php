<?php

namespace Tests\Feature;

use App\Jobs\PublishToWordPress;
use App\Models\Story;
use App\Models\User;
use App\Services\WordPressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class EditorialPublishTest extends TestCase
{
    use RefreshDatabase;

    private function draftStory(array $attributes = []): Story
    {
        return Story::create(array_merge([
            'url' => 'https://example.com/press-release-story',
            'headline' => 'Press Release Headline',
            'source' => 'PR Newswire',
            'status' => Story::STATUS_DRAFT,
            'trigger_keyword' => 'press release',
            'article_text' => 'Draft body copy for the story.',
        ], $attributes));
    }

    public function test_publishing_a_draft_marks_it_publishing_and_shows_it_in_progress(): void
    {
        Queue::fake();
        $story = $this->draftStory();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('editorial.publish', $story))
            ->assertRedirect(route('editorial'));

        $this->assertSame(Story::STATUS_PUBLISHING, $story->fresh()->status);
        Queue::assertPushed(PublishToWordPress::class);

        $this->actingAs($user)
            ->get(route('editorial'))
            ->assertSee('In Progress')
            ->assertSee('Press Release Headline')
            ->assertSee('Publishing to WordPress')
            ->assertSee('No drafts ready for review.');
    }

    public function test_queued_and_generating_stories_appear_in_progress_but_not_among_drafts(): void
    {
        $queued = $this->draftStory([
            'url' => 'https://example.com/queued',
            'headline' => 'Queued Release',
            'status' => Story::STATUS_USED,
        ]);
        $generating = $this->draftStory([
            'url' => 'https://example.com/generating',
            'headline' => 'Generating Release',
            'status' => Story::STATUS_PROCESSING,
        ]);
        $this->draftStory([
            'url' => 'https://example.com/draft',
            'headline' => 'Awaiting Review Release',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('editorial'))
            ->assertSee('In Progress')
            ->assertSee('Queued in the AI factory')
            ->assertSee('Generating article & image')
            ->assertSee('Queued Release')
            ->assertSee('Generating Release')
            ->assertSee('Awaiting Review Release');

        $this->assertSame(Story::STATUS_USED, $queued->fresh()->status);
        $this->assertSame(Story::STATUS_PROCESSING, $generating->fresh()->status);
    }

    public function test_only_drafts_can_be_published(): void
    {
        Queue::fake();
        $story = $this->draftStory();
        $story->update(['status' => Story::STATUS_PUBLISHED]);

        $this->actingAs(User::factory()->create())
            ->post(route('editorial.publish', $story))
            ->assertSessionHas('error');

        $this->assertSame(Story::STATUS_PUBLISHED, $story->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_publish_job_accepts_a_story_marked_publishing(): void
    {
        $story = $this->draftStory();
        $story->update(['status' => Story::STATUS_PUBLISHING]);

        $wordpress = $this->mock(WordPressService::class);
        $wordpress->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(fn (Story $queued) => $queued->is($story)), null);

        (new PublishToWordPress($story->id))->handle($wordpress);
    }

    public function test_failed_publish_returns_story_to_draft(): void
    {
        $story = $this->draftStory();
        $story->update(['status' => Story::STATUS_PUBLISHING]);

        (new PublishToWordPress($story->id))->failed(new \RuntimeException('WordPress rejected the post'));

        $this->assertSame(Story::STATUS_DRAFT, $story->fresh()->status);
    }
}
