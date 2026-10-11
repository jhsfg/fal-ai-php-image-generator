<?php

/**
 * Bootstrap: works both with Composer autoload and as a standalone include.
 */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    require_once __DIR__ . '/../src/FalAiException.php';
    require_once __DIR__ . '/../src/GeneratedImage.php';
    require_once __DIR__ . '/../src/Models.php';
    require_once __DIR__ . '/../src/FalAiClient.php';
}

/**
 * Read the fal.ai API key from the FAL_KEY environment variable
 * or a local .env file (KEY=VALUE lines).
 */
function fal_demo_api_key(): string
{
    $key = getenv('FAL_KEY') ?: '';
    if ($key !== '') return $key;

    $envFile = __DIR__ . '/../.env';
    if (is_file($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (preg_match('/^\s*FAL_KEY\s*=\s*(.+?)\s*$/', $line, $m)) {
                return trim($m[1], "\"'");
            }
        }
    }

    fwrite(STDERR, "Set FAL_KEY environment variable or create a .env file (see .env.example)\n");
    exit(1);
}
