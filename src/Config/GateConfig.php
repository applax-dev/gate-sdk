<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Config;

use InvalidArgumentException;

/**
 * Configuration class for Appla-X Gate SDK
 *
 * Handles all configuration options with validation and defaults
 *
 * @package ApplaxDev\GateSDK\Config
 */
class GateConfig
{
    private string $apiKey;
    private bool $sandbox;
    private int $timeout;
    private int $connectTimeout;
    private int $maxRetries;
    private bool $debug;
    private string $userAgent;
    private array $allowedCurrencies;
    private array $allowedLanguages;

    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_CONNECT_TIMEOUT = 10;
    private const DEFAULT_MAX_RETRIES = 3;
    private const DEFAULT_USER_AGENT = 'ApplaX-Gate-SDK-PHP/1.0.0';

    private const ALLOWED_CURRENCIES = [
        'EUR', 'USD', 'GBP', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'RON', 'BGN', 'HRK'
    ];

    private const ALLOWED_LANGUAGES = [
        'en', 'lv', 'lt', 'ee', 'ru', 'de', 'fr', 'es', 'it', 'pt', 'pl', 'cs', 'sk', 'hu', 'ro', 'bg', 'hr'
    ];

    /**
     * Create configuration instance
     *
     * @param string $apiKey Bearer token from Appla-X dashboard
     * @param array $options Configuration options
     */
    public function __construct(string $apiKey, array $options = [])
    {
        $this->validateApiKey($apiKey);
        $this->apiKey = $apiKey;

        $this->sandbox = $options['sandbox'] ?? true;
        $this->timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;
        $this->connectTimeout = $options['connect_timeout'] ?? self::DEFAULT_CONNECT_TIMEOUT;
        $this->maxRetries = $options['max_retries'] ?? self::DEFAULT_MAX_RETRIES;
        $this->debug = $options['debug'] ?? false;
        $this->userAgent = $options['user_agent'] ?? self::DEFAULT_USER_AGENT;
        $this->allowedCurrencies = $options['allowed_currencies'] ?? self::ALLOWED_CURRENCIES;
        $this->allowedLanguages = $options['allowed_languages'] ?? self::ALLOWED_LANGUAGES;

        $this->validateConfiguration();
    }

    /**
     * Create configuration from environment variables
     *
     * @return self
     */
    public static function fromEnvironment(): self
    {
        $apiKey = $_ENV['APPLAX_API_KEY'] ?? $_SERVER['APPLAX_API_KEY'] ?? '';
        $sandbox = filter_var(
            $_ENV['APPLAX_SANDBOX'] ?? $_SERVER['APPLAX_SANDBOX'] ?? 'true',
            FILTER_VALIDATE_BOOLEAN
        );

        if (empty($apiKey)) {
            throw new InvalidArgumentException('APPLAX_API_KEY environment variable is required');
        }

        return new self($apiKey, [
            'sandbox' => $sandbox,
            'timeout' => (int) ($_ENV['APPLAX_TIMEOUT'] ?? $_SERVER['APPLAX_TIMEOUT'] ?? self::DEFAULT_TIMEOUT),
            'debug' => filter_var(
                $_ENV['APPLAX_DEBUG'] ?? $_SERVER['APPLAX_DEBUG'] ?? 'false',
                FILTER_VALIDATE_BOOLEAN
            ),
        ]);
    }

    /**
     * Get API key
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Check if sandbox mode is enabled
     *
     * @return bool
     */
    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Get request timeout
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get connection timeout
     *
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    /**
     * Get maximum retry attempts
     *
     * @return int
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Get user agent string
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Get allowed currencies
     *
     * @return array
     */
    public function getAllowedCurrencies(): array
    {
        return $this->allowedCurrencies;
    }

    /**
     * Get allowed languages
     *
     * @return array
     */
    public function getAllowedLanguages(): array
    {
        return $this->allowedLanguages;
    }

    /**
     * Check if currency is supported
     *
     * @param string $currency
     * @return bool
     */
    public function isCurrencySupported(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->allowedCurrencies);
    }

    /**
     * Check if language is supported
     *
     * @param string $language
     * @return bool
     */
    public function isLanguageSupported(string $language): bool
    {
        return in_array(strtolower($language), $this->allowedLanguages);
    }

    /**
     * Get configuration as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'sandbox' => $this->sandbox,
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'max_retries' => $this->maxRetries,
            'debug' => $this->debug,
            'user_agent' => $this->userAgent,
            'allowed_currencies' => $this->allowedCurrencies,
            'allowed_languages' => $this->allowedLanguages,
        ];
    }

    /**
     * Validate API key format
     *
     * @param string $apiKey
     * @throws InvalidArgumentException
     */
    private function validateApiKey(string $apiKey): void
    {
        if (empty($apiKey)) {
            throw new InvalidArgumentException('API key cannot be empty');
        }

        if (strlen($apiKey) < 32) {
            throw new InvalidArgumentException('API key appears to be invalid (too short)');
        }

        if (!ctype_alnum($apiKey)) {
            throw new InvalidArgumentException('API key contains invalid characters');
        }
    }

    /**
     * Validate configuration values
     *
     * @throws InvalidArgumentException
     */
    private function validateConfiguration(): void
    {
        if ($this->timeout < 1 || $this->timeout > 300) {
            throw new InvalidArgumentException('Timeout must be between 1 and 300 seconds');
        }

        if ($this->connectTimeout < 1 || $this->connectTimeout > 60) {
            throw new InvalidArgumentException('Connect timeout must be between 1 and 60 seconds');
        }

        if ($this->maxRetries < 0 || $this->maxRetries > 10) {
            throw new InvalidArgumentException('Max retries must be between 0 and 10');
        }

        if (empty($this->userAgent)) {
            throw new InvalidArgumentException('User agent cannot be empty');
        }
    }
}