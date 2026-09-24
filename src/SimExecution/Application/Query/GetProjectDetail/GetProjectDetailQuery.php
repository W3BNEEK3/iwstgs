<?php
namespace Src\SimExecution\Application\Query\GetProjectDetail;

final class GetProjectDetailQuery
{
    public function __construct(
        public readonly string $projectId,
        public readonly ?string $userId,
    ) {}
}
