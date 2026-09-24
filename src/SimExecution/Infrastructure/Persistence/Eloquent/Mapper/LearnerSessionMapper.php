<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Mapper;

use Src\SimExecution\Domain\Session\LearnerSession;
use Src\SimExecution\Domain\Session\LearnerSessionId;
use Src\SimExecution\Domain\Session\SessionStatus;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerSessionModel;

final class LearnerSessionMapper
{
    public function toEntity(LearnerSessionModel $m): LearnerSession
    {
        return LearnerSession::reconstitute(
            id:                   LearnerSessionId::fromString($m->id),
            learnerId:            $m->learner_id,
            projectId:            $m->project_id,
            roleEnrolmentId:      $m->role_enrolment_id,
            status:               $m->status instanceof SessionStatus ? $m->status : SessionStatus::from($m->status),
            currentScenarioId:    $m->current_scenario_id,
            currentTaskId:        $m->current_task_id,
            currentSprintId:      $m->current_sprint_id,
            inductionCompletedAt: $m->induction_completed_at?->format('Y-m-d H:i:s'),
            targetedDimensionId:  $m->targeted_dimension_id,
        );
    }

    /** @return array attributes keyed for LearnerSessionModel::updateOrCreate() */
    public function toAttributes(LearnerSession $entity): array
    {
        return $entity->toPrimitives();
    }
}
