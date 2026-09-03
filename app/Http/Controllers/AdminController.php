<?php

namespace App\Http\Controllers;

use App\Models\AiCostLog;
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

        $uninvoicedCostUsd = (float) AiCostLog::whereNull('invoiced_at')->sum('cost_usd');
        $markupMultiplier = (float) $ai->cost_markup_multiplier ?: 1.0;
        $uninvoicedSpendUsd = $uninvoicedCostUsd * $markupMultiplier;
        $uninvoicedSpendGbp = $uninvoicedSpendUsd * 0.79;
        $totalLlmCalls = AiCostLog::whereNull('invoiced_at')->where('operation', 'llm')->count();
        $totalImageCalls = AiCostLog::whereNull('invoiced_at')->where('operation', 'image')->count();
        $totalTokens = (float) AiCostLog::whereNull('invoiced_at')->sum('input_tokens')
            + (float) AiCostLog::whereNull('invoiced_at')->sum('output_tokens');
        $uninvoicedProcessed = Story::whereNull('invoiced_at')
            ->whereIn('status', [Story::STATUS_DRAFT, Story::STATUS_PUBLISHED])
            ->count();
        $uninvoicedPublished = Story::whereNull('invoiced_at')
            ->where('status', Story::STATUS_PUBLISHED)
            ->count();

        // Legacy estimates for comparison.
        $tokensEstimate = $uninvoicedProcessed * 2500;
        $spendEstimate = ($tokensEstimate / 1000) * 0.03 + ($uninvoicedProcessed * 0.04);

        return view('admin.index', compact(
            'ai',
            'wp',
            'totalProcessed',
            'totalPublished',
            'tokensEstimate',
            'spendEstimate',
            'uninvoicedSpendUsd',
            'uninvoicedSpendGbp',
            'totalLlmCalls',
            'totalImageCalls',
            'totalTokens',
            'uninvoicedProcessed',
            'uninvoicedPublished'
        ));
    }

    public function resetCosts(Request $request)
    {
        $now = now();

        AiCostLog::whereNull('invoiced_at')->update(['invoiced_at' => $now]);
        Story::whereNull('invoiced_at')
            ->whereIn('status', [Story::STATUS_DRAFT, Story::STATUS_PUBLISHED])
            ->update(['invoiced_at' => $now]);

        return back()->with('success', 'Costs reset. Uninvoiced counter is now zero.');
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
            'llm_input_cost_per_1k' => 'nullable|numeric|min:0',
            'llm_output_cost_per_1k' => 'nullable|numeric|min:0',
            'image_cost_per_image' => 'nullable|numeric|min:0',
            'fal_cost_per_image' => 'nullable|numeric|min:0',
            'cost_markup_multiplier' => 'nullable|numeric|min:0',
            'target_article_length' => 'required|integer|min:200|max:3000',
        ]);

        $data = array_filter($data, fn ($value) => $value !== null);

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
