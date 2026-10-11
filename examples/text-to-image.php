<?php

/**
 * Example 1 — Basic text-to-image generation with FLUX.1 Schnell.
 *
 * Run:
 *   FAL_KEY=your_key php examples/text-to-image.php
 */

declare(strict_types=1);

use ShareAIChat\FalAi\FalAiClient;
use ShareAIChat\FalAi\Models;

require __DIR__ . '/bootstrap.php';

$client = new FalAiClient(fal_demo_api_key());

echo "Generating image with FLUX.1 Schnell...\n";

$images = $client->textToImage(
    'a samurai cat standing in a neon-lit cyberpunk alley at midnight, cinematic lighting, ultra detailed',
    [
        'model'      => Models::FLUX_SCHNELL,
        'image_size' => FalAiClient::SIZE_LANDSCAPE_16_9,
        'num_images' => 1,
    ]
);

foreach ($images as $i => $image) {
    echo sprintf(
        "Image #%d (%dx%d): %s\n",
        $i + 1,
        $image->width ?? 0,
        $image->height ?? 0,
        $image->url
    );
}
