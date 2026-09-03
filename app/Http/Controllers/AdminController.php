<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\Story;
use App\Models\WpSetting;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $ai = AiSetting::current();
        $wp = WpSetting::current();

        $totalProcessed = Story::whereIn('status', [Story::STATUS_DRAFT, Story::STATUS_PUBLISHED])->count();
        $totalPublished = Story::where('status', Story::STATUS_PUBLISHED)->count();
        $tokensEstimate = $totalProcessed * 2500;
        $spendEstimate = ($tokensEstimate / 1000) * 0.03 + ($totalProcessed * 0.04);

        return view('admin.index', compact('ai', 'wp', 'totalProcessed', 'totalPublished', 'tokensEstimate', 'spendEstimate'));
    }

    public function updateAiSettings(Request $request)
    {
        $data = $request->validate([
            'openai_api_key' => 'nullable|string|max:512',
            'fal_api_key' => 'nullable|string|max:512',
            'llm_model' => 'required|string|max:128',
            'image_model' => 'required|string|max:128',
            'image_provider' => 'required|string|in:openai,fal',
            'fal_model' => 'nullable|string|max:128',
            'brand_voice' => 'nullable|string',
        ]);

        AiSetting::current()->update($data);

        return back()->with('success', 'AI settings saved.');
    }

    public function updateWpSettings(Request $request)
    {
        $data = $request->validate([
            'base_url' => 'nullable|url|max:512',
            'username' => 'nullable|string|max:255',
            'application_password' => 'nullable|string|max:255',
        ]);

        WpSetting::current()->update($data);

        return back()->with('success', 'WordPress settings saved.');
    }
}
