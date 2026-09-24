<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessStoryWithAi;
use App\Models\AiSetting;
use App\Models\Story;
use App\Services\DiscoveryService;
use Illuminate\Http\Request;

class TriageController extends Controller
{
    public function index(Request $request)
    {
        $stories = Story::forStatus(Story::STATUS_PENDING)
            ->latest()
            ->paginate(20)
            ->withPath(route('triage'));

        $skippedCount = Story::forStatus(Story::STATUS_SKIPPED)->count();
        $skipped = $request->boolean('show_skipped')
            ? Story::forStatus(Story::STATUS_SKIPPED)->latest()->limit(50)->get()
            : collect();

        return view('triage.index', compact('stories', 'skipped', 'skippedCount'));
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

    public function scrapStory(Request $request, Story $story)
    {
        if ($story->status !== Story::STATUS_PENDING) {
            return back()->with('error', 'Story already triaged.');
        }

        $validated = $request->validate([
            'scrap_reason' => 'nullable|in:not_uk,off_topic,not_newsworthy,bad_source,other',
            'scrap_reason_text' => 'nullable|required_if:scrap_reason,other|string|max:200',
        ]);

        $story->update([
            'status' => Story::STATUS_SCRAPPED,
            'archived_at' => now(),
            'scrap_reason' => $validated['scrap_reason'] ?? null,
            'scrap_reason_text' => $validated['scrap_reason'] === 'other' ? ($validated['scrap_reason_text'] ?? null) : null,
        ]);

        return back()->with('success', 'Story scrapped. It will be archived for 90 days before deletion.');
    }

    public function archive()
    {
        $stories = Story::forStatus(Story::STATUS_SCRAPPED)
            ->whereNotNull('archived_at')
            ->latest('archived_at')
            ->paginate(20)
            ->withPath(route('triage.archive'));

        return view('triage.archive', compact('stories'));
    }

    public function restore(Story $story)
    {
        if ($story->status !== Story::STATUS_SCRAPPED) {
            return back()->with('error', 'Only scrapped stories can be restored.');
        }

        $story->update([
            'status' => Story::STATUS_PENDING,
            'archived_at' => null,
            'scrap_reason' => null,
            'scrap_reason_text' => null,
        ]);

        return redirect()->route('triage')->with('success', 'Story restored to triage feed.');
    }

    public function rescue(Story $story)
    {
        if ($story->status !== Story::STATUS_SKIPPED) {
            return back()->with('error', 'Only assistant-skipped stories can be rescued.');
        }

        $story->update([
            'status' => Story::STATUS_PENDING,
            'filter_source' => 'rescued',
        ]);

        return back()->with('success', 'Story rescued back to the triage feed.');
    }

    public function discover(DiscoveryService $service)
    {
        $stories = $service->discover();
        $saved = $service->save($stories);

        $message = 'Discovered '.count($stories).' stories, saved '.$saved.' new.';

        if (AiSetting::current()->discovery_filter_mode !== 'off') {
            $message .= ' The assistant is screening them.';
        }

        return back()->with('success', $message);
    }
}
