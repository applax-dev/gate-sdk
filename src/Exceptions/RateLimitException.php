<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Exceptions;

/**
 * Rate limiting errors (HTTP 429)
 *
 * Thrown when API rate limits are exceeded
 *
 * @package ApplaxDev\GateSDK\Exceptions
 */
class RateLimitException extends GateException
{
    private ?int $retryAfter = null;
    private ?int $rateLimit = null;
    private ?int $rateLimitRemaining = null;
    private ?int $rateLimitReset = null;

    /**
     * Set retry after seconds from response headers
     *
     * @param int $retryAfter
     * @return self
     */
    public function setRetryAfter(int $retryAfter): self
    {
        $this->retryAfter = $retryAfter;
        return $this;
    }

    /**
     * Set rate limit information from response headers
     *
     * @param int|null $rateLimit Total rate limit
     * @param int|null $rateLimitRemaining Remaining requests
     * @param int|null $rateLimitReset Unix timestamp when limit resets
     * @return self
     */
    public function setRateLimitInfo(?int $rateLimit = null, ?int $rateLimitRemaining = null, ?int $rateLimitReset = null): self
    {
        $this->rateLimit = $rateLimit;
        $this->rateLimitRemaining = $rateLimitRemaining;
        $this->rateLimitReset = $rateLimitReset;
        return $this;
    }

    /**
     * Get retry after seconds
     *
     * @return int|null
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Get total rate limit
     *
     * @return int|null
     */
    public function getRateLimit(): ?int
    {
        return $this->rateLimit;
    }

    /**
     * Get remaining requests in current window
     *
     * @return int|null
     */
    public function getRateLimitRemaining(): ?int
    {
        return $this->rateLimitRemaining;
    }

    /**
     * Get Unix timestamp when rate limit resets
     *
     * @return int|null
     */
    public function getRateLimitReset(): ?int
    {
        return $this->rateLimitReset;
    }

    /**
     * Get suggested wait time in seconds before retry
     *
     * @return int
     */
    public function getSuggestedWaitTime(): int
    {
        if ($this->retryAfter) {
            return $this->retryAfter;
        }

        if ($this->rateLimitReset) {
            $waitTime = $this->rateLimitReset - time();
            return max(1, $waitTime);
        }

        // Default fallback
        return 60;
    }

    /**
     * Check if rate limit information is available
     *
     * @return bool
     */
    public function hasRateLimitInfo(): bool
    {
        return $this->rateLimit !== null || $this->rateLimitRemaining !== null || $this->rateLimitReset !== null;
    }

    /**
     * Get formatted rate limit information
     *
     * @return string
     */
    public function getRateLimitSummary(): string
    {
        $parts = [];

        if ($this->rateLimit !== null && $this->rateLimitRemaining !== null) {
            $used = $this->rateLimit - $this->rateLimitRemaining;
            $parts[] = "Used {$used}/{$this->rateLimit} requests";
        }

        if ($this->rateLimitReset !== null) {
            $resetTime = date('Y-m-d H:i:s', $this->rateLimitReset);
            $parts[] = "Resets at {$resetTime}";
        }

        if ($this->retryAfter !== null) {
            $parts[] = "Retry after {$this->retryAfter} seconds";
        }

        return empty($parts) ? 'Rate limit exceeded' : implode(', ', $parts);
    }
}