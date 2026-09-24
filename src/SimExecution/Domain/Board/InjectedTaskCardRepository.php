<?php
namespace Src\SimExecution\Domain\Board;

/**
 * injected_task_cards has no domain aggregate — created once by the
 * Adaptive Engine's injectors (ConsequenceTaskInjector, SuggestionTaskInjector,
 * both outside this module), status transitions happen as a side effect of
 * the linked LearnerBacklogItem's own lifecycle rather than independently.
 */
interface InjectedTaskCardRepository
{
    public function create(
        string $learnerSessionId,
        string $learnerId,
        string $cardType,
        string $sourceTaskId,
        ?string $injectedTaskId,
        ?array $generatedTaskContent,
        ?string $targetSprintId,
        string $visualTreatment,
        ?string $suggestionType,
    ): string;

    public function findById(string $id): ?InjectedTaskCardSummary;

    /** @return InjectedTaskCardSummary[] */
    public function findAllForSession(string $learnerSessionId): array;

    public function findLatestId(string $learnerSessionId, string $sourceTaskId): ?string;
}
