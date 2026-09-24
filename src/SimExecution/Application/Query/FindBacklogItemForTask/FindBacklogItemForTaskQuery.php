<?php
namespace Src\SimExecution\Application\Query\FindBacklogItemForTask;

final class FindBacklogItemForTaskQuery
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $taskId,
        public readonly string $projectId,
    ) {}
}
