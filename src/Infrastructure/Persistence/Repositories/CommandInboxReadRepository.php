<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Repositories;

use Application\DTOs\CommandStatusResult;
use Domain\Idempotency\Repositories\CommandInboxReadRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CommandInboxReadRepository implements CommandInboxReadRepositoryInterface
{
    public function findByCommandId(string $commandId): ?CommandStatusResult
    {
        $row = DB::table('command_inbox')
            ->where('id', $commandId)
            ->first();

        if ($row === null) {
            return null;
        }

        return new CommandStatusResult(
            commandId: $row->id,
            status: $row->status,
            result: $this->normalizeJsonColumn($row->result),
            errorMessage: $row->error_message,
            processedAt: $row->processed_at,
        );
    }

    private function normalizeJsonColumn(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}

