<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Mapper;

use Src\SimExecution\Domain\Backlog\BacklogItemStatus;
use Src\SimExecution\Domain\Backlog\BacklogPriority;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItem;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemId;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Model\LearnerBacklogItemModel;

final class LearnerBacklogItemMapper
{
    public function toEntity(LearnerBacklogItemModel $m): LearnerBacklogItem
    {
        return LearnerBacklogItem::reconstitute(
            id:               LearnerBacklogItemId::fromString($m->id),
            learnerSessionId: $m->learner_session_id,
            learnerId:        $m->learner_id,
            templateItemId:   $m->template_item_id,
            sprintId:         $m->sprint_id,
            status:           $m->status instanceof BacklogItemStatus ? $m->status : BacklogItemStatus::from($m->status),
            priority:         $m->priority instanceof BacklogPriority ? $m->priority : BacklogPriority::from($m->priority),
            movedToSprintAt:  $m->moved_to_sprint_at?->format('Y-m-d H:i:s'),
            completedAt:      $m->completed_at?->format('Y-m-d H:i:s'),
            learnerNotes:     $m->learner_notes,
            isInjected:       $m->is_injected,
            injectedCardId:   $m->injected_card_id,
        );
    }

    /** @return array attributes keyed for LearnerBacklogItemModel::updateOrCreate() */
    public function toAttributes(LearnerBacklogItem $entity): array
    {
        return $entity->toPrimitives();
    }
}
