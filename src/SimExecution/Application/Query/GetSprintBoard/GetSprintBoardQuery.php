<?php
namespace Src\SimExecution\Application\Query\GetSprintBoard;

final class GetSprintBoardQuery
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $projectId,
    ) {}
}
