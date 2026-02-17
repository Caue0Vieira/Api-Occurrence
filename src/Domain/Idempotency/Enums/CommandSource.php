<?php

declare(strict_types=1);

namespace Domain\Idempotency\Enums;

enum CommandSource: string
{
    case INTERNAL = 'internal_system';
    case EXTERNAL = 'external_system';
}


