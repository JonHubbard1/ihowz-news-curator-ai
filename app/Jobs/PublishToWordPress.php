<?php

namespace App\Jobs;

use App\Models\Story;
use App\Models\User;
use App\Services\WordPressService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PublishToWordPress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public int $storyId,
        public ?int $publisherId = null,
    ) {}

    public function handle(WordPressService $wordpress): void
    {
        $story = Story::findOrFail($this->storyId);
        if (! in_array($story->status, [Story::STATUS_DRAFT, Story::STATUS_PUBLISHING], true)) {
            throw new \RuntimeException('Story must be in draft status to publish.');
        }
        $wordpress->publish($story, $this->publisherId ? User::find($this->publisherId) : null);
    }

    public function failed(Throwable $exception): void
    {
        $story = Story::find($this->storyId);
        if ($story && in_array($story->status, [Story::STATUS_PROCESSING, Story::STATUS_PUBLISHING], true)) {
            $story->update(['status' => Story::STATUS_DRAFT]);
        }
    }
}
