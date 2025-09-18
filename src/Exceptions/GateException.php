<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception for Appla-X Gate API errors
 *
 * @package ApplaxDev\GateSDK\Exceptions
 */
class GateException extends RuntimeException
{
    protected ?array $responseData;

    /**
     * Create a new Gate exception
     *
     * @param string $message Error message
     * @param int $code HTTP status code or error code
     * @param Throwable|null $previous Previous exception
     * @param array|null $responseData Response data from API
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, ?array $responseData = null)
    {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
    }

    /**
     * Get response data from API
     *
     * @return array|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * Check if the exception has response data
     *
     * @return bool
     */
    public function hasResponseData(): bool
    {
        return $this->responseData !== null;
    }

    /**
     * Get error details from response data
     *
     * @return array
     */
    public function getErrorDetails(): array
    {
        if (!$this->hasResponseData()) {
            return [];
        }

        return [
            'message' => $this->responseData['message'] ?? $this->getMessage(),
            'code' => $this->responseData['code'] ?? $this->getCode(),
            'details' => $this->responseData['details'] ?? null,
            'errors' => $this->responseData['errors'] ?? null,
        ];
    }

    /**
     * Convert exception to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'exception' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'response_data' => $this->responseData,
            'error_details' => $this->getErrorDetails(),
        ];
    }
}