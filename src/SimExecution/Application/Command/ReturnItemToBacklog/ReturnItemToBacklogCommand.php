<?php
namespace Src\SimExecution\Application\Command\ReturnItemToBacklog;

final class ReturnItemToBacklogCommand
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $itemId,
    ) {}
}
