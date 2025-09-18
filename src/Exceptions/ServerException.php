<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Exceptions;

/**
 * Server errors (HTTP 500+)
 *
 * Thrown when the API server encounters an internal error
 *
 * @package ApplaxDev\GateSDK\Exceptions
 */
class ServerException extends GateException
{
    /**
     * Check if this is an internal server error (500)
     *
     * @return bool
     */
    public function isInternalServerError(): bool
    {
        return $this->getCode() === 500;
    }

    /**
     * Check if this is a bad gateway error (502)
     *
     * @return bool
     */
    public function isBadGateway(): bool
    {
        return $this->getCode() === 502;
    }

    /**
     * Check if this is a service unavailable error (503)
     *
     * @return bool
     */
    public function isServiceUnavailable(): bool
    {
        return $this->getCode() === 503;
    }

    /**
     * Check if this is a gateway timeout error (504)
     *
     * @return bool
     */
    public function isGatewayTimeout(): bool
    {
        return $this->getCode() === 504;
    }

    /**
     * Get server error type
     *
     * @return string
     */
    public function getServerErrorType(): string
    {
        switch ($this->getCode()) {
            case 500:
                return 'internal_server_error';
            case 502:
                return 'bad_gateway';
            case 503:
                return 'service_unavailable';
            case 504:
                return 'gateway_timeout';
            default:
                return 'unknown_server_error';
        }
    }

    /**
     * Check if the error is retryable
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        // Generally, 500, 502, 503, and 504 errors are retryable
        return in_array($this->getCode(), [500, 502, 503, 504]);
    }

    /**
     * Get recommended retry delay in seconds
     *
     * @return int
     */
    public function getRecommendedRetryDelay(): int
    {
        switch ($this->getCode()) {
            case 503: // Service unavailable - longer delay
                return 60;
            case 504: // Gateway timeout - moderate delay
                return 30;
            case 500:
            case 502:
            default:
                return 15;
        }
    }

    /**
     * Get user-friendly error description
     *
     * @return string
     */
    public function getUserFriendlyDescription(): string
    {
        switch ($this->getCode()) {
            case 500:
                return 'The server encountered an internal error. Please try again later.';
            case 502:
                return 'The server received an invalid response from upstream. Please try again.';
            case 503:
                return 'The service is temporarily unavailable. Please try again later.';
            case 504:
                return 'The server request timed out. Please try again.';
            default:
                return 'A server error occurred. Please try again later.';
        }
    }
}