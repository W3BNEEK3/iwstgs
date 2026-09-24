<?php
namespace Src\SimExecution\Application\Command\CompleteDiagnosticSession;

final class CompleteDiagnosticSessionCommand
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $diagnosticSessionId,
        public readonly string $assignedRankTier,
        public readonly int $assignedRankLevel,
    ) {}
}
