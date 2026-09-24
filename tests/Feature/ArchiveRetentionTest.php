<?php

namespace Tests\Feature;

use App\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function story(string $status, array $timestamps = []): Story
    {
        $story = Story::create(array_merge([
            'url' => 'https://example.com/'.uniqid(),
            'headline' => 'Retention story',
            'status' => $status,
        ], $timestamps));

        return $story;
    }

    public function test_scrapped_stories_survive_eight_days_but_go_at_91(): void
    {
        $young = $this->story(Story::STATUS_SCRAPPED, ['archived_at' => now()->subDays(8)]);
        $old = $this->story(Story::STATUS_SCRAPPED, ['archived_at' => now()->subDays(91)]);

        $this->artisan('news:purge-archived')->assertExitCode(0);

        $this->assertDatabaseHas('stories', ['id' => $young->id]);
        $this->assertDatabaseMissing('stories', ['id' => $old->id]);
    }

    public function test_assistant_skipped_stories_purge_after_seven_days(): void
    {
        $young = $this->story(Story::STATUS_SKIPPED);
        $old = $this->story(Story::STATUS_SKIPPED);
        $old->created_at = now()->subDays(8);
        $old->save();

        $this->artisan('news:purge-archived')->assertExitCode(0);

        $this->assertDatabaseHas('stories', ['id' => $young->id]);
        $this->assertDatabaseMissing('stories', ['id' => $old->id]);
    }

    public function test_pending_stories_are_never_purged(): void
    {
        $pending = $this->story(Story::STATUS_PENDING);
        $pending->created_at = now()->subDays(200);
        $pending->save();

        $this->artisan('news:purge-archived')->assertExitCode(0);

        $this->assertDatabaseHas('stories', ['id' => $pending->id]);
    }
}
