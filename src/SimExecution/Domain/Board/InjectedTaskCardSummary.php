<?php
namespace Src\SimExecution\Domain\Board;

final class InjectedTaskCardSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $learnerSessionId,
        public readonly string $learnerId,
        public readonly string $cardType,
        public readonly string $sourceTaskId,
        public readonly ?string $injectedTaskId,
        public readonly ?string $targetSprintId,
        public readonly string $visualTreatment,
        public readonly string $status,
        public readonly ?string $suggestionType,
        public readonly ?array $generatedTaskContent = null,
    ) {}
}
