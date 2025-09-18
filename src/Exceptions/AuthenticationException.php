<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Exceptions;

/**
 * Authentication and authorization errors (HTTP 401, 403)
 *
 * Thrown when API credentials are invalid or insufficient permissions
 *
 * @package ApplaxDev\GateSDK\Exceptions
 */
class AuthenticationException extends GateException
{
    /**
     * Check if this is an invalid credentials error
     *
     * @return bool
     */
    public function isInvalidCredentials(): bool
    {
        return $this->getCode() === 401;
    }

    /**
     * Check if this is an insufficient permissions error
     *
     * @return bool
     */
    public function isInsufficientPermissions(): bool
    {
        return $this->getCode() === 403;
    }

    /**
     * Get authentication error type
     *
     * @return string
     */
    public function getAuthErrorType(): string
    {
        if ($this->isInvalidCredentials()) {
            return 'invalid_credentials';
        }

        if ($this->isInsufficientPermissions()) {
            return 'insufficient_permissions';
        }

        return 'unknown_auth_error';
    }

    /**
     * Get recommended action for the authentication error
     *
     * @return string
     */
    public function getRecommendedAction(): string
    {
        if ($this->isInvalidCredentials()) {
            return 'Check your API key and ensure it is valid and not expired.';
        }

        if ($this->isInsufficientPermissions()) {
            return 'Contact your administrator to ensure your API key has the required permissions.';
        }

        return 'Verify your authentication configuration.';
    }
}