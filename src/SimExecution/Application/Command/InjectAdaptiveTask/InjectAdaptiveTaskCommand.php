<?php
namespace Src\SimExecution\Application\Command\InjectAdaptiveTask;

final class InjectAdaptiveTaskCommand
{
    /**
     * @param string $cardType one of consequence|suggestion
     * @param string $visualTreatment one of consequence_amber|suggestion_teal
     * @param string $priority a BacklogPriority value for the resulting backlog item
     */
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $learnerId,
        public readonly string $cardType,
        public readonly string $sourceTaskId,
        public readonly ?string $injectedTaskId,
        public readonly ?string $targetSprintId,
        public readonly string $visualTreatment,
        public readonly ?string $suggestionType,
        public readonly string $priority,
        public readonly ?array $generatedTaskContent = null,
    ) {}
}
