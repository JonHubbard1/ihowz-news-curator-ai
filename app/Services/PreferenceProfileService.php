<?php

namespace App\Services;

use App\Models\PreferenceProfile;

class PreferenceProfileService
{
    /**
     * Cold-start taste profile used until the nightly digest writes a real one.
     */
    public const DEFAULT_PROFILE = <<<'TEXT'
    MUST HAVE: UK private rented sector (PRS) news — renting agents, landlords, leasehold, social housing, planning and housing policy, the Renters' Rights Bill, evictions and Section 21, deposit schemes, property legislation, and letting-business news (regulation, software, financing). Stories must be about the UK or materially affect UK landlords/agents.

    AVOID: US domestic politics and news (White House, Congress, state-level US stories), anything that only mentions the UK in passing, celebrity, sport, local crime, and stories about other countries' rental markets unless they directly inform UK policy.

    NOTES: Practical, sector-relevant angles preferred over sensational headlines. Recency matters — this is a daily news feed, not evergreen commentary.
    TEXT;

    public function current(): ?PreferenceProfile
    {
        return PreferenceProfile::latest()->first();
    }

    public function currentProfileText(): string
    {
        $latest = $this->current();

        return $latest?->profile ?: self::DEFAULT_PROFILE;
    }

    public function record(string $profile, int $storyCount, string $model, int $windowDays = 30): PreferenceProfile
    {
        return PreferenceProfile::create([
            'profile' => $profile,
            'story_count' => $storyCount,
            'window_days' => $windowDays,
            'model' => $model,
        ]);
    }
}
