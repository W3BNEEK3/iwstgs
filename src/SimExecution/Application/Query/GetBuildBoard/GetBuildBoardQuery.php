<?php
namespace Src\SimExecution\Application\Query\GetBuildBoard;

final class GetBuildBoardQuery
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $projectId,
    ) {}
}
