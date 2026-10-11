# fal.ai PHP SDK — AI Image Generator with FLUX.1 (Schnell / Dev / Kontext) & Stable Diffusion XL

[![Latest Version](https://img.shields.io/packagist/v/shareaichat/fal-ai-php-image-generator)](https://packagist.org/packages/shareaichat/fal-ai-php-image-generator)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-777BB4.svg)](https://www.php.net/)
[![Made with fal.ai](https://img.shields.io/badge/fal.ai-API-ff6b6b.svg)](https://fal.ai/)
[![Maintained by ShareAIChat](https://img.shields.io/badge/maintained%20by-ShareAIChat-8b5cf6.svg)](https://shareaichat.com)

**A lightweight, zero-dependency PHP library for generating AI images with the [fal.ai](https://fal.ai/) API.**
Generate stunning images from text (**text-to-image**), edit existing images with natural-language instructions (**image-to-image**), produce multiple variations in one call, and download the results locally — all with a few lines of PHP.

This package wraps the most popular fal.ai image models — **FLUX.1 Schnell, FLUX.1 Dev, FLUX.1 Kontext / Kontext Multi, and Stable Diffusion XL** — behind a single clean interface, with per-model validation (for example the 12-step limit of FLUX.1 Schnell), typed results and detailed exceptions.

> 🚀 **In production:** This SDK powers the AI creation studio at [**ShareAIChat — the free AI art community to create, remix and share AI-generated artwork**](https://shareaichat.com). See thousands of real FLUX.1 generations and remix them yourself at [shareaichat.com](https://shareaichat.com).

---

## Table of Contents

- [Why this library?](#why-this-library)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start — Generate an AI Image in 5 Lines](#quick-start--generate-an-ai-image-in-5-lines)
- [Supported Models](#supported-models)
- [Usage](#usage)
  - [Text-to-Image (FLUX.1 Schnell — fast & cheap)](#text-to-image-flux1-schnell--fast--cheap)
  - [Higher Quality with FLUX.1 Dev](#higher-quality-with-flux1-dev)
  - [Generate Multiple Images](#generate-multiple-images)
  - [Image-to-Image Editing with FLUX.1 Kontext](#image-to-image-editing-with-flux1-kontext)
  - [Batch Content Generation](#batch-content-generation)
  - [Save Images to Disk](#save-images-to-disk)
  - [Call Any fal.ai Endpoint](#call-any-falai-endpoint)
- [API Reference](#api-reference)
- [Error Handling](#error-handling)
- [Image Sizes & Prompt Tips](#image-sizes--prompt-tips)
- [Use Cases](#use-cases)
- [FAQ](#faq)
- [Contributing](#contributing)
- [License](#license)

---

## Why this library?

| | This SDK | Writing raw cURL yourself |
|---|---|---|
| **Zero dependencies** | ✅ PHP 8 + curl only | — |
| **FLUX.1 Schnell step limit handled** | ✅ Validates `num_inference_steps <= 12` | ❌ Confusing HTTP 422 |
| **Model-specific params** | ✅ `guidance_scale` only sent to models that accept it | ❌ Easily rejected |
| **Typed image results** | ✅ URL, width, height, content type | ❌ Parse JSON manually |
| **Batch & multi-image** | ✅ Built in | ❌ DIY |
| **Friendly errors** | ✅ Human-readable validation messages from fal.ai | ❌ Raw JSON `detail` |
| **Save to disk** | ✅ One method, follows redirects | ❌ DIY |

No Laravel, Symfony or framework required — it works in plain PHP, WordPress, Drupal, or any modern PHP project.

---

## Requirements

- **PHP 8.0+**
- Extensions: `curl`, `json` (both enabled in almost every PHP install)
- A [**fal.ai API key**](https://fal.ai/dashboard/keys)

---

## Installation

### Composer (recommended)

```bash
composer require shareaichat/fal-ai-php-image-generator
```

### Without Composer (standalone)

Copy the `src/` folder into your project and include the files directly:

```php
require_once __DIR__ . '/src/FalAiException.php';
require_once __DIR__ . '/src/GeneratedImage.php';
require_once __DIR__ . '/src/Models.php';
require_once __DIR__ . '/src/FalAiClient.php';
```

Set your API key as an environment variable:

```bash
export FAL_KEY="your-fal-ai-key"
```

---

## Quick Start — Generate an AI Image in 5 Lines

```php
use ShareAIChat\FalAi\FalAiClient;
use ShareAIChat\FalAi\Models;

$client = new FalAiClient(getenv('FAL_KEY'));

$images = $client->textToImage(
    'a cyberpunk samurai cat in a neon-lit Tokyo alley, cinematic lighting, ultra detailed'
);

echo $images[0]->url; // https://fal.media/files/...
```

That's it — the returned URL points to a **hosted PNG on fal.media** that you can display, download or store immediately.

---

## Supported Models

| Model | ID constant | Best for | Max images | Steps | Image-to-image |
|---|---|---|---|---|---|
| **FLUX.1 Schnell** | `Models::FLUX_SCHNELL` | Fast, cheap prototyping & bulk generation | 4 | ≤ 12 | ❌ |
| **FLUX.1 Dev** | `Models::FLUX_DEV` | High-quality text-to-image | 4 | ≤ 50 | ✅ |
| **FLUX.1 Kontext** | `Models::FLUX_KONTEXT` | Instruction-based image editing | 1 | ≤ 50 | ✅ |
| **FLUX.1 Kontext Multi** | `Models::FLUX_KONTEXT_MULTI` | Editing with multiple reference images | 1 | ≤ 50 | ✅ |
| **Stable Diffusion XL** | `Models::STABLE_DIFFUSION_XL` | Classic open-weights diffusion model | 1 | ≤ 50 | ✅ |

> **FLUX.1 Schnell** is the cheapest and fastest model and is ideal for AI art galleries, SEO content pipelines and prototypes. It produces great images in just **4 inference steps**.

---

## Usage

### Text-to-Image (FLUX.1 Schnell — fast & cheap)

```php
$images = $client->textToImage('an ancient temple hidden in a misty forest at dawn', [
    'model'      => Models::FLUX_SCHNELL,
    'image_size' => FalAiClient::SIZE_LANDSCAPE_16_9,
]);

echo $images[0]->url;
```

### Higher Quality with FLUX.1 Dev

```php
$images = $client->textToImage(
    'a magical library floating above the clouds, golden hour, fantasy concept art',
    [
        'model'                => Models::FLUX_DEV,
        'image_size'           => FalAiClient::SIZE_PORTRAIT_4_3,
        'num_inference_steps'  => 28,
        'guidance_scale'       => 3.5,
        'negative_prompt'      => 'blurry, low quality, watermark, text',
    ]
);
```

### Generate Multiple Images

Get up to 4 variations in a single request (FLUX.1 Schnell / Dev):

```php
$images = $client->textToImage('a phoenix rising from volcanic ash, epic fantasy art', [
    'model'      => Models::FLUX_SCHNELL,
    'num_images' => 4,
]);

foreach ($images as $image) {
    echo "{$image->width}x{$image->height} — {$image->url}\n";
}
```

### Image-to-Image Editing with FLUX.1 Kontext

Pass a reference image URL plus an editing instruction:

```php
$images = $client->imageToImage(
    'Turn this portrait into a cyberpunk style with neon goggles',
    'https://example.com/portrait.jpg',
    ['model' => Models::FLUX_KONTEXT]
);

echo $images[0]->url;
```

Multiple reference images with Kontext Multi:

```php
$images = $client->imageToImage(
    'Combine the style of image 1 with the composition of image 2',
    [
        'https://example.com/ref1.jpg',
        'https://example.com/ref2.jpg',
    ],
    ['model' => Models::FLUX_KONTEXT_MULTI]
);
```

### Batch Content Generation

Great for generating dozens of unique AI artworks for a gallery, blog or marketplace:

```php
$prompts = [
    ['cyberpunk', 'a lone samurai walking through a rainy neon street'],
    ['fantasy',    'a dragon soaring over snowy mountain peaks at dawn'],
    ['anime',      'a cozy cafe interior with cherry blossoms in spring'],
];

foreach ($prompts as [$style, $scene]) {
    try {
        $images = $client->textToImage("$style: $scene, ultra detailed, 8k");
        echo "$style -> {$images[0]->url}\n";
    } catch (\ShareAIChat\FalAi\FalAiException $e) {
        error_log("Generation failed: {$e->getMessage()}");
    }
}
```

See the full example: [`examples/batch-generate.php`](examples/batch-generate.php).

### Save Images to Disk

```php
$images = $client->textToImage('a bioluminescent jellyfish in deep space');

$path = $images[0]->save(__DIR__ . '/output');
echo "Saved to: {$path}\n";
```

### Call Any fal.ai Endpoint

Need an endpoint that isn't in the catalog? Use the escape hatch:

```php
$data = $client->request('fal-ai/your-model-of-choice', [
    'prompt' => 'your prompt',
    // ...raw fal.ai parameters
]);
```

Or parse a custom image-model response into `GeneratedImage` objects with `generate($endpoint, $payload)`.

---

## API Reference

### `new FalAiClient(string $apiKey, array $options = [])`

| Option | Default | Description |
|---|---|---|
| `base_url` | `https://fal.run` | fal.ai API base URL |
| `timeout` | `180` | Request timeout (seconds) |
| `connect_timeout` | `10` | Connection timeout (seconds) |

### `textToImage(string $prompt, array $options = []): GeneratedImage[]`

| Option | Type | Default | Description |
|---|---|---|---|
| `model` | string | `FLUX_SCHNELL` | Model ID from `Models` |
| `image_size` | string | `square_hd` | One of the `SIZE_*` constants |
| `num_images` | int | `1` | Number of images (model-dependent max) |
| `num_inference_steps` | int | model default | Diffusion steps (validated per model) |
| `guidance_scale` | float | — | Prompt adherence (ignored by Schnell) |
| `negative_prompt` | string | — | What to avoid |
| `safety_checker` | bool | `false` | Enable the fal.ai safety checker |
| `extra` | array | `[]` | Any additional raw fal.ai parameters |

### `imageToImage(string $prompt, string|array $referenceUrls, array $options = []): GeneratedImage[]`

Same options as `textToImage()`; the model must support image-to-image.

### `GeneratedImage`

| Property / Method | Description |
|---|---|
| `$image->url` | Hosted image URL on `fal.media` |
| `$image->width` / `$image->height` | Output dimensions in px |
| `$image->contentType` | e.g. `image/jpeg` |
| `$image->save($dir, $filename = null)` | Download the image, returns the local path |

---

## Error Handling

All failures throw a `FalAiException` with the HTTP status and parsed fal.ai validation details:

```php
use ShareAIChat\FalAi\FalAiException;

try {
    $images = $client->textToImage($prompt);
} catch (FalAiException $e) {
    echo $e->getMessage();          // e.g. "body.num_inference_steps: Input should be less than or equal to 12"
    echo $e->getHttpStatus();       // e.g. 422
    print_r($e->getResponseBody()); // raw decoded response for logging
}
```

Common cases handled for you:

- **HTTP 422 validation errors** — fal.ai's nested `detail` array is flattened into a readable message
- **Empty responses** — `"No image returned by FLUX.1 Schnell"` instead of cryptic null access
- **Network errors** — cURL errors with the underlying message
- **Per-model parameter validation** — e.g. 4 images max, 12 steps max for Schnell

---

## Image Sizes & Prompt Tips

**Sizes:** `SIZE_SQUARE_HD`, `SIZE_SQUARE`, `SIZE_PORTRAIT_4_3`, `SIZE_PORTRAIT_16_9`, `SIZE_LANDSCAPE_4_3`, `SIZE_LANDSCAPE_16_9`

**Prompt formula that works well with FLUX.1:**

```
[style]: [subject] [action/scene], [environment/lighting], [quality modifiers]

cyberpunk: a lone samurai walking through midnight neon rain, ultra detailed, 8k, cinematic lighting
```

- Put the **style first** (`cyberpunk:`, `anime:`, `watercolor:`)
- Describe **lighting** (`golden hour`, `neon glow`, `soft candlelight`)
- Add **quality modifiers** (`ultra detailed`, `cinematic`, `8k`)
- Use **negative prompts** to exclude artifacts (FLUX Dev / SDXL)

Looking for inspiration? Browse thousands of community prompts and remix them free at [**ShareAIChat**](https://shareaichat.com) — the AI art sharing community built on this library.

---

## Use Cases

- 🖼️ **AI art generators & SaaS tools** — add FLUX.1 image generation to any PHP backend
- 🛍️ **E-commerce product mockups** — generate lifestyle and concept product images in bulk
- 📝 **Content & SEO pipelines** — auto-create unique featured images for blog posts
- 🎮 **Game & app assets** — rapid concept art, character variations and environment sketches
- 🎨 **AI art communities & galleries** — user-driven generation with multiple variations (like [shareaichat.com](https://shareaichat.com))
- 🖨️ **Print-on-demand** — produce wall art, poster and t-shirt designs at scale
- 🤖 **Chatbots & assistants** — let a PHP bot answer with generated images

---

## FAQ

**Is this library free?**
Yes. It is released under the MIT license. You only pay [fal.ai](https://fal.ai/pricing) for the images you generate. FLUX.1 Schnell is the cheapest option and needs just 4 inference steps.

**Do I need a fal.ai account?**
Yes — create a free account and grab an API key at [fal.ai/dashboard/keys](https://fal.ai/dashboard/keys). The key is only used server-side; never expose it in browser code.

**What is the difference between FLUX.1 Schnell and FLUX.1 Dev?**
Schnell is optimized for speed and low cost (4–12 steps, seconds per image) and is perfect for prototyping and bulk generation. Dev offers higher fidelity and finer prompt adherence with up to 50 steps.

**Can I use this in Laravel / WordPress / plain PHP?**
Yes. It is a framework-agnostic Composer package with zero dependencies beyond `curl` and `json`, and it also works without Composer.

**Where are the generated images stored?**
fal.ai returns URLs hosted on its `fal.media` CDN. Display them directly or download with `$image->save()` and upload to your own storage / S3 / OSS.

**Can I generate videos too?**
This package focuses on image generation (text-to-image and image-to-image). The underlying `request()` escape hatch can be pointed at any fal.ai endpoint if you want to extend it yourself.

**Where can I see AI art built with this SDK?**
Visit [**shareaichat.com**](https://shareaichat.com) — a free community to create, remix and share AI-generated artwork powered by this exact library.

---

## Contributing

Contributions are welcome — issues, pull requests and new model catalog entries.
When adding a model, please include its endpoint, image limit, step limit and whether it accepts `guidance_scale` and `negative_prompt`.

---

## License

[MIT License](LICENSE) © 2026 [ShareAIChat](https://shareaichat.com)

**Create freely. Share imagination. Find inspiration — [shareaichat.com](https://shareaichat.com).**
