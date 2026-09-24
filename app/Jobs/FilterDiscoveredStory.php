<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\Story;
use App\Services\DiscoveryFilterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FilterDiscoveredStory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 90;

    public function __construct(public int $storyId) {}

    public function handle(DiscoveryFilterService $filter): void
    {
        $story = Story::find($this->storyId);
        if (! $story) {
            return;
        }

        $filter->filterStory($story, AiSetting::current()->discovery_filter_mode);
    }
}
