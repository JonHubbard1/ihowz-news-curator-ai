<?php

namespace App\Services;

use App\Models\Story;
use Illuminate\Support\Facades\DB;

class TriageStatsService
{
    /**
     * Accept-rate statistics per trigger keyword.
     *
     * @return array<string, array{used: int, scrapped: int, judged: int, accept_rate: ?float}> keyed by lowercased keyword
     */
    public function keywordStats(): array
    {
        return $this->statsFor('trigger_keyword');
    }

    /**
     * Accept-rate statistics per source.
     *
     * @return array<string, array{used: int, scrapped: int, judged: int, accept_rate: ?float}> keyed by lowercased source
     */
    public function sourceStats(): array
    {
        return $this->statsFor('source');
    }

    /**
     * @return array<string, array{used: int, scrapped: int, judged: int, accept_rate: ?float}>
     */
    private function statsFor(string $column): array
    {
        $acceptedPlaceholders = implode(',', array_fill(0, count(Story::ACCEPTED_STATUSES), '?'));

        $rows = DB::table('stories')
            ->selectRaw("lower({$column}) as stats_key, sum(case when status in ({$acceptedPlaceholders}) then 1 else 0 end) as used, sum(case when status = ? then 1 else 0 end) as scrapped", [...Story::ACCEPTED_STATUSES, Story::STATUS_SCRAPPED])
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy('stats_key')
            ->get();

        $stats = [];
        foreach ($rows as $row) {
            $used = (int) $row->used;
            $scrapped = (int) $row->scrapped;
            $judged = $used + $scrapped;
            $stats[$row->stats_key] = [
                'used' => $used,
                'scrapped' => $scrapped,
                'judged' => $judged,
                'accept_rate' => $judged > 0 ? $used / $judged : null,
            ];
        }

        return $stats;
    }
}
