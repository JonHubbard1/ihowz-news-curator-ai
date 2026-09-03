<?php

namespace App\Jobs;

use App\Models\Story;
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

    public function __construct(public int $storyId) {}

    public function handle(WordPressService $wordpress): void
    {
        $story = Story::findOrFail($this->storyId);
        if ($story->status !== Story::STATUS_DRAFT) {
            throw new \RuntimeException('Story must be in draft status to publish.');
        }
        $wordpress->publish($story);
    }

    public function failed(Throwable $exception): void
    {
        $story = Story::find($this->storyId);
        if ($story && $story->status === Story::STATUS_PROCESSING) {
            $story->update(['status' => Story::STATUS_DRAFT]);
        }
    }
}
