<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Models;

use InvalidArgumentException;

/**
 * Product model
 *
 * @package ApplaxDev\GateSDK\Models
 */
class Product extends BaseModel
{
    /**
     * Get product ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->get('id');
    }

    /**
     * Get product type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->get('type', 'product');
    }

    /**
     * Get brand ID
     *
     * @return string
     */
    public function getBrandId(): string
    {
        return $this->get('brand');
    }

    /**
     * Get product title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->get('title');
    }

    /**
     * Get product currency
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->get('currency');
    }

    /**
     * Get product price
     *
     * @return float
     */
    public function getPrice(): float
    {
        return (float) $this->get('price');
    }

    /**
     * Get product description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->get('description');
    }

    /**
     * Get quantity (for order products)
     *
     * @return float
     */
    public function getQuantity(): float
    {
        return (float) $this->get('quantity', 1);
    }

    /**
     * Get tax percentage
     *
     * @return float
     */
    public function getTaxPercent(): float
    {
        return (float) $this->get('tax_percent', 0);
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
     * Get discount percentage
     *
     * @return float
     */
    public function getDiscountPercent(): float
    {
        return (float) $this->get('discount_percent', 0);
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
     * Get tax ID
     *
     * @return string|null
     */
    public function getTaxId(): ?string
    {
        return $this->get('tax');
    }

    /**
     * Get product images
     *
     * @return array
     */
    public function getImages(): array
    {
        return $this->get('images', []);
    }

    /**
     * Get total amount (quantity * price)
     *
     * @return float
     */
    public function getTotal(): float
    {
        return (float) $this->get('total', $this->getQuantity() * $this->getPrice());
    }

    /**
     * Calculate subtotal (total - discount)
     *
     * @return float
     */
    public function getSubtotal(): float
    {
        return $this->getTotal() - $this->getDiscountAmount();
    }

    /**
     * Calculate grand total (subtotal + tax)
     *
     * @return float
     */
    public function getGrandTotal(): float
    {
        return $this->getSubtotal() + $this->getTaxAmount();
    }

    /**
     * Get formatted price with currency
     *
     * @return string
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->getPrice(), 2) . ' ' . $this->getCurrency();
    }

    /**
     * Get formatted total with currency
     *
     * @return string
     */
    public function getFormattedTotal(): string
    {
        return number_format($this->getTotal(), 2) . ' ' . $this->getCurrency();
    }

    /**
     * Check if product has discount
     *
     * @return bool
     */
    public function hasDiscount(): bool
    {
        return $this->getDiscountPercent() > 0 || $this->getDiscountAmount() > 0;
    }

    /**
     * Check if product has tax
     *
     * @return bool
     */
    public function hasTax(): bool
    {
        return $this->getTaxPercent() > 0 || $this->getTaxAmount() > 0;
    }

    /**
     * Check if product has images
     *
     * @return bool
     */
    public function hasImages(): bool
    {
        return !empty($this->getImages());
    }

    /**
     * Get product summary
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'price' => $this->getFormattedPrice(),
            'quantity' => $this->getQuantity(),
            'total' => $this->getFormattedTotal(),
            'has_discount' => $this->hasDiscount(),
            'has_tax' => $this->hasTax(),
        ];
    }

    /**
     * Validate product data
     *
     * @throws InvalidArgumentException
     */
    protected function validate(): void
    {
        if (empty($this->get('id'))) {
            throw new InvalidArgumentException('Product ID is required');
        }

        if (empty($this->get('title'))) {
            throw new InvalidArgumentException('Product title is required');
        }

        if (empty($this->get('currency'))) {
            throw new InvalidArgumentException('Product currency is required');
        }

        if ($this->get('price') === null || $this->get('price') < 0) {
            throw new InvalidArgumentException('Product price must be a positive number');
        }
    }
}