<?php
namespace Src\SimExecution\Domain\Board;

/**
 * sprint_board_events has no domain aggregate — it's an immutable, append-only
 * audit log, same reasoning as RoleEnrolmentRepository. Handlers call record()
 * inline inside their own transaction after a mutation succeeds.
 */
interface SprintBoardEventRepository
{
    public function record(
        string $learnerId,
        string $sessionId,
        ?string $sprintId,
        ?string $itemId,
        SprintBoardEventType $eventType,
        ?string $fromStatus = null,
        ?string $toStatus = null,
    ): void;
}
