<?php

/**
 * Example 2 — Batch generation: iterate over many prompts / styles
 * and keep producing unique AI artwork. Useful for content pipelines
 * and SEO-oriented art galleries.
 *
 * Run:
 *   FAL_KEY=your_key php examples/batch-generate.php
 */

declare(strict_types=1);

use ShareAIChat\FalAi\FalAiClient;
use ShareAIChat\FalAi\FalAiException;
use ShareAIChat\FalAi\Models;

require __DIR__ . '/bootstrap.php';

$client = new FalAiClient(fal_demo_api_key());

$prompts = [
    ['cyberpunk', 'a lone samurai walking through a rainy neon street'],
    ['fantasy',    'a dragon soaring over snowy mountain peaks at dawn'],
    ['anime',      'a cozy cafe interior in spring with cherry blossoms'],
    ['steampunk',  'an airship floating above a brass clockwork city'],
    ['realistic',  'portrait of an astronaut under the aurora borealis'],
];

$results = [];

foreach ($prompts as $i => [$style, $scene]) {
    $prompt = sprintf('%s: %s, ultra detailed, 8k, cinematic', $style, $scene);

    try {
        echo sprintf("[%d/%d] %s\n", $i + 1, count($prompts), $style);
        $images = $client->textToImage($prompt, [
            'model'      => Models::FLUX_SCHNELL,
            'image_size' => FalAiClient::SIZE_SQUARE_HD,
        ]);
        $results[$style] = $images[0]->url;
        echo "  -> {$images[0]->url}\n";
    } catch (FalAiException $e) {
        // A single failure must not kill the whole batch
        fwrite(STDERR, "  !! generation failed: {$e->getMessage()}\n");
    }
}

echo "\nGenerated " . count($results) . " images:\n";
print_r($results);
