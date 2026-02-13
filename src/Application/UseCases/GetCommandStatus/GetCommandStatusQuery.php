<?php

declare(strict_types=1);

namespace Application\UseCases\GetCommandStatus;

final readonly class GetCommandStatusQuery
{
    public function __construct(
        public string $commandId,
    ) {
    }
}

