<?php

namespace App\Http\Controllers;

use App\Models\AiCostLog;
use App\Models\AiSetting;
use App\Models\Story;

class UserCostsController extends Controller
{
    public function index()
    {
        $ai = AiSetting::current();

        $actualCostUsd = (float) AiCostLog::whereNull('invoiced_at')->sum('cost_usd');
        $markupMultiplier = (float) $ai->cost_markup_multiplier ?: 1.0;
        $actualSpendUsd = $actualCostUsd * $markupMultiplier;
        $actualSpendGbp = $actualSpendUsd * 0.79;

        $totalLlmCalls = AiCostLog::whereNull('invoiced_at')->where('operation', 'llm')->count();
        $totalImageCalls = AiCostLog::whereNull('invoiced_at')->where('operation', 'image')->count();
        $totalTokens = (float) AiCostLog::whereNull('invoiced_at')->sum('input_tokens')
            + (float) AiCostLog::whereNull('invoiced_at')->sum('output_tokens');
        $totalProcessed = Story::whereNull('invoiced_at')
            ->whereIn('status', [Story::STATUS_DRAFT, Story::STATUS_PUBLISHED])
            ->count();
        $totalPublished = Story::whereNull('invoiced_at')
            ->where('status', Story::STATUS_PUBLISHED)
            ->count();

        $recentCosts = AiCostLog::with('story')
            ->whereNull('invoiced_at')
            ->latest()
            ->limit(50)
            ->get();

        return view('user.costs', compact(
            'actualSpendUsd',
            'actualSpendGbp',
            'totalLlmCalls',
            'totalImageCalls',
            'totalTokens',
            'totalProcessed',
            'totalPublished',
            'recentCosts'
        ));
    }
}
