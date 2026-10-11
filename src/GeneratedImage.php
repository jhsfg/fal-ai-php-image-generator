<?php

declare(strict_types=1);

namespace ShareAIChat\FalAi;

/**
 * A single generated image returned by fal.ai.
 */
final class GeneratedImage
{
    public function __construct(
        public readonly string $url,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?string $contentType = null,
    ) {
    }

    /**
     * Download the generated image to a local file.
     *
     * @return string The saved file path
     * @throws FalAiException
     */
    public function save(string $directory, ?string $filename = null): string
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new FalAiException("Cannot create output directory: {$directory}");
        }

        $filename ??= 'fal-' . bin2hex(random_bytes(8)) . '.png';
        $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        $ch = curl_init($this->url);
        $fp = fopen($path, 'wb');
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_FAILONERROR    => true,
        ]);
        $ok = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok) {
            @unlink($path);
            throw new FalAiException('Failed to download image: ' . $err);
        }

        return $path;
    }
}
