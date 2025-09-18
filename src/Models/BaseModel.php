<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Models;

use DateTime;
use InvalidArgumentException;

/**
 * Base model class with common functionality
 *
 * @package ApplaxDev\GateSDK\Models
 */
abstract class BaseModel
{
    protected array $data;

    /**
     * Create a new model instance
     *
     * @param array $data Raw data from API response
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->validate();
    }

    /**
     * Get raw data array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get JSON representation
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->data, JSON_PRETTY_PRINT);
    }

    /**
     * Get value from data array
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Parse datetime string to DateTime object
     *
     * @param string|null $dateString
     * @return DateTime|null
     */
    protected function parseDateTime(?string $dateString): ?DateTime
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return new DateTime($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if a key exists in the data
     *
     * @param string $key
     * @return bool
     */
    protected function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get nested value from data array using dot notation
     *
     * @param string $key Dot-notated key (e.g., 'client.email')
     * @param mixed $default
     * @return mixed
     */
    protected function getNested(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->data;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Validate model data - to be implemented by child classes
     */
    abstract protected function validate(): void;
}