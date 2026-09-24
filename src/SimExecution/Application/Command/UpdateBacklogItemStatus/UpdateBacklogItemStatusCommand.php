<?php
namespace Src\SimExecution\Application\Command\UpdateBacklogItemStatus;

final class UpdateBacklogItemStatusCommand
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $itemId,
        public readonly string $targetStatus,
    ) {}
}
