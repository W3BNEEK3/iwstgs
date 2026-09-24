<?php
namespace Src\SimExecution\Application\Query\FindDiagnosticSessionForTask;

final class FindDiagnosticSessionForTaskQuery
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $taskId,
    ) {}
}
