<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Repositories;

use Application\DTOs\CommandStatusResult;
use Domain\Idempotency\Repositories\CommandInboxReadRepositoryInterface;
use Infrastructure\Support\CommandNormalizationHelper;
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
            result: CommandNormalizationHelper::normalizeJsonColumn($row->result),
            errorMessage: $row->error_message,
            processedAt: $row->processed_at,
        );
    }
}

