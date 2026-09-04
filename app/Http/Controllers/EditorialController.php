<?php

namespace App\Http\Controllers;

use App\Jobs\PublishToWordPress;
use App\Jobs\RegenerateStoryImage;
use App\Models\Story;
use App\Services\AiFactoryService;
use Illuminate\Http\Request;

class EditorialController extends Controller
{
    public function index()
    {
        $drafts = Story::forStatus(Story::STATUS_DRAFT)->latest()->get();
        $published = Story::forStatus(Story::STATUS_PUBLISHED)->latest()->get();

        return view('editorial.index', compact('drafts', 'published'));
    }

    public function show(Story $story)
    {
        return view('editorial.show', compact('story'));
    }

    public function update(Request $request, Story $story)
    {
        $data = $request->validate([
            'headline' => 'required|string|max:500',
            'article_text' => 'required|string',
            'meta_description' => 'nullable|string|max:320',
            'suggested_category' => 'nullable|string|max:255',
            'suggested_tags' => 'nullable|string',
        ]);

        $data['suggested_tags'] = array_filter(array_map('trim', explode(',', $data['suggested_tags'] ?? '')));
        $story->update($data);

        return back()->with('success', 'Draft updated.');
    }

    public function aiCommand(Request $request, Story $story, AiFactoryService $ai)
    {
        $command = $request->validate(['command' => 'required|string'])['command'];
        $ai->applyCommand($story, $command);

        return back()->with('success', 'AI edit applied.');
    }

    public function regenerateImage(Story $story)
    {
        RegenerateStoryImage::dispatch($story->id);

        return redirect()->route('editorial.show', ['story' => $story, 'generating' => 1]);
    }

    public function imageStatus(Story $story)
    {
        return response()->json([
            'image_url' => $story->image_url,
            'updated_at' => $story->updated_at->toIso8601String(),
        ]);
    }

    public function publish(Story $story)
    {
        if ($story->status !== Story::STATUS_DRAFT) {
            return back()->with('error', 'Only drafts can be published.');
        }
        PublishToWordPress::dispatch($story->id);

        return redirect()->route('editorial')->with('success', 'Publishing to WordPress...');
    }
}
