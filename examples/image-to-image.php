<?php

/**
 * Example 4 — Image-to-image editing with FLUX.1 Kontext.
 * Give the model a reference image and an editing instruction.
 *
 * Run:
 *   FAL_KEY=your_key php examples/image-to-image.php
 */

declare(strict_types=1);

use ShareAIChat\FalAi\FalAiClient;
use ShareAIChat\FalAi\Models;

require __DIR__ . '/bootstrap.php';

$client = new FalAiClient(fal_demo_api_key());

$referenceImageUrl = 'https://images.unsplash.com/photo-1543852786-1cf6624b9987?w=1024';

$images = $client->imageToImage(
    'Turn this cat into a cyberpunk version wearing neon goggles',
    $referenceImageUrl,
    [
        'model' => Models::FLUX_KONTEXT,
    ]
);

echo "Edited image: {$images[0]->url}\n";
