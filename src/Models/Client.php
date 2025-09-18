<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Models;

use DateTime;
use InvalidArgumentException;

/**
 * Client model
 *
 * @package ApplaxDev\GateSDK\Models
 */
class Client extends BaseModel
{
    /**
     * Get client ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->get('id');
    }

    /**
     * Get client type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->get('type', 'client');
    }

    /**
     * Get client email
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->get('email');
    }

    /**
     * Get client phone
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->get('phone');
    }

    /**
     * Get client first name
     *
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->get('first_name');
    }

    /**
     * Get client last name
     *
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->get('last_name');
    }

    /**
     * Get client full name
     *
     * @return string
     */
    public function getFullName(): string
    {
        $firstName = $this->getFirstName();
        $lastName = $this->getLastName();

        if ($firstName && $lastName) {
            return trim($firstName . ' ' . $lastName);
        }

        return $firstName ?: $lastName ?: '';
    }

    /**
     * Get client birth date
     *
     * @return DateTime|null
     */
    public function getBirthDate(): ?DateTime
    {
        return $this->parseDateTime($this->get('birth_date'));
    }

    /**
     * Get client personal code
     *
     * @return string|null
     */
    public function getPersonalCode(): ?string
    {
        return $this->get('personal_code');
    }

    /**
     * Get client brand name
     *
     * @return string|null
     */
    public function getBrandName(): ?string
    {
        return $this->get('brand_name');
    }

    /**
     * Get client legal name
     *
     * @return string|null
     */
    public function getLegalName(): ?string
    {
        return $this->get('legal_name');
    }

    /**
     * Get client registration number
     *
     * @return string|null
     */
    public function getRegistrationNr(): ?string
    {
        return $this->get('registration_nr');
    }

    /**
     * Get client VAT payer number
     *
     * @return string|null
     */
    public function getVatPayerNr(): ?string
    {
        return $this->get('vat_payer_nr');
    }

    /**
     * Get client legal address
     *
     * @return string|null
     */
    public function getLegalAddress(): ?string
    {
        return $this->get('legal_address');
    }

    /**
     * Get client bank account
     *
     * @return string|null
     */
    public function getBankAccount(): ?string
    {
        return $this->get('bank_account');
    }

    /**
     * Get client bank code
     *
     * @return string|null
     */
    public function getBankCode(): ?string
    {
        return $this->get('bank_code');
    }

    /**
     * Get shipping details
     *
     * @return array|null
     */
    public function getShippingDetails(): ?array
    {
        return $this->get('shipping_details');
    }

    /**
     * Check if client should receive email notifications
     *
     * @return bool
     */
    public function shouldSendToEmail(): bool
    {
        return (bool) $this->get('send_to_email', false);
    }

    /**
     * Check if client should receive SMS notifications
     *
     * @return bool
     */
    public function shouldSendToPhone(): bool
    {
        return (bool) $this->get('send_to_phone', false);
    }

    /**
     * Check if client is a business
     *
     * @return bool
     */
    public function isBusiness(): bool
    {
        return !empty($this->getLegalName()) || !empty($this->getRegistrationNr());
    }

    /**
     * Check if client is an individual
     *
     * @return bool
     */
    public function isIndividual(): bool
    {
        return !$this->isBusiness();
    }

    /**
     * Check if client has complete personal information
     *
     * @return bool
     */
    public function hasCompletePersonalInfo(): bool
    {
        return !empty($this->getFirstName())
            && !empty($this->getLastName())
            && !empty($this->getEmail());
    }

    /**
     * Check if client has complete business information
     *
     * @return bool
     */
    public function hasCompleteBusinessInfo(): bool
    {
        return !empty($this->getLegalName())
            && !empty($this->getRegistrationNr())
            && !empty($this->getLegalAddress());
    }

    /**
     * Get client age (if birth date available)
     *
     * @return int|null
     */
    public function getAge(): ?int
    {
        $birthDate = $this->getBirthDate();
        if (!$birthDate) {
            return null;
        }

        $now = new DateTime();
        return $now->diff($birthDate)->y;
    }

    /**
     * Get display name (business or personal)
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        if ($this->isBusiness() && $this->getBrandName()) {
            return $this->getBrandName();
        }

        if ($this->isBusiness() && $this->getLegalName()) {
            return $this->getLegalName();
        }

        $fullName = $this->getFullName();
        if ($fullName) {
            return $fullName;
        }

        return $this->getEmail() ?: $this->getPhone() ?: 'Unknown Client';
    }

    /**
     * Get client summary
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->getId(),
            'display_name' => $this->getDisplayName(),
            'email' => $this->getEmail(),
            'phone' => $this->getPhone(),
            'type' => $this->isBusiness() ? 'business' : 'individual',
            'has_complete_info' => $this->isBusiness()
                ? $this->hasCompleteBusinessInfo()
                : $this->hasCompletePersonalInfo(),
        ];
    }

    /**
     * Format phone number for display
     *
     * @return string|null
     */
    public function getFormattedPhone(): ?string
    {
        $phone = $this->getPhone();
        if (!$phone) {
            return null;
        }

        // Convert from API format (371-12345678) to display format (+371 12345678)
        if (preg_match('/^(\d{1,4})-(\d+)$/', $phone, $matches)) {
            return '+' . $matches[1] . ' ' . $matches[2];
        }

        return $phone;
    }

    /**
     * Validate client data
     *
     * @throws InvalidArgumentException
     */
    protected function validate(): void
    {
        if (empty($this->get('id'))) {
            throw new InvalidArgumentException('Client ID is required');
        }

        if (empty($this->get('email')) && empty($this->get('phone'))) {
            throw new InvalidArgumentException('Client must have either email or phone');
        }
    }
}