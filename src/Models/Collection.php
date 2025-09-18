<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Models;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection model for paginated API results
 *
 * @package ApplaxDev\GateSDK\Models
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    private array $items;
    private ?string $nextCursor;
    private ?string $previousCursor;
    private int $count;
    private int $totalCount;
    private ?string $itemClass;

    /**
     * Create a new collection
     *
     * @param array $data Response data from API
     * @param string|null $itemClass Class to instantiate for each item
     */
    public function __construct(array $data, ?string $itemClass = null)
    {
        $this->nextCursor = $data['next'] ?? null;
        $this->previousCursor = $data['previous'] ?? null;
        $this->count = $data['count'] ?? 0;
        $this->totalCount = $data['total_count'] ?? $this->count;
        $this->itemClass = $itemClass;

        $this->items = [];
        foreach ($data['results'] ?? [] as $itemData) {
            if ($itemClass && class_exists($itemClass)) {
                $this->items[] = new $itemClass($itemData);
            } else {
                $this->items[] = $itemData;
            }
        }
    }

    /**
     * Get all items
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get first item
     *
     * @return mixed|null
     */
    public function first()
    {
        return $this->items[0] ?? null;
    }

    /**
     * Get last item
     *
     * @return mixed|null
     */
    public function last()
    {
        return empty($this->items) ? null : $this->items[count($this->items) - 1];
    }

    /**
     * Get next cursor for pagination
     *
     * @return string|null
     */
    public function getNextCursor(): ?string
    {
        return $this->nextCursor;
    }

    /**
     * Get previous cursor for pagination
     *
     * @return string|null
     */
    public function getPreviousCursor(): ?string
    {
        return $this->previousCursor;
    }

    /**
     * Get current page count
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Get total count of all items
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * Get the item class used for instantiation
     *
     * @return string|null
     */
    public function getItemClass(): ?string
    {
        return $this->itemClass;
    }

    /**
     * Check if there are more items available
     *
     * @return bool
     */
    public function hasNext(): bool
    {
        return $this->nextCursor !== null;
    }

    /**
     * Check if there are previous items available
     *
     * @return bool
     */
    public function hasPrevious(): bool
    {
        return $this->previousCursor !== null;
    }

    /**
     * Check if collection is empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if collection is not empty
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Filter items by a callback function
     *
     * @param callable $callback
     * @return self
     */
    public function filter(callable $callback): self
    {
        $filteredItems = array_filter($this->items, $callback);

        $newData = [
            'results' => array_values($filteredItems),
            'count' => count($filteredItems),
            'total_count' => count($filteredItems),
            'next' => null,
            'previous' => null,
        ];

        return new self($newData, $this->itemClass);
    }

    /**
     * Map items using a callback function
     *
     * @param callable $callback
     * @return array
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * Find first item matching callback
     *
     * @param callable $callback
     * @return mixed|null
     */
    public function find(callable $callback)
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Check if any item matches callback
     *
     * @param callable $callback
     * @return bool
     */
    public function some(callable $callback): bool
    {
        return $this->find($callback) !== null;
    }

    /**
     * Check if all items match callback
     *
     * @param callable $callback
     * @return bool
     */
    public function every(callable $callback): bool
    {
        foreach ($this->items as $item) {
            if (!$callback($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get items as array
     *
     * @return array
     */
    public function toArray(): array
    {
        $items = [];
        foreach ($this->items as $item) {
            if (method_exists($item, 'toArray')) {
                $items[] = $item->toArray();
            } else {
                $items[] = $item;
            }
        }

        return [
            'items' => $items,
            'count' => $this->count,
            'total_count' => $this->totalCount,
            'next' => $this->nextCursor,
            'previous' => $this->previousCursor,
        ];
    }

    /**
     * Get JSON representation
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Get collection summary
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'item_class' => $this->itemClass,
            'count' => $this->count,
            'total_count' => $this->totalCount,
            'has_next' => $this->hasNext(),
            'has_previous' => $this->hasPrevious(),
            'is_empty' => $this->isEmpty(),
        ];
    }

    // ArrayAccess implementation

    /**
     * Check if offset exists
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Get item at offset
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * Set item at offset
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Unset item at offset
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
        $this->items = array_values($this->items); // Re-index array
    }

    // Countable implementation

    /**
     * Count items
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    // IteratorAggregate implementation

    /**
     * Get iterator
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}