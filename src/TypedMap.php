<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/data-structure project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\DataStructure;

use Daikon\Interop\Assert;
use Daikon\Interop\Assertion;
use Ds\Map;

abstract class TypedMap implements TypedMapInterface
{
    protected Map $compositeMap;

    protected array $validTypes = [];

    protected function init(iterable $objects, array $validTypes): void
    {
        Assertion::false(isset($this->compositeMap), 'Cannot reinitialize map.');
        Assertion::minCount($validTypes, 1, 'No valid types specified.');
        Assert::thatAll($validTypes, 'Invalid map types.')->string()->notEmpty();

        $this->validTypes = $validTypes;
        $this->compositeMap = new Map;

        foreach ($objects as $key => $object) {
            $this->assertValidKey($key);
            $this->assertValidType($object);
            $this->compositeMap->put($key, clone $object);
        }
    }

    /** @return static */
    public function empty(): self
    {
        $this->assertInitialized();
        $copy = clone $this;
        $copy->compositeMap->clear();
        return $copy;
    }

    public function keys(): array
    {
        $this->assertInitialized();
        return $this->compositeMap->keys()->toArray();
    }

    public function has(string $key): bool
    {
        $this->assertInitialized();
        $this->assertValidKey($key);
        return $this->compositeMap->hasKey($key);
    }

    public function get(string $key, $default = null): ?object
    {
        $this->assertInitialized();
        $this->assertValidKey($key);
        if (func_num_args() === 1) {
            Assertion::satisfy($key, [$this, 'has'], "Key '$key' not found and no default provided.");
            return clone $this->compositeMap->get($key);
        }
        if (!is_null($default)) {
            $this->assertValidType($default);
        }
        $object = $this->compositeMap->get($key, $default);
        return is_null($object) ? null : clone $object;
    }

    /** @return static */
    public function with(string $key, object $object): self
    {
        $this->assertInitialized();
        $this->assertValidKey($key);
        $this->assertValidType($object);
        $copy = clone $this;
        $copy->compositeMap->put($key, clone $object);
        return $copy;
    }

    /** @return static */
    public function without(string $key): self
    {
        $this->assertInitialized();
        Assertion::satisfy($key, [$this, 'has'], "Key '$key' not found.");
        $copy = clone $this;
        $copy->compositeMap->remove($key);
        return $copy;
    }

    /**
     * Note that this does not do a strict equality check because all objects are immutable so it's
     * unlikely that you will request a reference to an internal object. If you require more specific
     * matching use search(), filter(), unwrap object, or iterate.
     */
    public function find(object $object)
    {
        $this->assertInitialized();
        $this->assertValidType($object);
        return array_search($object, $this->compositeMap->toArray(), false);
    }

    public function first(): object
    {
        $this->assertInitialized();
        return clone $this->compositeMap->first()->value;
    }

    public function last(): object
    {
        $this->assertInitialized();
        return clone $this->compositeMap->last()->value;
    }

    public function isEmpty(): bool
    {
        $this->assertInitialized();
        return $this->compositeMap->isEmpty();
    }

    /**
     * @param static $map
     * @return static
     */
    public function merge($map): self
    {
        $this->assertInitialized();
        $this->assertValidMap($map);
        $copy = clone $this;
        $copy->compositeMap = $copy->compositeMap->merge(clone $map);
        return $copy;
    }

    /**
     * @param static $map
     * @return static
     */
    public function intersect($map): self
    {
        $this->assertInitialized();
        $this->assertValidMap($map);
        return $this->filter(fn(string $key): bool => $map->has($key));
    }

    /**
     * @param static $map
     * @return static
     */
    public function diff($map): self
    {
        $this->assertInitialized();
        $this->assertValidMap($map);
        return $this->filter(fn(string $key): bool => !$map->has($key));
    }

    /** @return static */
    public function filter(callable $predicate): self
    {
        $this->assertInitialized();
        $copy = clone $this;
        $copy->compositeMap = $copy->compositeMap->filter($predicate);
        return $copy;
    }

    public function search(callable $predicate)
    {
        $this->assertInitialized();
        foreach ($this as $key => $object) {
            if ($predicate($object) === true) {
                return $key;
            }
        }
        return false;
    }

    /** @return static */
    public function map(callable $predicate): self
    {
        $this->assertInitialized();
        $copy = clone $this;
        $copy->compositeMap->apply($predicate);
        return $copy;
    }

    public function reduce(callable $predicate, $initial = null)
    {
        $this->assertInitialized();
        return $this->compositeMap->reduce($predicate, $initial);
    }

    public function count(): int
    {
        $this->assertInitialized();
        return $this->compositeMap->count();
    }

    public function getValidTypes(): array
    {
        return $this->validTypes;
    }

    /**
     * This function does not clone the internal objects because you may want to access
     * them specifically for some reason.
     */
    public function unwrap(): array
    {
        $this->assertInitialized();
        return $this->compositeMap->toArray();
    }

    /** @psalm-suppress ImplementedReturnTypeMismatch */
    public function getIterator(): Map
    {
        $this->assertInitialized();
        $copy = clone $this;
        return $copy->compositeMap;
    }

    /** @param mixed $map */
    protected function assertValidMap($map): void
    {
        Assertion::isInstanceOf(
            $map,
            static::class,
            sprintf("Map operation must be on same type as '%s'.", static::class)
        );
    }

    /** @psalm-suppress RedundantPropertyInitializationCheck */
    protected function assertInitialized(): void
    {
        Assertion::true(isset($this->compositeMap), 'Map is not initialized.');
    }

    /** @param mixed $key */
    protected function assertValidKey($key): void
    {
        Assert::that($key, 'Key must be a valid string.')->string()->notEmpty();
    }

    /** @param mixed $object */
    protected function assertValidType($object): void
    {
        Assert::thatAll(
            $this->validTypes,
            sprintf("Object types specified in '%s' must be valid class or interface names.", static::class)
        )->string()
        ->notEmpty();

        Assertion::true(
            array_reduce(
                $this->validTypes,
                fn(bool $carry, string $type): bool => $carry || is_a($object, $type, true),
                false
            ),
            sprintf(
                "Invalid object type given to '%s', expected one of [%s] but was given '%s'.",
                static::class,
                implode(', ', $this->validTypes),
                is_object($object) ? get_class($object) : @gettype($object)
            )
        );
    }

    public function __get(string $key): ?object
    {
        return $this->get($key);
    }

    public function __clone()
    {
        $this->assertInitialized();
        $this->compositeMap = new Map(array_map(
            fn(object $object): object => clone $object,
            $this->compositeMap->toArray()
        ));
    }
}
