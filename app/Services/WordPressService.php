<?php

namespace App\Services;

use App\Models\Story;
use App\Models\WpSetting;
use Illuminate\Support\Facades\Http;

class WordPressService
{
    private function settings(): ?WpSetting
    {
        return WpSetting::current();
    }

    private function baseUrl(): string
    {
        return rtrim($this->settings()?->base_url ?? '', '/');
    }

    private function headers(): array
    {
        $settings = $this->settings();
        if (! $settings || ! $settings->username || ! $settings->application_password) {
            throw new \RuntimeException('WordPress credentials are not configured.');
        }

        return [
            'Authorization' => 'Basic '.base64_encode("{$settings->username}:{$settings->application_password}"),
            'Accept' => 'application/json',
        ];
    }

    public function publish(Story $story): array
    {
        $base = $this->baseUrl();
        $headers = $this->headers();

        $categoryId = $this->ensureCategory($base, $story->suggested_category ?: 'News', $headers);
        $tagIds = $this->ensureTags($base, $story->suggested_tags ?: [], $headers);

        $featuredMediaId = null;
        if ($story->image_url) {
            $featuredMediaId = $this->uploadFeaturedImage($base, $story->image_url, $story->headline, $headers);
        }

        $payload = [
            'title' => $story->headline,
            'content' => $story->article_text,
            'status' => 'publish',
            'categories' => [$categoryId],
            'tags' => $tagIds,
            'meta' => ['description' => $story->meta_description ?? ''],
        ];
        if ($featuredMediaId) {
            $payload['featured_media'] = $featuredMediaId;
        }

        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->timeout(30)
            ->post("{$base}/wp-json/wp/v2/posts", $payload);

        $response->throw();
        $wpPost = $response->json();

        $story->update([
            'wp_post_id' => $wpPost['id'] ?? null,
            'status' => Story::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        return [
            'wp_post_id' => $wpPost['id'] ?? null,
            'wp_link' => $wpPost['link'] ?? null,
            'published_at' => now(),
        ];
    }

    private function ensureCategory(string $base, string $name, array $headers): int
    {
        $search = Http::withHeaders(['Authorization' => $headers['Authorization']])
            ->withOptions(['verify' => false])
            ->timeout(20)
            ->get("{$base}/wp-json/wp/v2/categories", ['search' => $name]);
        $search->throw();

        foreach ($search->json() as $cat) {
            if (strtolower($cat['name']) === strtolower($name)) {
                return $cat['id'];
            }
        }

        $create = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->timeout(20)
            ->post("{$base}/wp-json/wp/v2/categories", ['name' => $name]);
        $create->throw();

        return $create->json()['id'];
    }

    private function ensureTags(string $base, array $tags, array $headers): array
    {
        $ids = [];
        foreach ($tags as $tag) {
            $search = Http::withHeaders(['Authorization' => $headers['Authorization']])
                ->withOptions(['verify' => false])
                ->timeout(20)
                ->get("{$base}/wp-json/wp/v2/tags", ['search' => $tag]);
            $search->throw();

            $found = null;
            foreach ($search->json() as $t) {
                if (strtolower($t['name']) === strtolower($tag)) {
                    $found = $t['id'];
                    break;
                }
            }

            if ($found) {
                $ids[] = $found;
            } else {
                $create = Http::withHeaders($headers)
                    ->withOptions(['verify' => false])
                    ->timeout(20)
                    ->post("{$base}/wp-json/wp/v2/tags", ['name' => $tag]);
                $create->throw();
                $ids[] = $create->json()['id'];
            }
        }

        return $ids;
    }

    private function uploadFeaturedImage(string $base, string $imageUrl, string $title, array $headers): ?int
    {
        try {
            $imageBytes = Http::withOptions(['verify' => false])->timeout(60)->get($imageUrl)->body();
            $uploadHeaders = array_merge($headers, ['Content-Disposition' => 'attachment; filename="featured.jpg"']);
            unset($uploadHeaders['Content-Type']);

            $response = Http::withHeaders($uploadHeaders)
                ->withOptions(['verify' => false])
                ->attach('file', $imageBytes, 'featured.jpg')
                ->timeout(60)
                ->post("{$base}/wp-json/wp/v2/media", ['title' => $title]);
            $response->throw();

            return $response->json()['id'] ?? null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
