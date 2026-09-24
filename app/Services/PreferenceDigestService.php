<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\PreferenceProfile;
use App\Models\Story;
use Illuminate\Support\Collection;

class PreferenceDigestService
{
    private const SAMPLE_CAP = 100;

    public function __construct(
        private AiFactoryService $ai,
        private AiCostLogger $costs,
        private PreferenceProfileService $profiles,
    ) {}

    /**
     * Rewrite the taste profile from recent use/scrap history. Returns null
     * (without spending tokens) when there is nothing to learn from.
     */
    public function run(?int $windowDays = 30): ?PreferenceProfile
    {
        $windowDays = $windowDays ?: 30;
        $since = now()->subDays($windowDays);

        $positives = Story::where('created_at', '>=', $since)
            ->where(function ($query) {
                $query->whereIn('status', Story::ACCEPTED_STATUSES)
                    ->orWhere('filter_source', 'rescued');
            })
            ->latest()
            ->limit(self::SAMPLE_CAP)
            ->get();

        $negatives = Story::where('created_at', '>=', $since)
            ->where('status', Story::STATUS_SCRAPPED)
            ->where(function ($query) {
                $query->whereNotNull('scrap_reason')->orWhereNotNull('scrap_reason_text');
            })
            ->latest()
            ->limit(self::SAMPLE_CAP)
            ->get();

        if ($positives->isEmpty() && $negatives->isEmpty()) {
            return null;
        }

        $model = AiSetting::current()->discovery_filter_model ?: 'gpt-4.1-mini';

        $response = $this->ai->chatArray($model, [
            ['role' => 'system', 'content' => 'You maintain the taste profile for a UK private-rental-sector news editor. Reply with the profile text only.'],
            ['role' => 'user', 'content' => $this->userPrompt($positives, $negatives)],
        ], temperature: 0.4, timeout: 60, maxTokens: 600);

        $profile = trim((string) ($response['choices'][0]['message']['content'] ?? ''));
        $profile = (string) preg_replace('/^```[a-z]*\s*|\s*```$/m', '', $profile);

        if ($profile === '') {
            return null;
        }

        $this->costs->logOperation(null, $model, $response, 'preference_digest');

        return $this->profiles->record(
            profile: $profile,
            storyCount: $positives->count() + $negatives->count(),
            model: $model,
            windowDays: $windowDays,
        );
    }

    private function userPrompt(Collection $positives, Collection $negatives): string
    {
        $lines = [];

        $previous = $this->profiles->current()?->profile;
        $lines[] = "Current taste profile:\n".($previous ?: PreferenceProfileService::DEFAULT_PROFILE);

        $lines[] = 'Stories the editor used (good): '.$this->storyList(
            $positives,
            fn (Story $story) => '- '.$story->headline.' ['.$this->label($story->source).']',
        );

        $lines[] = 'Stories the editor scrapped (bad, with their reason): '.$this->storyList(
            $negatives,
            function (Story $story) {
                $reason = Story::SCRAP_REASON_LABELS[$story->scrap_reason] ?? 'Scrapped';

                if ($story->scrap_reason === 'other' && $story->scrap_reason_text) {
                    $reason .= ': '.$story->scrap_reason_text;
                }

                return '- '.$story->headline.' ['.$this->label($story->source).'] — '.$reason;
            },
        );

        $lines[] = 'Rewrite the taste profile (maximum 250 words) with sections MUST HAVE, AVOID and NOTES, through a UK private-rental-sector lens. '
            .'Fold in what the used/scrapped examples reveal beyond the current profile — recurring themes, sources, and story types. '
            .'Keep it concrete enough to screen headlines against. Reply with the profile text only, no preamble.';

        return implode("\n\n", $lines);
    }

    /**
     * @param  Collection<Story>  $stories
     * @param  callable(Story): string  $formatter
     */
    private function storyList(Collection $stories, callable $formatter): string
    {
        if ($stories->isEmpty()) {
            return '(none)';
        }

        return "\n".$stories->map($formatter)->implode("\n");
    }

    private function label(?string $source): string
    {
        return $source ?: 'unknown source';
    }
}
