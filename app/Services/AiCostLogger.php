<?php

namespace App\Services;

use App\Models\AiCostLog;
use App\Models\AiSetting;
use App\Models\Story;

class AiCostLogger
{
    public function logLlm(Story $story, string $model, array $response): AiCostLog
    {
        $settings = AiSetting::current();

        $inputTokens = $response['usage']['prompt_tokens'] ?? 0;
        $outputTokens = $response['usage']['completion_tokens'] ?? 0;
        $baseCostUsd = ($inputTokens / 1000) * $settings->llm_input_cost_per_1k
            + ($outputTokens / 1000) * $settings->llm_output_cost_per_1k;
        $multiplier = (float) $settings->cost_markup_multiplier ?: 1.0;
        $costUsd = $baseCostUsd * $multiplier;

        return AiCostLog::create([
            'story_id' => $story->id,
            'provider' => 'openai',
            'operation' => 'llm',
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_usd' => $costUsd,
            'metadata' => array_merge($response['usage'] ?? [], [
                'base_cost_usd' => $baseCostUsd,
                'markup_multiplier' => $multiplier,
            ]),
        ]);
    }

    public function logImage(Story $story, string $provider, string $model, float $costUsd, ?array $metadata = null): AiCostLog
    {
        return AiCostLog::create([
            'story_id' => $story->id,
            'provider' => $provider,
            'operation' => 'image',
            'model' => $model,
            'cost_usd' => $costUsd,
            'metadata' => $metadata,
        ]);
    }

    public function logEdit(Story $story, string $model, array $response): AiCostLog
    {
        return $this->logLlm($story, $model, $response);
    }
}
