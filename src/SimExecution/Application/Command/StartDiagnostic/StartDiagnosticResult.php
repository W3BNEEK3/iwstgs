<?php
namespace Src\SimExecution\Application\Command\StartDiagnostic;

final class StartDiagnosticResult
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $scenarioId,
        public readonly string $diagnosticSessionId,
        public readonly bool $isComplete,
        public readonly ?string $assignedRankTier,
        public readonly ?int $assignedRankLevel,
    ) {}
}
