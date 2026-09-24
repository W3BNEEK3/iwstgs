<?php
namespace Src\SimExecution\Domain\Diagnostic;

final class DiagnosticSessionSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $learnerId,
        public readonly DiagnosticPathway $pathway,
        public readonly DiagnosticStatus $status,
        public readonly ?string $assignedRankTier,
        public readonly ?int $assignedRankLevel,
    ) {}
}
