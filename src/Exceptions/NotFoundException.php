<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Exceptions;

/**
 * Resource not found errors (HTTP 404)
 *
 * Thrown when the requested resource does not exist
 *
 * @package ApplaxDev\GateSDK\Exceptions
 */
class NotFoundException extends GateException
{
    private ?string $resourceType = null;
    private ?string $resourceId = null;

    /**
     * Set the resource type that was not found
     *
     * @param string $resourceType
     * @return self
     */
    public function setResourceType(string $resourceType): self
    {
        $this->resourceType = $resourceType;
        return $this;
    }

    /**
     * Set the resource ID that was not found
     *
     * @param string $resourceId
     * @return self
     */
    public function setResourceId(string $resourceId): self
    {
        $this->resourceId = $resourceId;
        return $this;
    }

    /**
     * Get the resource type that was not found
     *
     * @return string|null
     */
    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    /**
     * Get the resource ID that was not found
     *
     * @return string|null
     */
    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    /**
     * Create a not found exception for a specific resource
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param string|null $customMessage
     * @return self
     */
    public static function forResource(string $resourceType, string $resourceId, ?string $customMessage = null): self
    {
        $message = $customMessage ?? "The {$resourceType} with ID '{$resourceId}' was not found.";

        $exception = new self($message, 404);
        $exception->setResourceType($resourceType);
        $exception->setResourceId($resourceId);

        return $exception;
    }

    /**
     * Get formatted error message with resource details
     *
     * @return string
     */
    public function getFormattedMessage(): string
    {
        if ($this->resourceType && $this->resourceId) {
            return "Resource not found: {$this->resourceType} '{$this->resourceId}' does not exist.";
        }

        return $this->getMessage();
    }
}