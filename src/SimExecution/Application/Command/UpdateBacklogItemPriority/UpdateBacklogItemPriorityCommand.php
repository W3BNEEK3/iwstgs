<?php
namespace Src\SimExecution\Application\Command\UpdateBacklogItemPriority;

final class UpdateBacklogItemPriorityCommand
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $itemId,
        public readonly string $priority,
    ) {}
}
