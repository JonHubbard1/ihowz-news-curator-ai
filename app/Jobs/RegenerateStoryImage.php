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

class RegenerateStoryImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $storyId) {}

    public function handle(AiFactoryService $ai): void
    {
        $story = Story::findOrFail($this->storyId);
        $ai->regenerateImage($story);
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
