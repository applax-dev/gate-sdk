<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Exceptions;

/**
 * Network and connectivity errors
 *
 * Thrown when network connectivity issues prevent API communication
 *
 * @package ApplaxDev\GateSDK\Exceptions
 */
class NetworkException extends GateException
{
    private ?string $networkErrorType = null;

    /**
     * Set the network error type
     *
     * @param string $networkErrorType
     * @return self
     */
    public function setNetworkErrorType(string $networkErrorType): self
    {
        $this->networkErrorType = $networkErrorType;
        return $this;
    }

    /**
     * Get the network error type
     *
     * @return string|null
     */
    public function getNetworkErrorType(): ?string
    {
        return $this->networkErrorType;
    }

    /**
     * Create a timeout exception
     *
     * @param string $message
     * @param int $timeout
     * @return self
     */
    public static function timeout(string $message = 'Request timeout', int $timeout = 0): self
    {
        $fullMessage = $timeout > 0
            ? "{$message} (timeout: {$timeout}s)"
            : $message;

        $exception = new self($fullMessage, 0);
        $exception->setNetworkErrorType('timeout');

        return $exception;
    }

    /**
     * Create a connection failed exception
     *
     * @param string $message
     * @param string $host
     * @return self
     */
    public static function connectionFailed(string $message = 'Connection failed', string $host = ''): self
    {
        $fullMessage = !empty($host)
            ? "{$message} (host: {$host})"
            : $message;

        $exception = new self($fullMessage, 0);
        $exception->setNetworkErrorType('connection_failed');

        return $exception;
    }

    /**
     * Create a DNS resolution exception
     *
     * @param string $message
     * @param string $host
     * @return self
     */
    public static function dnsResolutionFailed(string $message = 'DNS resolution failed', string $host = ''): self
    {
        $fullMessage = !empty($host)
            ? "{$message} (host: {$host})"
            : $message;

        $exception = new self($fullMessage, 0);
        $exception->setNetworkErrorType('dns_resolution_failed');

        return $exception;
    }

    /**
     * Create an SSL/TLS exception
     *
     * @param string $message
     * @return self
     */
    public static function sslError(string $message = 'SSL/TLS error'): self
    {
        $exception = new self($message, 0);
        $exception->setNetworkErrorType('ssl_error');

        return $exception;
    }

    /**
     * Check if this is a timeout error
     *
     * @return bool
     */
    public function isTimeout(): bool
    {
        return $this->networkErrorType === 'timeout';
    }

    /**
     * Check if this is a connection failed error
     *
     * @return bool
     */
    public function isConnectionFailed(): bool
    {
        return $this->networkErrorType === 'connection_failed';
    }

    /**
     * Check if this is a DNS resolution error
     *
     * @return bool
     */
    public function isDnsResolutionFailed(): bool
    {
        return $this->networkErrorType === 'dns_resolution_failed';
    }

    /**
     * Check if this is an SSL/TLS error
     *
     * @return bool
     */
    public function isSslError(): bool
    {
        return $this->networkErrorType === 'ssl_error';
    }

    /**
     * Check if the error is retryable
     *
     * @return bool
     */
    public function isRetryable(): bool
    {
        // Most network errors are retryable except SSL errors
        return !$this->isSslError();
    }

    /**
     * Get recommended retry delay in seconds
     *
     * @return int
     */
    public function getRecommendedRetryDelay(): int
    {
        switch ($this->networkErrorType) {
            case 'timeout':
                return 5;
            case 'connection_failed':
                return 10;
            case 'dns_resolution_failed':
                return 30;
            case 'ssl_error':
                return 0; // Not retryable
            default:
                return 5;
        }
    }

    /**
     * Get user-friendly error description
     *
     * @return string
     */
    public function getUserFriendlyDescription(): string
    {
        switch ($this->networkErrorType) {
            case 'timeout':
                return 'The request timed out. Please check your internet connection and try again.';
            case 'connection_failed':
                return 'Unable to connect to the server. Please check your internet connection.';
            case 'dns_resolution_failed':
                return 'Unable to resolve the server address. Please check your DNS settings.';
            case 'ssl_error':
                return 'SSL/TLS connection error. Please check your security settings.';
            default:
                return 'A network error occurred. Please check your internet connection and try again.';
        }
    }
}