<?php
namespace Src\SimExecution\Application\Query\GetSprintBoard;

final class SprintBoardView
{
    /**
     * @param BacklogItemBoardView[] $toDo
     * @param BacklogItemBoardView[] $inProgress
     * @param BacklogItemBoardView[] $done
     * @param BacklogItemBoardView[] $blocked
     * @param VaultItemBoardView[] $vaultItems
     * @param ReferenceMaterialBoardView[] $referenceMaterials
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $sprintId,
        public readonly int $sprintNumber,
        public readonly string $sprintGoal,
        public readonly ?string $scenarioTitle,
        public readonly array $toDo,
        public readonly array $inProgress,
        public readonly array $done,
        public readonly array $blocked,
        public readonly array $vaultItems,
        public readonly array $referenceMaterials,
    ) {}
}
