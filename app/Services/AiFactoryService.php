<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\Story;
use App\Models\StoryEdit;
use Illuminate\Support\Facades\Http;
use OpenAI;
use OpenAI\Exceptions\ErrorException;

class AiFactoryService
{
    public function __construct(private AiCostLogger $costLogger) {}

    private function client(): OpenAI\Client
    {
        $settings = AiSetting::current();
        if (! $settings->openai_api_key) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        return OpenAI::client($settings->openai_api_key);
    }

    private function llmModel(): string
    {
        return AiSetting::current()->llm_model ?: config('news.default_llm_model');
    }

    private function imageModel(): string
    {
        return AiSetting::current()->image_model ?: config('news.default_image_model');
    }

    private function imageProvider(): string
    {
        return AiSetting::current()->image_provider ?: 'openai';
    }

    private function falApiKey(): ?string
    {
        return AiSetting::current()->fal_api_key;
    }

    private function falModel(): string
    {
        return AiSetting::current()->fal_model ?: 'fal-ai/flux-pro/v1.1-ultra';
    }

    private function brandVoice(): string
    {
        return AiSetting::current()->brand_voice ?: config('news.brand_voice');
    }

    private function imageCostUsd(): float
    {
        $settings = AiSetting::current();

        return (float) $settings->image_cost_per_image * ((float) $settings->cost_markup_multiplier ?: 1.0);
    }

    private function falCostUsd(): float
    {
        $settings = AiSetting::current();

        return (float) $settings->fal_cost_per_image * ((float) $settings->cost_markup_multiplier ?: 1.0);
    }

    public function process(Story $story): void
    {
        $story->update(['status' => Story::STATUS_PROCESSING]);

        try {
            $rawText = $this->researchUrl($story->url);
            [$headline, $article, $category, $tags, $meta] = $this->synthesizeArticle($story, $rawText);

            // Image generation is best-effort. If the API key lacks image access,
            // we still produce a draft so the editorial workflow is not blocked.
            $imageUrl = null;
            try {
                $imagePrompt = $this->generateImagePrompt($story, $headline, $article);
                $imageUrl = $this->generateImage($story, $imagePrompt);
            } catch (\Throwable $imageException) {
                report($imageException);
            }

            $story->update([
                'headline' => $headline,
                'article_text' => $article,
                'suggested_category' => $category,
                'suggested_tags' => $tags,
                'meta_description' => $meta,
                'image_url' => $imageUrl,
                'status' => Story::STATUS_DRAFT,
            ]);
        } catch (\Throwable $e) {
            $story->update(['status' => Story::STATUS_USED]);
            throw $e;
        }
    }

    public function applyCommand(Story $story, string $command): string
    {
        $client = $this->client();
        $model = $this->llmModel();
        $response = $client->chat()->create([
            'model' => $model,
            'temperature' => 0.6,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert UK property-industry editor.'],
                ['role' => 'user', 'content' => 'Given this article for '.config('news.brand_name').":\n\n{$story->article_text}\n\nInstruction: {$command}\n\nReturn the revised full article text only, preserving the same tone and brand voice ({$this->brandVoice()})."],
            ],
        ]);

        $this->costLogger->logEdit($story, $model, $response->toArray());

        $revised = trim($response->choices[0]->message->content);

        StoryEdit::create([
            'story_id' => $story->id,
            'field' => 'article_text',
            'previous_value' => $story->article_text,
            'new_value' => $revised,
            'ai_command' => $command,
        ]);

        $story->update(['article_text' => $revised]);

        return $revised;
    }

    public function regenerateImage(Story $story): string
    {
        $prompt = $this->generateImagePrompt($story, $story->headline, $story->article_text ?? '');
        $url = $this->generateImage($story, $prompt);
        $story->update(['image_url' => $url]);

        return $url;
    }

    private function researchUrl(string $url): string
    {
        // Follow redirects to reach the real article, bypassing Google consent pages.
        $response = Http::withOptions(['verify' => false, 'allow_redirects' => true])
            ->timeout(15)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; iHowzBot/1.0)'])
            ->get($url);

        $finalUrl = $response->effectiveUri();
        if ($finalUrl && str_contains((string) $finalUrl, 'consent.google.com')) {
            return '';
        }

        $text = $this->extractArticleText($response->body());

        // If extracted text looks like a consent/cookie wall, return empty.
        if (str_contains(strtolower($text), 'before you continue') ||
            str_contains(strtolower($text), 'accept all cookies') ||
            str_contains(strtolower($text), 'reject all cookies') ||
            str_contains(strtolower($text), 'google uses cookies') ||
            str_contains(strtolower($text), 'this site uses cookies')
        ) {
            return '';
        }

        return $text;
    }

    private function extractArticleText(string $html): string
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        foreach (['script', 'style', 'nav', 'footer', 'header', 'aside'] as $tag) {
            foreach ($xpath->query("//{$tag}") as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        $paragraphs = $dom->getElementsByTagName('p');
        $texts = [];
        foreach ($paragraphs as $p) {
            $t = trim($p->textContent);
            if (strlen($t) > 40) {
                $texts[] = $t;
            }
        }

        return substr(implode("\n\n", $texts), 0, 8000);
    }

    private function synthesizeArticle(Story $story, string $rawText): array
    {
        $client = $this->client();

        // When scraping fails, fall back to headline + snippet so the LLM can still
        // write about the actual news topic instead of generic cookie boilerplate.
        $sourceContext = trim($rawText) !== ''
            ? substr($rawText, 0, 6000)
            : "Source headline: {$story->headline}\nSource snippet: ".($story->snippet ?: 'No snippet available.');

        $targetLength = (int) AiSetting::current()->target_article_length;

        $prompt = 'You are the senior editor for '.config('news.brand_name').", a trusted voice in the UK Private Rental Sector.

Your task: write a unique, original, SEO-friendly news article for landlords and letting agents.

CRITICAL RULES:
- The source headline is: '{$story->headline}'. Your article MUST be about this exact topic.
- Do NOT write about generic cookie notices, privacy pop-ups, consent banners, or data protection unless that is genuinely the main story.
- If the source text is short or mostly boilerplate, infer the real news from the headline and write about that topic.
- Do NOT copy sentences verbatim from the source.
- Synthesize facts, add context, and explain what it means for landlords.
- Tone: {$this->brandVoice()}.
- Target length: approximately {$targetLength} words.
- Structure: compelling headline, 1-paragraph intro, 3-5 concise body paragraphs, 1-paragraph practical takeaway.
- Suggest a WordPress category (max 30 chars) and 3-5 tags.
- Write a meta description (max 160 chars) that encourages clicks.

Return ONLY valid JSON in this exact format:
{
  \"headline\": \"...\",
  \"article\": \"...\",
  \"category\": \"...\",
  \"tags\": [\"...\"],
  \"meta_description\": \"...\"
}

Source URL: {$story->url}
Source content:
{$sourceContext}";

        $model = $this->llmModel();
        $response = $client->chat()->create([
            'model' => $model,
            'temperature' => 0.7,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful UK property-industry content editor. Output only valid JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $this->costLogger->logLlm($story, $model, $response->toArray());

        $content = trim($response->choices[0]->message->content);
        $content = preg_replace('/^```json\s*|\s*```$/m', '', $content);
        $data = json_decode($content, true);

        return [
            $data['headline'] ?? $story->headline,
            $data['article'] ?? '',
            $data['category'] ?? 'News',
            $data['tags'] ?? [],
            $data['meta_description'] ?? '',
        ];
    }

    private function generateImagePrompt(Story $story, string $headline, string $article): string
    {
        $client = $this->client();
        $model = $this->llmModel();

        $brandName = config('news.brand_name');
        $articleExcerpt = substr($article, 0, 900);

        $response = $client->chat()->create([
            'model' => $model,
            'temperature' => 0.7,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert prompt engineer for AI image generators used by a UK property news website. You write detailed, literal prompts for photorealistic editorial photography.'],
                ['role' => 'user', 'content' => "We need a single, high-quality landscape banner image for a news article on {$brandName}.

Headline: '{$headline}'
Article excerpt: {$articleExcerpt}

Write a concise but specific image-generation prompt (60–120 words) that will produce a photorealistic editorial photograph suitable for a UK property-industry news story. The scene should clearly relate to the article topic and feel like stock photography from a reputable news or property website.

Requirements:
- Realistic photography style, natural lighting, no illustrations, no cartoons, no AI-looking artefacts.
- Subject must be centred horizontally and vertically so it survives a centre-crop to 1280x512 pixels.
- Prefer wide, landscape-friendly scenes: exteriors of houses/flats, streets, letting-agent office interiors, hands signing documents, keys on a table, etc.
- Avoid busy crowds, small text, or fine detail that will be lost in a banner crop.
- No text, logos, watermarks, captions, or words in the image.
- Use simple, literal language that the image model will follow accurately.

Return only the prompt text."],
            ],
        ]);

        $this->costLogger->logLlm($story, $model, $response->toArray());

        return trim($response->choices[0]->message->content);
    }

    private function generateImage(Story $story, string $prompt): string
    {
        if ($this->imageProvider() === 'fal') {
            return $this->generateFalImage($story, $prompt);
        }

        return $this->generateOpenAiImage($story, $prompt);
    }

    private function generateOpenAiImage(Story $story, string $prompt): string
    {
        $client = $this->client();
        $model = $this->imageModel();

        try {
            $payload = [
                'model' => $model,
                'prompt' => $prompt,
                'n' => 1,
            ];

            // gpt-image-1 and DALL-E families have different accepted parameters.
            if ($model === 'gpt-image-1') {
                $payload['quality'] = 'medium';
                $payload['size'] = '1536x1024';
            } else {
                $payload['quality'] = 'standard';
                $payload['size'] = '1792x1024';
            }

            $response = $client->images()->create($payload);

            $this->costLogger->logImage($story, 'openai', $model, $this->imageCostUsd());

            return $response->data[0]->url;
        } catch (ErrorException $e) {
            // Fallback to dall-e-2 if the chosen model is unavailable on this key.
            if (str_contains($e->getMessage(), 'does not exist') && $model !== 'dall-e-2') {
                $response = $client->images()->create([
                    'model' => 'dall-e-2',
                    'prompt' => $prompt,
                    'size' => '1024x512',
                    'n' => 1,
                ]);

                $this->costLogger->logImage($story, 'openai', 'dall-e-2', $this->imageCostUsd());

                return $response->data[0]->url;
            }

            throw $e;
        }
    }

    private function generateFalImage(Story $story, string $prompt): string
    {
        $key = $this->falApiKey();
        if (! $key) {
            throw new \RuntimeException('FAL API key is not configured.');
        }

        $model = $this->falModel();
        $url = "https://queue.fal.run/{$model}";

        // Submit the request to FAL's queue.
        $submit = Http::withHeaders([
            'Authorization' => "Key {$key}",
            'Content-Type' => 'application/json',
        ])->withOptions(['verify' => false])
            ->timeout(30)
            ->post($url, [
                'prompt' => $prompt,
                'image_size' => 'landscape_16_9',
                'aspect_ratio' => '16:9',
                'num_images' => 1,
            ]);

        $submit->throw();
        $data = $submit->json();

        // Fast path: synchronous response already contains the image.
        $imageUrl = $this->extractFalImageUrl($data);
        if ($imageUrl) {
            $this->costLogger->logImage($story, 'fal', $model, $this->falCostUsd(), ['queue' => false]);

            return $imageUrl;
        }

        // Async path: poll the status_url until the request is completed, then fetch response_url.
        $statusUrl = $data['status_url'] ?? null;
        $responseUrl = $data['response_url'] ?? null;
        if (! $statusUrl || ! $responseUrl) {
            throw new \RuntimeException('FAL response did not contain status_url/response_url: '.json_encode($data));
        }

        $start = time();
        $maxWait = 120;
        $pollInterval = 2;

        while (time() - $start < $maxWait) {
            sleep($pollInterval);

            $status = Http::withHeaders([
                'Authorization' => "Key {$key}",
            ])->withOptions(['verify' => false])
                ->timeout(30)
                ->get($statusUrl);

            $status->throw();
            $statusData = $status->json();

            $state = $statusData['status'] ?? 'UNKNOWN';
            if (in_array($state, ['FAILED', 'CANCELLED', 'TIMEOUT'], true)) {
                throw new \RuntimeException("FAL request failed with status: {$state}. Response: ".json_encode($statusData));
            }

            if ($state === 'COMPLETED') {
                $result = Http::withHeaders([
                    'Authorization' => "Key {$key}",
                ])->withOptions(['verify' => false])
                    ->timeout(30)
                    ->get($responseUrl);

                $result->throw();
                $resultData = $result->json();

                $imageUrl = $this->extractFalImageUrl($resultData);
                if ($imageUrl) {
                    $this->costLogger->logImage($story, 'fal', $model, $this->falCostUsd(), ['queue' => true]);

                    return $imageUrl;
                }

                throw new \RuntimeException('FAL response did not contain an image URL: '.json_encode($resultData));
            }
        }

        throw new \RuntimeException('FAL image generation timed out after '.$maxWait.' seconds.');
    }

    private function extractFalImageUrl(array $data): ?string
    {
        if (! empty($data['images'][0]['url'])) {
            return $data['images'][0]['url'];
        }

        if (! empty($data['image']['url'])) {
            return $data['image']['url'];
        }

        if (! empty($data['output']) && is_array($data['output'])) {
            if (! empty($data['output']['images'][0]['url'])) {
                return $data['output']['images'][0]['url'];
            }

            if (! empty($data['output']['image']['url'])) {
                return $data['output']['image']['url'];
            }
        }

        return null;
    }
}
