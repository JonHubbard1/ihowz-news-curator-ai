<?php

namespace App\Http\Controllers;

use App\Models\RssFeed;
use Illuminate\Http\Request;

class RssFeedController extends Controller
{
    public function index()
    {
        $feeds = RssFeed::orderByDesc('priority')->orderBy('name')->get();

        return view('rss-feeds.index', compact('feeds'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:1024|unique:rss_feeds,url',
            'priority' => 'nullable|integer|min:0|max:9999',
        ]);

        RssFeed::create([
            'name' => $data['name'],
            'url' => $data['url'],
            'priority' => $data['priority'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'RSS feed added.');
    }

    public function update(Request $request, RssFeed $feed)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:1024|unique:rss_feeds,url,'.$feed->id,
            'priority' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $feed->update([
            'name' => $data['name'],
            'url' => $data['url'],
            'priority' => $data['priority'] ?? $feed->priority,
            'is_active' => $request->boolean('is_active', $feed->is_active),
        ]);

        return back()->with('success', 'RSS feed updated.');
    }

    public function destroy(RssFeed $feed)
    {
        $feed->delete();

        return back()->with('success', 'RSS feed removed.');
    }
}
