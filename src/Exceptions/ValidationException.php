<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Exceptions;

/**
 * Input validation errors (HTTP 400)
 *
 * Thrown when the API request contains invalid or missing data
 *
 * @package ApplaxDev\GateSDK\Exceptions
 */
class ValidationException extends GateException
{
    /**
     * Get validation errors from response data
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        if (!$this->hasResponseData()) {
            return [];
        }

        $responseData = $this->getResponseData();

        // Handle different validation error formats
        if (isset($responseData['errors']) && is_array($responseData['errors'])) {
            return $responseData['errors'];
        }

        if (isset($responseData['field_errors']) && is_array($responseData['field_errors'])) {
            return $responseData['field_errors'];
        }

        if (isset($responseData['validation_errors']) && is_array($responseData['validation_errors'])) {
            return $responseData['validation_errors'];
        }

        return [];
    }

    /**
     * Get field-specific validation errors
     *
     * @param string $fieldName
     * @return array
     */
    public function getFieldErrors(string $fieldName): array
    {
        $validationErrors = $this->getValidationErrors();

        return $validationErrors[$fieldName] ?? [];
    }

    /**
     * Check if a specific field has validation errors
     *
     * @param string $fieldName
     * @return bool
     */
    public function hasFieldErrors(string $fieldName): bool
    {
        return !empty($this->getFieldErrors($fieldName));
    }

    /**
     * Get all field names that have validation errors
     *
     * @return array
     */
    public function getInvalidFields(): array
    {
        return array_keys($this->getValidationErrors());
    }
}