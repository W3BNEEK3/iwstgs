<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Mapper;

use Src\SimExecution\Domain\Sprint\LearnerSprint;
use Src\SimExecution\Domain\Sprint\LearnerSprintId;
use Src\SimExecution\Domain\Sprint\SprintGoalSource;
use Src\SimExecution\Domain\Sprint\SprintStatus;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerSprintModel;

final class LearnerSprintMapper
{
    public function toEntity(LearnerSprintModel $m): LearnerSprint
    {
        return LearnerSprint::reconstitute(
            id:                 LearnerSprintId::fromString($m->id),
            learnerSessionId:   $m->learner_session_id,
            learnerId:          $m->learner_id,
            projectId:          $m->project_id,
            sprintNumber:       $m->sprint_number,
            sprintGoal:         $m->sprint_goal,
            sprintGoalSource:   $m->sprint_goal_source instanceof SprintGoalSource ? $m->sprint_goal_source : SprintGoalSource::from($m->sprint_goal_source),
            status:             $m->status instanceof SprintStatus ? $m->status : SprintStatus::from($m->status),
            scopeWarningIssued: $m->scope_warning_issued,
            startedAt:          $m->started_at?->format('Y-m-d H:i:s'),
            submittedAt:        $m->submitted_at?->format('Y-m-d H:i:s'),
        );
    }

    /** @return array attributes keyed for LearnerSprintModel::updateOrCreate() */
    public function toAttributes(LearnerSprint $entity): array
    {
        return $entity->toPrimitives();
    }
}
