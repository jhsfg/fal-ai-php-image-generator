<?php

/**
 * Example 3 — Generate an image with FLUX.1 Dev and save it to disk.
 *
 * Run:
 *   FAL_KEY=your_key php examples/save-image.php
 */

declare(strict_types=1);

use ShareAIChat\FalAi\FalAiClient;
use ShareAIChat\FalAi\Models;

require __DIR__ . '/bootstrap.php';

$client = new FalAiClient(fal_demo_api_key());

$images = $client->textToImage(
    'a magical library floating in the clouds, golden hour light, fantasy concept art',
    [
        'model'      => Models::FLUX_DEV,           // higher quality
        'image_size' => FalAiClient::SIZE_PORTRAIT_4_3,
        'num_inference_steps' => 28,
        'guidance_scale'      => 3.5,
        'negative_prompt'     => 'blurry, low quality, watermark, text',
    ]
);

$outputDir = __DIR__ . '/../output';
$path = $images[0]->save($outputDir);

echo "Saved to: {$path}\n";
