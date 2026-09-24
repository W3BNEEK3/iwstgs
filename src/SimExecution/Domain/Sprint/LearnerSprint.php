<?php
namespace Src\SimExecution\Domain\Sprint;

use Src\Shared\Domain\AggregateRoot;
use Src\SimExecution\Domain\Exceptions\InvalidSprintTransitionException;
use Src\SimExecution\Domain\Exceptions\SprintGoalRequiredException;

/**
 * A single sprint within a learner_session — a real lifecycle aggregate
 * (planning -> active -> submitted -> evaluated), unlike the write-once
 * byproduct records elsewhere in this module.
 */
final class LearnerSprint extends AggregateRoot
{
    public function __construct(
        private readonly LearnerSprintId $id,
        private readonly string $learnerSessionId,
        private readonly string $learnerId,
        private readonly string $projectId,
        private readonly int $sprintNumber,
        private ?string $sprintGoal,
        private SprintGoalSource $sprintGoalSource,
        private SprintStatus $status,
        private bool $scopeWarningIssued,
        private ?string $startedAt,
        private ?string $submittedAt,
    ) {}

    public static function plan(
        LearnerSprintId $id,
        string $learnerSessionId,
        string $learnerId,
        string $projectId,
        int $sprintNumber,
        SprintGoalSource $sprintGoalSource,
        ?string $sprintGoal = null,
    ): self {
        $sprint = new self(
            id:                  $id,
            learnerSessionId:    $learnerSessionId,
            learnerId:           $learnerId,
            projectId:           $projectId,
            sprintNumber:        $sprintNumber,
            sprintGoal:          $sprintGoal,
            sprintGoalSource:    $sprintGoalSource,
            status:              SprintStatus::Planning,
            scopeWarningIssued:  false,
            startedAt:           null,
            submittedAt:         null,
        );

        $sprint->recordEvent(new LearnerSprintPlanned(
            sprintId:          (string) $id,
            learnerSessionId:  $learnerSessionId,
            learnerId:         $learnerId,
            sprintNumber:      $sprintNumber,
        ));

        return $sprint;
    }

    public static function reconstitute(
        LearnerSprintId $id,
        string $learnerSessionId,
        string $learnerId,
        string $projectId,
        int $sprintNumber,
        ?string $sprintGoal,
        SprintGoalSource $sprintGoalSource,
        SprintStatus $status,
        bool $scopeWarningIssued,
        ?string $startedAt,
        ?string $submittedAt,
    ): self {
        return new self(
            $id, $learnerSessionId, $learnerId, $projectId, $sprintNumber,
            $sprintGoal, $sprintGoalSource, $status, $scopeWarningIssued,
            $startedAt, $submittedAt,
        );
    }

    public function writeGoal(string $goal): void
    {
        if ($this->status !== SprintStatus::Planning) {
            throw new InvalidSprintTransitionException('the sprint goal can only be edited while the sprint is in planning');
        }

        $this->sprintGoal = $goal;
    }

    public function markScopeWarningIssued(): void
    {
        $this->scopeWarningIssued = true;
    }

    public function confirm(): void
    {
        if ($this->status !== SprintStatus::Planning) {
            throw new InvalidSprintTransitionException('only a sprint in planning can be confirmed');
        }

        if ($this->sprintGoal === null || trim($this->sprintGoal) === '') {
            throw new SprintGoalRequiredException();
        }

        $this->status = SprintStatus::Active;
        $this->startedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    public function submit(): void
    {
        if ($this->status !== SprintStatus::Active) {
            throw new InvalidSprintTransitionException('only an active sprint can be submitted');
        }

        $this->status = SprintStatus::Submitted;
        $this->submittedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    public function id(): string { return (string) $this->id; }
    public function sprintId(): LearnerSprintId { return $this->id; }
    public function learnerSessionId(): string { return $this->learnerSessionId; }
    public function learnerId(): string { return $this->learnerId; }
    public function projectId(): string { return $this->projectId; }
    public function sprintNumber(): int { return $this->sprintNumber; }
    public function sprintGoal(): ?string { return $this->sprintGoal; }
    public function sprintGoalSource(): SprintGoalSource { return $this->sprintGoalSource; }
    public function status(): SprintStatus { return $this->status; }
    public function scopeWarningIssued(): bool { return $this->scopeWarningIssued; }

    public function toPrimitives(): array
    {
        return [
            'id'                   => (string) $this->id,
            'learner_session_id'   => $this->learnerSessionId,
            'learner_id'           => $this->learnerId,
            'project_id'           => $this->projectId,
            'sprint_number'        => $this->sprintNumber,
            'sprint_goal'          => $this->sprintGoal,
            'sprint_goal_source'   => $this->sprintGoalSource->value,
            'status'               => $this->status->value,
            'scope_warning_issued' => $this->scopeWarningIssued,
            'started_at'           => $this->startedAt,
            'submitted_at'         => $this->submittedAt,
        ];
    }
}
