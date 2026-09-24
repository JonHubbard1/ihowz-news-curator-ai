<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\Story;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DiscoveryFilterService
{
    /**
     * Zero-token pre-screen: obvious US-domestic signals in the headline/snippet.
     */
    private const US_SIGNAL_REGEX = '/\b(white house|washington d\.?c\.?|new york|california|texas|florida|chicago|boston|seattle|nfl|nba|mlb|u\.?\s*s\.?\s+(senate|senator|congress(wo)?man|president|house|supreme court))\b/i';

    public function __construct(
        private AiFactoryService $ai,
        private AiCostLogger $costs,
        private PreferenceProfileService $profiles,
    ) {}

    /**
     * Screen a freshly discovered story. Never throws — any failure keeps the
     * story pending (fail-open) so a wrongly-skipped story can't silently
     * disappear from the feed.
     */
    public function filterStory(Story $story, string $mode): void
    {
        if ($story->status !== Story::STATUS_PENDING || $mode === 'off') {
            return;
        }

        try {
            $signal = $this->deterministicUsSignal($story->headline, $story->snippet);

            if ($signal !== null) {
                $this->record($story, $mode, [
                    'keep' => false,
                    'uk_relevant' => false,
                    'confidence' => 1.0,
                    'reason' => 'US-domestic signal: '.Str::limit($signal, 60),
                ], 'deterministic');

                return;
            }

            $this->callModel($story, $mode);
        } catch (\Throwable $e) {
            Log::warning('Discovery filter failed open: '.$e->getMessage(), ['story_id' => $story->id]);
            $story->update([
                'filter_decision' => 'keep',
                'filter_source' => 'llm_error',
                'filter_reason' => Str::limit($e->getMessage(), 190),
                'filter_confidence' => null,
                'uk_relevant' => null,
            ]);
        }
    }

    public static function deterministicUsSignal(string $headline, ?string $snippet): ?string
    {
        $text = trim($headline.' '.$snippet);

        if (preg_match(self::US_SIGNAL_REGEX, $text, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function callModel(Story $story, string $mode): void
    {
        $model = AiSetting::current()->discovery_filter_model ?: 'gpt-4.1-mini';

        $response = $this->ai->chatArray($model, [
            ['role' => 'system', 'content' => 'You are the intake screener for a UK private-rental-sector news site. Reply with JSON only.'],
            ['role' => 'user', 'content' => $this->userPrompt($story)],
        ], temperature: 0.2, timeout: 30, maxTokens: 150);

        $content = trim((string) ($response['choices'][0]['message']['content'] ?? ''));
        $content = preg_replace('/^```json\s*|\s*```$/m', '', $content);
        $decoded = json_decode($content, true);

        $this->costs->logOperation($story, $model, $response, 'discovery_filter');

        if (! is_array($decoded) || ! array_key_exists('keep', $decoded) || ! array_key_exists('uk_relevant', $decoded)) {
            $story->update([
                'filter_decision' => 'keep',
                'filter_source' => 'llm_parse_error',
                'filter_reason' => Str::limit($content, 190),
                'filter_confidence' => null,
                'uk_relevant' => null,
            ]);

            return;
        }

        $confidence = array_key_exists('confidence', $decoded) && is_numeric($decoded['confidence'])
            ? max(0.0, min(1.0, (float) $decoded['confidence']))
            : null;

        $this->record($story, $mode, [
            'keep' => (bool) $decoded['keep'],
            'uk_relevant' => (bool) $decoded['uk_relevant'],
            'confidence' => $confidence,
            'reason' => trim((string) ($decoded['reason'] ?? '')),
        ], 'llm');
    }

    /**
     * @param  array{keep: bool, uk_relevant: bool, confidence: ?float, reason: string}  $decision
     */
    private function record(Story $story, string $mode, array $decision, string $source): void
    {
        // Hard UK gate: anything not UK-relevant is skipped regardless of `keep`.
        $skip = ! $decision['uk_relevant'] || ! $decision['keep'];

        $story->update([
            'filter_decision' => $skip ? 'skip' : 'keep',
            'filter_source' => $source,
            'filter_reason' => Str::limit($decision['reason'], 190),
            'filter_confidence' => $decision['confidence'],
            'uk_relevant' => $decision['uk_relevant'],
        ]);

        if ($skip && $mode === 'enforce') {
            $story->update(['status' => Story::STATUS_SKIPPED]);
        }
    }

    private function userPrompt(Story $story): string
    {
        return "Taste profile:\n".$this->profiles->currentProfileText()."\n\n"
            ."Candidate story:\n"
            .'Headline: '.$story->headline."\n"
            .'Snippet: '.($story->snippet ?: '(none)')."\n"
            .'Source: '.($story->source ?: '(unknown)')."\n"
            .'Search phrase that found it: '.($story->trigger_keyword ?: '(unknown)')."\n\n"
            .'Judge by what the story is actually about, not incidental mentions — a UK story that merely mentions the US is still UK-relevant. '
            ."When uncertain, keep it.\n\n"
            .'Reply with JSON only, exactly this shape: {"keep": true|false, "uk_relevant": true|false, "confidence": 0.0-1.0, "reason": "max 90 chars"}';
    }
}
