<?php

declare(strict_types=1);

namespace Application\Support;

use InvalidArgumentException;

class OutboxEventResolver
{
    public function resolve(string $commandType): array
    {
        return match ($commandType) {
            'create_occurrence' => [
                'aggregateType' => 'OccurrenceCommand',
                'eventType' => 'OccurrenceCreateRequested',
            ],
            'start_occurrence' => [
                'aggregateType' => 'OccurrenceCommand',
                'eventType' => 'OccurrenceStartRequested',
            ],
            'resolve_occurrence' => [
                'aggregateType' => 'OccurrenceCommand',
                'eventType' => 'OccurrenceResolvedRequested',
            ],
            'create_dispatch' => [
                'aggregateType' => 'DispatchCommand',
                'eventType' => 'DispatchCreateRequested',
            ],
            'close_dispatch' => [
                'aggregateType' => 'DispatchCommand',
                'eventType' => 'DispatchCloseRequested',
            ],
            'update_dispatch_status' => [
                'aggregateType' => 'DispatchCommand',
                'eventType' => 'DispatchStatusUpdateRequested',
            ],
            default => throw new InvalidArgumentException("Unsupported command type: {$commandType}"),
        };
    }
}
