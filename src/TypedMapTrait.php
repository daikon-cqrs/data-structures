<?php
declare(strict_types=1);

namespace Daikon\DataStructure;

use Ds\Map;
use InvalidArgumentException;
use Iterator;

trait TypedMapTrait
{
    /**
     * @var Ds\Map internal map to store items
     */
    private $compositeMap;

    /**
     * @var string fully qualified class name of acceptable types
     */
    private $itemFqcn;

    public function has(string $key): bool
    {
        return $this->compositeMap->hasKey($key);
    }

    /**
     * @throws OutOfBoundsException
     */
    public function get(string $key)
    {
        return $this->compositeMap->get($key);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function set($key, $item): self
    {
        $this->assertItemType($item);
        $copy = clone $this;
        $copy->compositeMap->put($key, $item);
        return $copy;
    }

    public function count(): int
    {
        return count($this->compositeMap);
    }

    public function toArray(): array
    {
        return $this->compositeMap->toArray();
    }

    public function isEmpty(): bool
    {
        return $this->compositeMap->isEmpty();
    }

    public function getIterator(): Iterator
    {
        return $this->compositeMap->getIterator();
    }

    public function getItemFqcn(): string
    {
        return $this->itemFqcn;
    }

    /**
     * @throws OutOfBoundsException
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    private function init(iterable $items, string $itemFqcn): void
    {
        $this->itemFqcn = $itemFqcn;
        foreach ($items as $key => $item) {
            $this->assertItemKey($key);
            $this->assertItemType($item);
        }
        $this->compositeMap = new Map($items);
    }

    private function assertItemKey($key): void
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid item key given to %s. Expected string but was given %s.',
                static::CLASS,
                is_object($key) ? get_class($key) : @gettype($key)
            ));
        }
    }

    private function assertItemType($item): void
    {
        if (!is_a($item, $this->itemFqcn)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid item type given to %s. Expected %s but was given %s.',
                static::CLASS,
                $this->itemFqcn,
                is_object($item) ? get_class($item) : @gettype($item)
            ));
        }
    }

    public function __clone()
    {
        $this->compositeMap = new Map($this->compositeMap->toArray());
    }
}
