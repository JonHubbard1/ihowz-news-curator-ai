<?php

namespace App\Services;

use App\Models\RssFeed;
use App\Models\SearchTerm;
use App\Models\Story;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DiscoveryService
{
    public function discover(): array
    {
        $keywords = $this->keywords();
        $raw = [];

        foreach ($keywords as $keyword) {
            foreach ($this->fetchGoogleNews($keyword) as $item) {
                $raw[] = $item;
            }
        }

        foreach ($this->fetchRssFeeds() as $item) {
            $raw[] = $item;
        }

        $filtered = [];
        foreach ($raw as $item) {
            $textToCheck = $item['headline'].' '.$item['snippet'];
            $match = $this->matchesKeyword($textToCheck, $keywords);
            if (! $match) {
                continue;
            }
            $item['trigger_keyword'] = $match;
            $item['cluster_id'] = $this->clusterHash($item['headline']);
            $item['url'] = $this->normaliseUrl($item['url']);
            $filtered[] = $item;
        }

        $seen = [];
        $unique = [];
        foreach ($filtered as $item) {
            $key = ($item['cluster_id'] ?? '').$item['url'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    public function save(array $stories): int
    {
        $count = 0;
        foreach ($stories as $item) {
            $exists = Story::where('url', $item['url'])->first();
            if ($exists) {
                continue;
            }
            Story::create([
                'url' => $item['url'],
                'headline' => Str::limit($item['headline'], 500),
                'source' => $item['source'] ?? null,
                'snippet' => Str::limit($item['snippet'] ?? '', 600),
                'status' => Story::STATUS_PENDING,
                'trigger_keyword' => $item['trigger_keyword'] ?? null,
                'cluster_id' => $item['cluster_id'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    private function keywords(): array
    {
        $terms = SearchTerm::active()
            ->orderByDesc('priority')
            ->orderBy('phrase')
            ->pluck('phrase')
            ->toArray();

        if (empty($terms)) {
            return [
                'Private Rental Sector',
                'Landlord Law',
                'HMO Regulations',
                'PRS',
                'Section 21',
                'Section 8',
                'Buy to Let',
            ];
        }

        return $terms;
    }

    private function matchesKeyword(string $text, array $keywords): ?string
    {
        $textLower = strtolower($text);
        foreach ($keywords as $keyword) {
            if (str_contains($textLower, strtolower($keyword))) {
                return $keyword;
            }
        }

        return null;
    }

    private function normaliseUrl(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').($parts['path'] ?? '');
    }

    private function clusterHash(string $headline): string
    {
        $text = preg_replace('/[^a-z0-9\s]/', '', strtolower($headline));
        $words = array_filter(explode(' ', $text), fn ($w) => strlen($w) > 3);
        $words = array_unique($words);
        sort($words);

        return substr(md5(implode(' ', $words)), 0, 12);
    }

    private function fetchGoogleNews(string $keyword): array
    {
        $url = 'https://news.google.com/rss/search?q='.urlencode($keyword);
        try {
            $xml = Http::withOptions(['verify' => false])->timeout(15)->get($url)->body();
        } catch (\Throwable $e) {
            return [];
        }
        $feed = simplexml_load_string($xml);
        if (! $feed) {
            return [];
        }

        $results = [];
        foreach ($feed->channel->item ?? [] as $item) {
            $url = (string) $item->link;
            $realUrl = $this->resolveGoogleNewsUrl($url);

            $results[] = [
                'url' => $realUrl ?: $url,
                'headline' => (string) $item->title,
                'snippet' => strip_tags((string) ($item->description ?? '')),
                'source' => (string) ($item->source ?? 'Google News'),
            ];
            if (count($results) >= 8) {
                break;
            }
        }

        return $results;
    }

    private function resolveGoogleNewsUrl(string $url): ?string
    {
        if (! str_contains($url, 'news.google.com/rss/articles/')) {
            return $url;
        }

        // Google News links sometimes contain the real URL as a query param.
        $parts = parse_url($url);
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (! empty($query['url'])) {
                return $query['url'];
            }
        }

        try {
            // Attempt a HEAD request and follow redirects to find the real article.
            $response = Http::withOptions(['verify' => false, 'allow_redirects' => true])
                ->timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; iHowzBot/1.0)'])
                ->head($url);

            $final = $response->effectiveUri();
            if ($final && ! str_contains((string) $final, 'consent.google.com') && ! str_contains((string) $final, 'news.google.com')) {
                return (string) $final;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return $url;
    }

    private function fetchRssFeeds(): array
    {
        $feeds = RssFeed::active()
            ->orderByDesc('priority')
            ->orderBy('name')
            ->pluck('url')
            ->toArray();

        // Fallback to a small default set if the database is empty.
        if (empty($feeds)) {
            $feeds = [
                'https://www.property118.com/feed/',
                'https://www.landlordlawblog.co.uk/feed/',
            ];
        }

        $results = [];
        $maxPerFeed = 5;
        foreach ($feeds as $feedUrl) {
            try {
                $xml = Http::withOptions(['verify' => false])->timeout(15)->get($feedUrl)->body();
                $feed = simplexml_load_string($xml);
            } catch (\Throwable $e) {
                continue;
            }
            if (! $feed) {
                continue;
            }
            $source = (string) ($feed->channel->title ?? parse_url($feedUrl, PHP_URL_HOST));
            $count = 0;
            foreach ($feed->channel->item ?? [] as $item) {
                $results[] = [
                    'url' => (string) $item->link,
                    'headline' => (string) $item->title,
                    'snippet' => strip_tags((string) ($item->description ?? '')),
                    'source' => $source,
                ];
                $count++;
                if ($count >= $maxPerFeed) {
                    break;
                }
            }
        }

        return $results;
    }
}
