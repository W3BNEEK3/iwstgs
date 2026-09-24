<?php
namespace Src\SimExecution\Application\Command\CompleteDiagnosticSession;

use Src\SimExecution\Domain\Diagnostic\DiagnosticSessionRepository;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\SimExecution\Domain\Session\SessionStatus;

/**
 * Dispatched by RankAssignmentService (EvalEngine) once it has computed and
 * written the learner's real starting rank — this only mutates SimExecution's
 * own aggregates (LearnerSession, DiagnosticSession), keeping the "only this
 * module writes its own state" boundary intact even though the trigger comes
 * from another module.
 */
final class CompleteDiagnosticSessionHandler
{
    public function __construct(
        private readonly LearnerSessionRepository $sessions,
        private readonly DiagnosticSessionRepository $diagnosticSessions,
    ) {}

    public function handle(CompleteDiagnosticSessionCommand $command): void
    {
        $session = $this->sessions->findById($command->learnerSessionId);
        if ($session === null || $session->status() !== SessionStatus::Diagnostic) {
            return; // already completed — idempotent no-op
        }

        $session->completeDiagnostic();
        $this->sessions->save($session);

        $this->diagnosticSessions->markComplete(
            $command->diagnosticSessionId,
            $command->assignedRankTier,
            $command->assignedRankLevel,
        );
    }
}
