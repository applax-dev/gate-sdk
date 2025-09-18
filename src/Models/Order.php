<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Models;

use DateTime;
use InvalidArgumentException;

/**
 * Order model
 *
 * @package ApplaxDev\GateSDK\Models
 */
class Order extends BaseModel
{
    /**
     * Get order ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->get('id');
    }

    /**
     * Get order type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->get('type', 'order');
    }

    /**
     * Get order number
     *
     * @return string
     */
    public function getNumber(): string
    {
        return $this->get('number');
    }

    /**
     * Get order status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->get('status');
    }

    /**
     * Get order currency
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->get('currency');
    }

    /**
     * Get order amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return (float) $this->get('amount');
    }

    /**
     * Get total amount including tax and fees
     *
     * @return float
     */
    public function getTotalAmount(): float
    {
        return (float) $this->get('total_amount', $this->getAmount());
    }

    /**
     * Get tax amount
     *
     * @return float
     */
    public function getTaxAmount(): float
    {
        return (float) $this->get('tax_amount', 0);
    }

    /**
     * Get discount amount
     *
     * @return float
     */
    public function getDiscountAmount(): float
    {
        return (float) $this->get('discount_amount', 0);
    }

    /**
     * Get order client
     *
     * @return Client|null
     */
    public function getClient(): ?Client
    {
        $clientData = $this->get('client');
        return $clientData ? new Client($clientData) : null;
    }

    /**
     * Get order products
     *
     * @return Product[]
     */
    public function getProducts(): array
    {
        $products = [];
        foreach ($this->get('products', []) as $productData) {
            $products[] = new Product($productData);
        }
        return $products;
    }

    /**
     * Get brand information
     *
     * @return array|null
     */
    public function getBrand(): ?array
    {
        return $this->get('brand');
    }

    /**
     * Get order language
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->get('language', 'en');
    }

    /**
     * Get order notes
     *
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->get('notes');
    }

    /**
     * Check if order is a test order
     *
     * @return bool
     */
    public function isTest(): bool
    {
        return (bool) $this->get('is_test', false);
    }

    /**
     * Check if order should skip capture
     *
     * @return bool
     */
    public function shouldSkipCapture(): bool
    {
        return (bool) $this->get('skip_capture', false);
    }

    /**
     * Check if card should be saved
     *
     * @return bool
     */
    public function shouldSaveCard(): bool
    {
        return (bool) $this->get('save_card', false);
    }

    /**
     * Check if order is MOTO (Mail Order/Telephone Order)
     *
     * @return bool
     */
    public function isMoto(): bool
    {
        return (bool) $this->get('is_moto', false);
    }

    /**
     * Get order due date
     *
     * @return DateTime|null
     */
    public function getDueDate(): ?DateTime
    {
        return $this->parseDateTime($this->get('due'));
    }

    /**
     * Get order creation date
     *
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->parseDateTime($this->get('created_at'));
    }

    /**
     * Get order last updated date
     *
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->parseDateTime($this->get('updated_at'));
    }

    /**
     * Get payment URL for customer
     *
     * @return string|null
     */
    public function getPaymentUrl(): ?string
    {
        return $this->get('payment_url');
    }

    /**
     * Get API do URL for card payments
     *
     * @return string|null
     */
    public function getApiDoUrl(): ?string
    {
        return $this->get('api_do_url');
    }

    /**
     * Get Apple Pay API URL
     *
     * @return string|null
     */
    public function getApplePayUrl(): ?string
    {
        return $this->get('api_do_applepay');
    }

    /**
     * Get Google Pay API URL
     *
     * @return string|null
     */
    public function getGooglePayUrl(): ?string
    {
        return $this->get('api_do_googlepay');
    }

    /**
     * Get PayPal initialization URL
     *
     * @return string|null
     */
    public function getPayPalInitUrl(): ?string
    {
        return $this->get('api_init_paypal');
    }

    /**
     * Get Klarna initialization URL
     *
     * @return string|null
     */
    public function getKlarnaInitUrl(): ?string
    {
        return $this->get('api_init_klarna');
    }

    /**
     * Get available payment methods from API response
     *
     * @return array
     */
    public function getAvailablePaymentMethods(): array
    {
        $methods = [];

        if ($this->getApiDoUrl()) {
            $methods[] = 'card';
        }

        if ($this->getApplePayUrl()) {
            $methods[] = 'apple_pay';
        }

        if ($this->getGooglePayUrl()) {
            $methods[] = 'google_pay';
        }

        if ($this->getPayPalInitUrl()) {
            $methods[] = 'paypal';
        }

        if ($this->getKlarnaInitUrl()) {
            $methods[] = 'klarna';
        }

        return $methods;
    }

    /**
     * Check if order can be paid
     *
     * @return bool
     */
    public function isPayable(): bool
    {
        return in_array($this->getStatus(), ['pending', 'partially_paid']);
    }

    /**
     * Check if order is fully paid
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->getStatus() === 'paid';
    }

    /**
     * Check if order is cancelled
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->getStatus() === 'cancelled';
    }

    /**
     * Check if order is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $dueDate = $this->getDueDate();
        return $dueDate && $dueDate < new DateTime();
    }

    /**
     * Check if order is refundable
     *
     * @return bool
     */
    public function isRefundable(): bool
    {
        return in_array($this->getStatus(), ['paid', 'partially_refunded']);
    }

    /**
     * Get formatted amount with currency
     *
     * @return string
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->getTotalAmount(), 2) . ' ' . $this->getCurrency();
    }

    /**
     * Get order summary
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->getId(),
            'number' => $this->getNumber(),
            'status' => $this->getStatus(),
            'amount' => $this->getFormattedAmount(),
            'client_email' => $this->getClient()?->getEmail(),
            'created_at' => $this->getCreatedAt()?->format('Y-m-d H:i:s'),
            'is_payable' => $this->isPayable(),
            'is_paid' => $this->isPaid(),
        ];
    }

    /**
     * Validate order data
     *
     * @throws InvalidArgumentException
     */
    protected function validate(): void
    {
        if (empty($this->get('id'))) {
            throw new InvalidArgumentException('Order ID is required');
        }

        if (empty($this->get('number'))) {
            throw new InvalidArgumentException('Order number is required');
        }

        if (empty($this->get('currency'))) {
            throw new InvalidArgumentException('Order currency is required');
        }

        if ($this->get('amount') === null || $this->get('amount') < 0) {
            throw new InvalidArgumentException('Order amount must be a positive number');
        }
    }
}