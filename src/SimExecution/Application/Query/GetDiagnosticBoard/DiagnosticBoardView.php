<?php
namespace Src\SimExecution\Application\Query\GetDiagnosticBoard;

final class DiagnosticBoardView
{
    /** @param DiagnosticTaskView[] $tasks */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $scenarioTitle,
        public readonly string $narrativeContext,
        public readonly string $situationTrigger,
        public readonly array $tasks,
        public readonly bool $isComplete,
        public readonly ?string $assignedRankTier,
        public readonly ?int $assignedRankLevel,
    ) {}
}
