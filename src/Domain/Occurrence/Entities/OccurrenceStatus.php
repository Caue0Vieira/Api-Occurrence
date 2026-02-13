<?php

declare(strict_types=1);

namespace Domain\Occurrence\Entities;

use JsonSerializable;

class OccurrenceStatus implements JsonSerializable
{
    private function __construct(
        private readonly string $code,
        private readonly string $name,
    ) {
    }

    public static function create(string $code, string $name): self
    {
        return new self(
            code: $code,
            name: $name,
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
        );
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

