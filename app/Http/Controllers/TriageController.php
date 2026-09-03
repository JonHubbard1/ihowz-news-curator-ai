<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Services\DiscoveryService;
use App\Jobs\ProcessStoryWithAi;
use Illuminate\Http\Request;

class TriageController extends Controller
{
    public function index(Request $request)
    {
        $stories = Story::forStatus(Story::STATUS_PENDING)
            ->latest()
            ->paginate(20)
            ->withPath(route('triage'));

        return view('triage.index', compact('stories'));
    }

    public function useStory(Story $story)
    {
        if ($story->status !== Story::STATUS_PENDING) {
            return back()->with('error', 'Story already triaged.');
        }
        $story->update(['status' => Story::STATUS_USED]);
        ProcessStoryWithAi::dispatch($story->id);

        return back()->with('success', 'Story sent to the AI factory.');
    }

    public function scrapStory(Story $story)
    {
        $story->update(['status' => Story::STATUS_SCRAPPED]);

        return back()->with('success', 'Story scrapped.');
    }

    public function discover(DiscoveryService $service)
    {
        $stories = $service->discover();
        $saved = $service->save($stories);

        return back()->with('success', "Discovered ".count($stories).' stories, saved '.$saved.' new.');
    }
}
