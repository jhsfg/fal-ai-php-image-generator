<?php

declare(strict_types=1);

namespace ShareAIChat\FalAi;

/**
 * Exception thrown when the fal.ai API call fails
 * (network error, non-2xx HTTP status, validation error, empty result).
 */
class FalAiException extends \RuntimeException
{
    private int $httpStatus;
    private ?array $responseBody;

    public function __construct(string $message, int $httpStatus = 0, ?array $responseBody = null)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
