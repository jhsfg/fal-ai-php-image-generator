<?php

declare(strict_types=1);

namespace ShareAIChat\FalAi;

/**
 * Minimal, zero-dependency PHP client for the fal.ai image generation API.
 *
 * Supports text-to-image and image-to-image generation with popular models:
 * FLUX.1 Schnell (fastest/cheapest), FLUX.1 Dev, FLUX Kontext (image editing)
 * and Stable Diffusion XL.
 *
 * Quick start:
 *
 *   $client  = new FalAiClient($apiKey);
 *   $images  = $client->textToImage('a cyberpunk cat in neon Tokyo');
 *   echo $images[0]->url;
 *
 * This library powers the AI creation studio at https://shareaichat.com
 */
class FalAiClient
{
    // fal.ai image_size enum values
    public const SIZE_SQUARE_HD    = 'square_hd';
    public const SIZE_SQUARE       = 'square';
    public const SIZE_PORTRAIT_4_3 = 'portrait_4_3';
    public const SIZE_PORTRAIT_16_9 = 'portrait_16_9';
    public const SIZE_LANDSCAPE_4_3 = 'landscape_4_3';
    public const SIZE_LANDSCAPE_16_9 = 'landscape_16_9';

    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private int $connectTimeout;

    /**
     * @param string $apiKey         fal.ai API key (https://fal.ai/dashboard/keys)
     * @param array  $options        base_url / timeout / connect_timeout overrides
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if ($apiKey === '') {
            throw new FalAiException('fal.ai API key is required. Get one at https://fal.ai/dashboard/keys');
        }
        $this->apiKey         = $apiKey;
        $this->baseUrl        = rtrim($options['base_url'] ?? 'https://fal.run', '/');
        $this->timeout        = (int)($options['timeout'] ?? 180);
        $this->connectTimeout = (int)($options['connect_timeout'] ?? 10);
    }

    /**
     * Generate one or more images from a text prompt (text-to-image).
     *
     * @param string $prompt  Description of the image you want to create
     * @param array  $options Optional:
     *   - model:            string  Model id (default: Models::FLUX_SCHNELL)
     *   - image_size:       string  One of the SIZE_* constants (default: square_hd)
     *   - num_images:       int     1..model max (default: 1)
     *   - num_inference_steps: int  Override per-model default (validated)
     *   - guidance_scale:   float   Sent only when the model supports it
     *   - negative_prompt:  string  Things to avoid in the image
     *   - safety_checker:   bool    Enable/disable safety checker (default: false)
     *   - extra:            array   Any additional raw API parameters
     *
     * @return GeneratedImage[]
     * @throws FalAiException
     */
    public function textToImage(string $prompt, array $options = []): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new FalAiException('Prompt must not be empty');
        }

        $modelId = $options['model'] ?? Models::FLUX_SCHNELL;
        $model   = Models::get($modelId);

        $numImages = (int)($options['num_images'] ?? 1);
        if ($numImages < 1 || $numImages > $model['max_images']) {
            throw new FalAiException(
                "num_images must be between 1 and {$model['max_images']} for {$model['name']}"
            );
        }

        $steps = (int)($options['num_inference_steps'] ?? $model['default_steps']);
        if ($steps > $model['max_steps']) {
            throw new FalAiException(
                "num_inference_steps must be <= {$model['max_steps']} for {$model['name']} (got {$steps})"
            );
        }

        $payload = array_merge([
            'prompt'              => $prompt,
            'image_size'          => $options['image_size'] ?? self::SIZE_SQUARE_HD,
            'num_images'          => $numImages,
            'num_inference_steps' => $steps,
            'enable_safety_checker' => $options['safety_checker'] ?? false,
        ], $options['extra'] ?? []);

        // guidance_scale is not accepted by every model (FLUX.1 Schnell rejects it)
        if ($model['supports_guidance'] && isset($options['guidance_scale'])) {
            $payload['guidance_scale'] = (float)$options['guidance_scale'];
        }
        if ($model['supports_negative_prompt'] && !empty($options['negative_prompt'])) {
            $payload['negative_prompt'] = (string)$options['negative_prompt'];
        }

        $data = $this->request($model['endpoint'], $payload);
        return $this->parseImages($data, $model['name']);
    }

    /**
     * Generate an image conditioned on one or more reference images
     * (image-to-image / image editing). Use a model that supports it,
     * e.g. Models::FLUX_DEV or Models::FLUX_KONTEXT.
     *
     * @param string       $prompt        Editing instruction
     * @param string|array $referenceUrls One URL or a list of URLs (multi-reference)
     * @param array        $options       Same options as textToImage()
     *
     * @return GeneratedImage[]
     * @throws FalAiException
     */
    public function imageToImage(string $prompt, string|array $referenceUrls, array $options = []): array
    {
        $modelId = $options['model'] ?? Models::FLUX_DEV;
        $model   = Models::get($modelId);
        if (!$model['image_to_image']) {
            throw new FalAiException("{$model['name']} does not support image-to-image");
        }

        $urls = is_array($referenceUrls) ? array_values($referenceUrls) : [$referenceUrls];
        $urls = array_filter(array_map('strval', $urls), fn($u) => trim($u) !== '');
        if (empty($urls)) {
            throw new FalAiException('At least one reference image URL is required');
        }

        $options['num_images'] = 1;
        // Multi-reference models expect input_image_urls; single-image models expect image_url
        $options['extra'] = ($options['extra'] ?? []) + (
            count($urls) > 1
                ? ['input_image_urls' => array_values($urls)]
                : ['image_url' => $urls[0]]
        );

        return $this->textToImage($prompt, $options + ['model' => $modelId]);
    }

    /**
     * Escape hatch: call any fal.ai image endpoint with a raw payload.
     *
     * @return GeneratedImage[]
     * @throws FalAiException
     */
    public function generate(string $endpoint, array $payload): array
    {
        $data = $this->request($endpoint, $payload);
        return $this->parseImages($data, $endpoint);
    }

    /**
     * Raw authenticated POST to a fal.ai endpoint.
     *
     * @throws FalAiException
     */
    public function request(string $endpoint, array $payload): array
    {
        $ch = curl_init($this->baseUrl . '/' . ltrim($endpoint, '/'));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Key ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
        ]);

        $body  = curl_exec($ch);
        $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $err   = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new FalAiException('Network error calling fal.ai: ' . $err);
        }

        $data = json_decode((string)$body, true);
        if (!is_array($data)) {
            throw new FalAiException(
                'Invalid JSON response from fal.ai (HTTP ' . $code . '): ' . substr((string)$body, 0, 300),
                $code
            );
        }

        if ($code < 200 || $code >= 300) {
            $message = $this->extractErrorMessage($data)
                ?? ('fal.ai API error (HTTP ' . $code . ')');
            throw new FalAiException($message, $code, $data);
        }

        return $data;
    }

    /**
     * Parse the standard { images: [{ url, width, height, content_type }] } response.
     *
     * @return GeneratedImage[]
     * @throws FalAiException
     */
    private function parseImages(array $data, string $modelName): array
    {
        $images = $data['images'] ?? [];
        $result = [];
        foreach ($images as $img) {
            if (!empty($img['url'])) {
                $result[] = new GeneratedImage(
                    $img['url'],
                    isset($img['width']) ? (int)$img['width'] : null,
                    isset($img['height']) ? (int)$img['height'] : null,
                    $img['content_type'] ?? null
                );
            }
        }

        if (empty($result)) {
            throw new FalAiException("No image returned by {$modelName}");
        }
        return $result;
    }

    /**
     * fal.ai returns validation errors as {"detail":[{"msg":...}]} or {"detail":"..."}
     * and upstream errors as {"error": "..."} / {"message": "..."}.
     */
    private function extractErrorMessage(array $data): ?string
    {
        if (isset($data['detail'])) {
            $detail = $data['detail'];
            if (is_array($detail)) {
                $messages = [];
                foreach ($detail as $item) {
                    if (is_array($item) && isset($item['msg'])) {
                        $loc = isset($item['loc']) ? implode('.', array_slice((array)$item['loc'], 1)) : '';
                        $messages[] = ($loc !== '' ? "{$loc}: " : '') . $item['msg'];
                    } elseif (is_string($item)) {
                        $messages[] = $item;
                    }
                }
                if ($messages) return implode('; ', $messages);
            }
            if (is_string($detail)) return $detail;
        }
        foreach (['error', 'message'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && $data[$key] !== '') {
                return $data[$key];
            }
        }
        return null;
    }
}
