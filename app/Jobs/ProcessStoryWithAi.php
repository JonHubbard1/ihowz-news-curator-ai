<?php

namespace App\Jobs;

use App\Models\Story;
use App\Services\AiFactoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessStoryWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public int $storyId) {}

    public function handle(AiFactoryService $ai): void
    {
        $story = Story::findOrFail($this->storyId);
        $ai->process($story);
    }

    public function failed(Throwable $exception): void
    {
        $story = Story::find($this->storyId);
        if ($story) {
            $story->update(['status' => Story::STATUS_USED]);
        }
    }
}
