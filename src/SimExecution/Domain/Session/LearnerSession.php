<?php
namespace Src\SimExecution\Domain\Session;

use Src\Shared\Domain\AggregateRoot;
use Src\SimExecution\Domain\Exceptions\InvalidSessionTransitionException;

/**
 * A learner's live run through a project, under a specific role enrolment.
 * Unlike RoleEnrolment (write-once, no domain class of its own — see
 * RoleEnrolmentRepository), this is a real lifecycle aggregate: Phase 6
 * repeatedly loads and mutates it (status transitions, current_scenario_id,
 * current_sprint_id, induction_completed_at) as the learner progresses.
 *
 * A session starts in Diagnostic status via begin()'s default. Two things
 * can happen from there: completeInduction() is the normal-project path —
 * it skips straight to Active, since CompleteInductionHandler filters out
 * any is_diagnostic scenario when picking the first one. beginDiagnosticScenario()
 * is the diagnostic-pathway path (Implementation Plan §5.5) — it keeps the
 * session in Diagnostic status while the learner works the diagnostic
 * scenario's tasks, and completeDiagnostic() is its terminal transition once
 * RankAssignmentService has computed a real starting rank.
 */
final class LearnerSession extends AggregateRoot
{
    public function __construct(
        private readonly LearnerSessionId $id,
        private readonly string $learnerId,
        private readonly string $projectId,
        private readonly string $roleEnrolmentId,
        private SessionStatus $status,
        private ?string $currentScenarioId,
        private ?string $currentTaskId,
        private ?string $currentSprintId,
        private ?string $inductionCompletedAt,
        /** BLD §11.4 — dimension to prioritise when selecting the next scenario. */
        private ?string $targetedDimensionId = null,
    ) {}

    public static function begin(
        LearnerSessionId $id,
        string $learnerId,
        string $projectId,
        string $roleEnrolmentId,
    ): self {
        $session = new self(
            id:                    $id,
            learnerId:             $learnerId,
            projectId:             $projectId,
            roleEnrolmentId:       $roleEnrolmentId,
            status:                SessionStatus::Diagnostic,
            currentScenarioId:     null,
            currentTaskId:         null,
            currentSprintId:       null,
            inductionCompletedAt:  null,
        );

        $session->recordEvent(new LearnerSessionStarted(
            sessionId:  (string) $id,
            learnerId:  $learnerId,
            projectId:  $projectId,
        ));

        return $session;
    }

    public static function reconstitute(
        LearnerSessionId $id,
        string $learnerId,
        string $projectId,
        string $roleEnrolmentId,
        SessionStatus $status,
        ?string $currentScenarioId,
        ?string $currentTaskId,
        ?string $currentSprintId,
        ?string $inductionCompletedAt,
        ?string $targetedDimensionId = null,
    ): self {
        return new self(
            $id, $learnerId, $projectId, $roleEnrolmentId, $status,
            $currentScenarioId, $currentTaskId, $currentSprintId,
            $inductionCompletedAt, $targetedDimensionId,
        );
    }

    public function completeInduction(?string $firstScenarioId): void
    {
        if ($this->status !== SessionStatus::Diagnostic) {
            throw new InvalidSessionTransitionException('induction has already been completed for this session');
        }

        $this->inductionCompletedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->currentScenarioId = $firstScenarioId;
        $this->status = SessionStatus::Active;
    }

    public function beginDiagnosticScenario(string $scenarioId): void
    {
        if ($this->status !== SessionStatus::Diagnostic) {
            throw new InvalidSessionTransitionException('a diagnostic scenario can only be started on a session still in the diagnostic status');
        }

        $this->currentScenarioId = $scenarioId;
    }

    public function completeDiagnostic(): void
    {
        if ($this->status !== SessionStatus::Diagnostic) {
            throw new InvalidSessionTransitionException('only a session still in the diagnostic status can complete the diagnostic');
        }

        $this->status = SessionStatus::Complete;
    }

    public function advanceToSprint(string $sprintId): void
    {
        if ($this->status !== SessionStatus::Active) {
            throw new InvalidSessionTransitionException('a sprint can only be set on an active session');
        }

        $this->currentSprintId = $sprintId;
    }

    /** Implementation Plan §9.5 — ScenarioTransitionService, once all of the current scenario's tasks are attempted. */
    public function advanceToScenario(string $scenarioId): void
    {
        if ($this->status !== SessionStatus::Active) {
            throw new InvalidSessionTransitionException('a scenario can only be advanced on an active session');
        }

        $this->currentScenarioId    = $scenarioId;
        $this->targetedDimensionId  = null; // cleared; ScenarioTransitionService may re-set for the new scenario
    }

    /**
     * BLD §11.4 — Records which dimension should be prioritised in the next
     * scenario selection. Dispatched by ScenarioTransitionService when
     * HabitFlag evidence confirms a persistent weakness.
     */
    public function setTargetedDimension(string $dimensionId): void
    {
        $this->targetedDimensionId = $dimensionId;
    }

    /**
     * Gap 3 fix — called by ScenarioTransitionService when findNextScenario()
     * returns null (all scenarios complete). Fires GenerateFinalCompetencyGraph.
     */
    public function markComplete(): void
    {
        if ($this->status !== SessionStatus::Active) {
            throw new InvalidSessionTransitionException('only an active session can be marked complete');
        }

        $this->status = SessionStatus::Complete;
    }

    public function id(): string { return (string) $this->id; }
    public function sessionId(): LearnerSessionId { return $this->id; }
    public function learnerId(): string { return $this->learnerId; }
    public function projectId(): string { return $this->projectId; }
    public function roleEnrolmentId(): string { return $this->roleEnrolmentId; }
    public function status(): SessionStatus { return $this->status; }
    public function currentScenarioId(): ?string { return $this->currentScenarioId; }
    public function currentSprintId(): ?string { return $this->currentSprintId; }
    public function inductionCompletedAt(): ?string { return $this->inductionCompletedAt; }
    public function targetedDimensionId(): ?string { return $this->targetedDimensionId; }

    public function toPrimitives(): array
    {
        return [
            'id'                      => (string) $this->id,
            'learner_id'              => $this->learnerId,
            'project_id'              => $this->projectId,
            'role_enrolment_id'       => $this->roleEnrolmentId,
            'status'                  => $this->status->value,
            'current_scenario_id'     => $this->currentScenarioId,
            'current_task_id'         => $this->currentTaskId,
            'current_sprint_id'       => $this->currentSprintId,
            'induction_completed_at'  => $this->inductionCompletedAt,
            'targeted_dimension_id'   => $this->targetedDimensionId,
        ];
    }
}
