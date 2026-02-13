<?php

namespace Domain\Shared\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class DomainCollection implements IteratorAggregate, Countable, JsonSerializable
{
    private array $items;

    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    public function filter(callable $callback): self
    {
        return new self(array_filter($this->items, $callback));
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function pluck(string $key): self
    {
        return new self(array_map(fn($item) => $item->$key ?? null, $this->items));
    }

    public function implode(string $glue): string
    {
        return implode($glue, array_map(fn($item) => (string) $item, $this->items));
    }

    public function groupBy(callable $callback): array
    {
        $grouped = [];
        foreach ($this->items as $item) {
            $key = $callback($item);
            $grouped[$key][] = $item;
        }

        return array_map(fn($group) => new self($group), $grouped);
    }

    public function values(): self
    {
        return new self(array_values($this->items));
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
