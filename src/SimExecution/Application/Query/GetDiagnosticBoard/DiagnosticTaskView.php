<?php
namespace Src\SimExecution\Application\Query\GetDiagnosticBoard;

final class DiagnosticTaskView
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $title,
        public readonly string $taskBrief,
        public readonly int $submissionCount,
    ) {}
}
