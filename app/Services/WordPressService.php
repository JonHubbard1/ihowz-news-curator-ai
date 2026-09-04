<?php

namespace App\Services;

use App\Models\Story;
use App\Models\WpSetting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

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

    /**
     * Build a Guzzle client configured for the configured WordPress host.
     *
     * Herd's .test DNS resolver only returns A records; PHP/cURL's dual-stack
     * lookup stalls waiting for an AAAA response. We bypass system DNS by
     * pinning the WordPress host to 127.0.0.1 via curl's resolve option and
     * forcing IPv4.
     */
    private function client(): Client
    {
        $baseUrl = $this->baseUrl();
        $host = parse_url($baseUrl, PHP_URL_HOST) ?: 'localhost';
        $port = parse_url($baseUrl, PHP_URL_PORT) ?: 80;
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'http';
        $port = $port ?: ($scheme === 'https' ? 443 : 80);

        return new Client([
            RequestOptions::VERIFY => false,
            RequestOptions::TIMEOUT => 30,
            RequestOptions::CONNECT_TIMEOUT => 5,
            RequestOptions::FORCE_IP_RESOLVE => 'v4',
            'curl' => [
                CURLOPT_RESOLVE => ["{$host}:{$port}:127.0.0.1"],
            ],
        ]);
    }

    private function request(string $method, string $uri, array $options = []): array
    {
        try {
            $response = $this->client()->request($method, $uri, array_merge([
                'headers' => $this->headers(),
                'http_errors' => true,
            ], $options));
        } catch (ClientException $e) {
            $this->handleClientError($e);
        } catch (RequestException $e) {
            throw new \RuntimeException('WordPress request failed: '.$e->getMessage(), 0, $e);
        }

        $body = (string) $response->getBody();

        return json_decode($body, true) ?? [];
    }

    private function handleClientError(ClientException $exception): never
    {
        $response = $exception->getResponse();
        $status = $response?->getStatusCode();
        $body = [];

        if ($response) {
            try {
                $body = json_decode((string) $response->getBody(), true) ?? [];
            } catch (\Throwable) {
                // Ignore malformed JSON bodies.
            }
        }

        $message = $body['message'] ?? $exception->getMessage();

        if ($status === 401) {
            throw new \RuntimeException(
                'WordPress authentication failed. Please ensure the username and application password are correct and that Application Passwords are enabled.',
                401,
                $exception
            );
        }

        if ($status === 403) {
            throw new \RuntimeException(
                'WordPress permission denied: '.$message,
                403,
                $exception
            );
        }

        throw new \RuntimeException('WordPress API error ('.$status.'): '.$message, $status ?? 0, $exception);
    }

    public function publish(Story $story): array
    {
        $base = $this->baseUrl();

        $categoryId = $this->ensureCategory($base, $story->suggested_category ?: 'News');
        $tagIds = $this->ensureTags($base, $story->suggested_tags ?: []);

        $featuredMediaId = null;
        if ($story->image_url) {
            $featuredMediaId = $this->uploadFeaturedImage($base, $story->image_url, $story->headline);
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

        $wpPost = $this->request('POST', "{$base}/wp-json/wp/v2/posts", [
            'json' => $payload,
        ]);

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

    private function ensureCategory(string $base, string $name): int
    {
        $normalised = strtolower(trim($name));

        // Fetch all existing categories so we can fuzzy-match against them.
        $categories = $this->request('GET', "{$base}/wp-json/wp/v2/categories", [
            'query' => ['per_page' => 100],
        ]);

        $bestId = null;
        $bestSimilarity = 0;
        foreach ($categories as $cat) {
            $catName = strtolower(trim($cat['name']));
            if ($catName === $normalised) {
                return $cat['id'];
            }

            similar_text($catName, $normalised, $similarity);
            if ($similarity >= 70 && $similarity > $bestSimilarity) {
                $bestSimilarity = $similarity;
                $bestId = $cat['id'];
            }
        }

        if ($bestId) {
            return $bestId;
        }

        // Generalise overly specific names before creating a new category.
        $genericName = $this->generaliseCategoryName($name);
        $normalisedGeneric = strtolower(trim($genericName));
        foreach ($categories as $cat) {
            if (strtolower(trim($cat['name'])) === $normalisedGeneric) {
                return $cat['id'];
            }
        }

        // Ensure the parent "News" category exists.
        $newsParentId = $this->ensureNewsParentCategory($base);

        $created = $this->request('POST', "{$base}/wp-json/wp/v2/categories", [
            'json' => [
                'name' => $genericName,
                'parent' => $newsParentId,
            ],
        ]);

        return $created['id'];
    }

    private function ensureNewsParentCategory(string $base): int
    {
        $categories = $this->request('GET', "{$base}/wp-json/wp/v2/categories", [
            'query' => ['search' => 'News'],
        ]);

        foreach ($categories as $cat) {
            if (strtolower(trim($cat['name'])) === 'news') {
                return $cat['id'];
            }
        }

        $created = $this->request('POST', "{$base}/wp-json/wp/v2/categories", [
            'json' => ['name' => 'News'],
        ]);

        return $created['id'];
    }

    private function generaliseCategoryName(string $name): string
    {
        $name = trim($name);
        $lower = strtolower($name);

        // Strip common noisy suffixes/prefixes.
        $name = preg_replace('/\s+(news|update|report|latest|insights?|analysis)$/i', '', $name);
        $name = preg_replace('/^(latest|breaking|new)\s+/i', '', $name);

        $map = [
            'landlord' => 'Landlords',
            'letting' => 'Letting Agents',
            'tenant' => 'Tenants',
            'rent' => 'Rent & Renting',
            'rental' => 'Rent & Renting',
            'housing' => 'Housing Market',
            'property' => 'Property Market',
            'regulation' => 'Regulations',
            'regulatory' => 'Regulations',
            'law' => 'Regulations',
            'legal' => 'Regulations',
            'eviction' => 'Evictions',
            'mortgage' => 'Finance',
            'tax' => 'Finance',
            'energy' => 'Property Standards',
            'council' => 'Local Government',
            'building' => 'Property Standards',
            'safety' => 'Property Standards',
        ];

        foreach ($map as $needle => $replacement) {
            if (str_contains($lower, $needle)) {
                return $replacement;
            }
        }

        return $name !== '' ? $name : 'News';
    }

    private function ensureTags(string $base, array $tags): array
    {
        $ids = [];
        foreach ($tags as $tag) {
            $found = null;
            $results = $this->request('GET', "{$base}/wp-json/wp/v2/tags", [
                'query' => ['search' => $tag],
            ]);

            foreach ($results as $t) {
                if (strtolower($t['name']) === strtolower($tag)) {
                    $found = $t['id'];
                    break;
                }
            }

            if ($found) {
                $ids[] = $found;
            } else {
                $created = $this->request('POST', "{$base}/wp-json/wp/v2/tags", [
                    'json' => ['name' => $tag],
                ]);
                $ids[] = $created['id'];
            }
        }

        return $ids;
    }

    private function uploadFeaturedImage(string $base, string $imageUrl, string $title): ?int
    {
        try {
            $imageBytes = $this->client()->get($imageUrl, [
                RequestOptions::TIMEOUT => 60,
            ])->getBody()->getContents();

            $imageBytes = $this->cropToBanner($imageBytes);

            $uploadHeaders = array_merge($this->headers(), [
                'Content-Disposition' => 'attachment; filename="featured.jpg"',
            ]);
            unset($uploadHeaders['Content-Type']);

            $uploaded = $this->request('POST', "{$base}/wp-json/wp/v2/media", [
                'headers' => $uploadHeaders,
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => $imageBytes,
                        'filename' => 'featured.jpg',
                    ],
                    [
                        'name' => 'title',
                        'contents' => $title,
                    ],
                ],
            ]);

            return $uploaded['id'] ?? null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Crop/resample source image to a landscape banner only when the aspect ratio
     * is not already suitable. We preserve wide/landscape images (e.g. OpenAI
     * 1536x1024, 1.5:1) and only crop photos that are taller than a 16:9 banner.
     */
    private function cropToBanner(string $imageBytes): string
    {
        $source = imagecreatefromstring($imageBytes);
        if (! $source) {
            throw new \RuntimeException('Could not decode source image.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $targetWidth = 1280;
        $targetHeight = 512;
        $maxAspect = 16 / 9;

        $sourceAspect = $sourceWidth / $sourceHeight;

        // Already a wide landscape image or smaller than target width: leave it alone.
        if ($sourceAspect >= $maxAspect || $sourceWidth < $targetWidth) {
            imagedestroy($source);

            return $imageBytes;
        }

        // Source is taller/portrait: crop/resample to a 16:9 landscape banner.
        $targetAspect = $targetWidth / $targetHeight;

        if ($sourceAspect > $targetAspect) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $targetAspect);
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetAspect);
        }

        $cropX = (int) round(($sourceWidth - $cropWidth) / 2);
        $cropY = (int) round(($sourceHeight - $cropHeight) / 2);

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled(
            $resized,
            $source,
            0,
            0,
            $cropX,
            $cropY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight
        );

        ob_start();
        imagejpeg($resized, null, 90);
        $output = ob_get_clean();

        imagedestroy($source);
        imagedestroy($resized);

        return $output;
    }
}
