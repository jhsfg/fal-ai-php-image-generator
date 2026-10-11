<?php

declare(strict_types=1);

namespace ShareAIChat\FalAi;

/**
 * Built-in fal.ai image model catalog.
 *
 * Every entry contains everything the client needs to build a valid request,
 * including per-model parameter constraints (e.g. FLUX.1 Schnell only accepts
 * up to 12 inference steps and ignores guidance_scale).
 */
final class Models
{
    /** Fastest & cheapest FLUX.1 model — great for prototypes & bulk generation */
    public const FLUX_SCHNELL = 'fal-ai/flux/schnell';

    /** FLUX.1 Dev — higher quality text-to-image, default 28 steps */
    public const FLUX_DEV = 'fal-ai/flux/dev';

    /** FLUX.1 Kontext — context-aware image editing / image-to-image */
    public const FLUX_KONTEXT = 'fal-ai/flux-pro/kontext';

    /** FLUX.1 Kontext with multiple reference images */
    public const FLUX_KONTEXT_MULTI = 'fal-ai/flux-pro/kontext-multi';

    /** Stable Diffusion XL — classic open weights model */
    public const STABLE_DIFFUSION_XL = 'fal-ai/fast-sdxl';

    /**
     * Model catalog.
     *
     * @var array<string, array{
     *   name: string,
     *   endpoint: string,
     *   max_images: int,
     *   default_steps: int,
     *   max_steps: int,
     *   supports_guidance: bool,
     *   supports_negative_prompt: bool,
     *   image_to_image: bool
     * }>
     */
    private const CATALOG = [
        self::FLUX_SCHNELL => [
            'name'                    => 'FLUX.1 Schnell',
            'endpoint'                => 'fal-ai/flux/schnell',
            'max_images'              => 4,
            'default_steps'           => 4,
            // fal.ai rejects num_inference_steps > 12 for Schnell (HTTP 422)
            'max_steps'               => 12,
            'supports_guidance'       => false,
            'supports_negative_prompt' => true,
            'image_to_image'          => false,
        ],
        self::FLUX_DEV => [
            'name'                    => 'FLUX.1 Dev',
            'endpoint'                => 'fal-ai/flux/dev',
            'max_images'              => 4,
            'default_steps'           => 28,
            'max_steps'               => 50,
            'supports_guidance'       => true,
            'supports_negative_prompt' => true,
            'image_to_image'          => true,
        ],
        self::FLUX_KONTEXT => [
            'name'                    => 'FLUX.1 Kontext',
            'endpoint'                => 'fal-ai/flux-pro/kontext',
            'max_images'              => 1,
            'default_steps'           => 28,
            'max_steps'               => 50,
            'supports_guidance'       => true,
            'supports_negative_prompt' => false,
            'image_to_image'          => true,
        ],
        self::FLUX_KONTEXT_MULTI => [
            'name'                    => 'FLUX.1 Kontext Multi',
            'endpoint'                => 'fal-ai/flux-pro/kontext-multi',
            'max_images'              => 1,
            'default_steps'           => 28,
            'max_steps'               => 50,
            'supports_guidance'       => true,
            'supports_negative_prompt' => false,
            'image_to_image'          => true,
        ],
        self::STABLE_DIFFUSION_XL => [
            'name'                    => 'Stable Diffusion XL',
            'endpoint'                => 'fal-ai/fast-sdxl',
            'max_images'              => 1,
            'default_steps'           => 30,
            'max_steps'               => 50,
            'supports_guidance'       => true,
            'supports_negative_prompt' => true,
            'image_to_image'          => true,
        ],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::CATALOG;
    }

    /**
     * @return array<string, mixed>
     * @throws FalAiException for unknown model ids
     */
    public static function get(string $modelId): array
    {
        if (!isset(self::CATALOG[$modelId])) {
            throw new FalAiException("Unknown fal.ai model: {$modelId}");
        }
        return self::CATALOG[$modelId];
    }
}
