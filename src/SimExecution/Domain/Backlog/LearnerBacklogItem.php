<?php
namespace Src\SimExecution\Domain\Backlog;

use Src\Shared\Domain\Entity;
use Src\SimExecution\Domain\Exceptions\InvalidBacklogItemTransitionException;

/**
 * A learner's own copy of a backlog_item_template, tracked through the
 * board's status lifecycle. No domain events — hundreds of these are seeded
 * in bulk per session (one per project's backlog_item_templates row), and
 * no listener needs a per-item creation fact; only the aggregate's own
 * mutation methods enforce the board's transition rules.
 */
final class LearnerBacklogItem extends Entity
{
    public function __construct(
        private readonly LearnerBacklogItemId $id,
        private readonly string $learnerSessionId,
        private readonly string $learnerId,
        private readonly ?string $templateItemId,
        private ?string $sprintId,
        private BacklogItemStatus $status,
        private BacklogPriority $priority,
        private ?string $movedToSprintAt,
        private ?string $completedAt,
        private ?string $learnerNotes,
        private readonly bool $isInjected,
        private ?string $injectedCardId,
    ) {}

    public static function seedFromTemplate(
        LearnerBacklogItemId $id,
        string $learnerSessionId,
        string $learnerId,
        string $templateItemId,
        BacklogPriority $priority,
    ): self {
        return new self(
            id:               $id,
            learnerSessionId: $learnerSessionId,
            learnerId:        $learnerId,
            templateItemId:   $templateItemId,
            sprintId:         null,
            status:           BacklogItemStatus::Backlog,
            priority:         $priority,
            movedToSprintAt:  null,
            completedAt:      null,
            learnerNotes:     null,
            isInjected:       false,
            injectedCardId:   null,
        );
    }

    /**
     * A consequence/suggestion task the Adaptive Engine (Phase 9) selected
     * dynamically from another task's consequence_task_ids/suggestion_task_ids
     * — there's no backlog_item_template row behind it, hence templateItemId
     * null and injectedCardId set instead (the inverse of seedFromTemplate).
     */
    public static function seedFromInjection(
        LearnerBacklogItemId $id,
        string $learnerSessionId,
        string $learnerId,
        string $injectedCardId,
        BacklogPriority $priority,
    ): self {
        return new self(
            id:               $id,
            learnerSessionId: $learnerSessionId,
            learnerId:        $learnerId,
            templateItemId:   null,
            sprintId:         null,
            status:           BacklogItemStatus::Backlog,
            priority:         $priority,
            movedToSprintAt:  null,
            completedAt:      null,
            learnerNotes:     null,
            isInjected:       true,
            injectedCardId:   $injectedCardId,
        );
    }

    public static function reconstitute(
        LearnerBacklogItemId $id,
        string $learnerSessionId,
        string $learnerId,
        ?string $templateItemId,
        ?string $sprintId,
        BacklogItemStatus $status,
        BacklogPriority $priority,
        ?string $movedToSprintAt,
        ?string $completedAt,
        ?string $learnerNotes,
        bool $isInjected,
        ?string $injectedCardId,
    ): self {
        return new self(
            $id, $learnerSessionId, $learnerId, $templateItemId, $sprintId,
            $status, $priority, $movedToSprintAt, $completedAt, $learnerNotes,
            $isInjected, $injectedCardId,
        );
    }

    public function moveToSprint(string $sprintId): void
    {
        if ($this->status !== BacklogItemStatus::Backlog) {
            throw new InvalidBacklogItemTransitionException('only a backlog item can be moved into a sprint');
        }

        $this->sprintId = $sprintId;
        $this->status = BacklogItemStatus::InSprint;
        $this->movedToSprintAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    public function returnToBacklog(): void
    {
        if ($this->status !== BacklogItemStatus::InSprint) {
            throw new InvalidBacklogItemTransitionException('only an item still in sprint planning (not yet started) can be returned to the backlog');
        }

        $this->sprintId = null;
        $this->status = BacklogItemStatus::Backlog;
        $this->movedToSprintAt = null;
    }

    public function start(): void
    {
        if ($this->status !== BacklogItemStatus::InSprint) {
            throw new InvalidBacklogItemTransitionException('only an item in the current sprint can be started');
        }

        $this->status = BacklogItemStatus::InProgress;
    }

    public function revertToSprint(): void
    {
        if ($this->status !== BacklogItemStatus::InProgress) {
            throw new InvalidBacklogItemTransitionException('only an in-progress item can be moved back to not-started');
        }

        $this->status = BacklogItemStatus::InSprint;
    }

    public function complete(): void
    {
        if (! in_array($this->status, [BacklogItemStatus::InProgress, BacklogItemStatus::Blocked], true)) {
            throw new InvalidBacklogItemTransitionException('only an in-progress or blocked item can be completed');
        }

        $this->status = BacklogItemStatus::Done;
        $this->completedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    public function block(): void
    {
        if (! in_array($this->status, [BacklogItemStatus::InSprint, BacklogItemStatus::InProgress], true)) {
            throw new InvalidBacklogItemTransitionException('only an item in the current sprint can be blocked');
        }

        $this->status = BacklogItemStatus::Blocked;
    }

    public function unblock(): void
    {
        if ($this->status !== BacklogItemStatus::Blocked) {
            throw new InvalidBacklogItemTransitionException('only a blocked item can be unblocked');
        }

        $this->status = BacklogItemStatus::InProgress;
    }

    public function updatePriority(BacklogPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function setNotes(?string $notes): void
    {
        $this->learnerNotes = $notes;
    }

    public function id(): string { return (string) $this->id; }
    public function backlogItemId(): LearnerBacklogItemId { return $this->id; }
    public function learnerSessionId(): string { return $this->learnerSessionId; }
    public function learnerId(): string { return $this->learnerId; }
    public function templateItemId(): ?string { return $this->templateItemId; }
    public function sprintId(): ?string { return $this->sprintId; }
    public function status(): BacklogItemStatus { return $this->status; }
    public function priority(): BacklogPriority { return $this->priority; }
    public function learnerNotes(): ?string { return $this->learnerNotes; }
    public function isInjected(): bool { return $this->isInjected; }
    public function injectedCardId(): ?string { return $this->injectedCardId; }

    public function toPrimitives(): array
    {
        return [
            'id'                 => (string) $this->id,
            'learner_session_id' => $this->learnerSessionId,
            'learner_id'         => $this->learnerId,
            'template_item_id'   => $this->templateItemId,
            'sprint_id'          => $this->sprintId,
            'status'             => $this->status->value,
            'priority'           => $this->priority->value,
            'moved_to_sprint_at' => $this->movedToSprintAt,
            'completed_at'       => $this->completedAt,
            'learner_notes'      => $this->learnerNotes,
            'is_injected'        => $this->isInjected,
            'injected_card_id'   => $this->injectedCardId,
        ];
    }
}
