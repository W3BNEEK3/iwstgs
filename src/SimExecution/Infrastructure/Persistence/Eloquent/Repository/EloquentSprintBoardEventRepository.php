<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Str;
use Src\SimExecution\Domain\Board\SprintBoardEventRepository;
use Src\SimExecution\Domain\Board\SprintBoardEventType;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\SprintBoardEventModel;

final class EloquentSprintBoardEventRepository implements SprintBoardEventRepository
{
    public function record(
        string $learnerId,
        string $sessionId,
        ?string $sprintId,
        ?string $itemId,
        SprintBoardEventType $eventType,
        ?string $fromStatus = null,
        ?string $toStatus = null,
    ): void {
        // created_at is left unset — the migration's DEFAULT CURRENT_TIMESTAMP fills it in,
        // same convention as role_enrolments.enrolled_at and rank_events.created_at.
        SprintBoardEventModel::create([
            'id'          => (string) Str::uuid(),
            'learner_id'  => $learnerId,
            'session_id'  => $sessionId,
            'sprint_id'   => $sprintId,
            'item_id'     => $itemId,
            'event_type'  => $eventType,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
        ]);
    }
}
