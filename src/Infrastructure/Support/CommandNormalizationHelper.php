<?php

declare(strict_types=1);

namespace Infrastructure\Support;

use Domain\Shared\ValueObjects\Uuid;

class CommandNormalizationHelper
{
    public static function normalizeJsonColumn(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    public static function normalizeIdempotencyKey(string $idempotencyKey): string
    {
        $trimmed = trim($idempotencyKey);

        if ($trimmed !== '') {
            return $trimmed;
        }

        // Mantem rastreabilidade de comandos internos sem depender de header.
        return 'auto-' . Uuid::generate()->toString();
    }

    /**
     * @param array $payload
     * @return array
     */
    public static function normalizePayload(array $payload): array
    {
        ksort($payload, SORT_STRING);

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = self::normalizePayload($value);
            }
        }

        return $payload;
    }
}

