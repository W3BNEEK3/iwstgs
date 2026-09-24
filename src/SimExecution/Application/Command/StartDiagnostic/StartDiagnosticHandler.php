<?php
namespace Src\SimExecution\Application\Command\StartDiagnostic;

use Illuminate\Support\Facades\DB;
use Src\Competency\Application\Query\ListRoleDefinitions\ListRoleDefinitionsQuery;
use Src\Competency\Domain\Role\RoleDefinitionSummary;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Diagnostic\DiagnosticPathway;
use Src\SimExecution\Domain\Diagnostic\DiagnosticSessionRepository;
use Src\SimExecution\Domain\Diagnostic\DiagnosticStatus;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Exceptions\DiagnosticContentNotConfiguredException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\RoleEnrolment\RoleEnrolmentRepository;
use Src\SimExecution\Domain\Session\LearnerSession;
use Src\SimExecution\Domain\Session\LearnerSessionId;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\Simulation\Application\Query\GetDiagnosticScenario\GetDiagnosticScenarioQuery;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;

/**
 * Idempotent entry point for /learn/diagnostic — resumes an in-progress
 * diagnostic, reports an already-complete one, or starts a brand new one.
 * The diagnostic has exactly one fixed role and one fixed scenario (no
 * learner choice involved), so this creates the RoleEnrolment + LearnerSession
 * directly rather than going through EnrolInProjectHandler, which is built
 * for learner-chosen roles on real projects.
 */
final class StartDiagnosticHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSessionRepository $sessions,
        private readonly RoleEnrolmentRepository $roleEnrolments,
        private readonly DiagnosticSessionRepository $diagnosticSessions,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(StartDiagnosticCommand $command): StartDiagnosticResult
    {
        $learner = $command->userId !== null ? $this->learners->findByUserId($command->userId) : null;
        if ($learner === null) {
            throw new LearnerNotFoundException();
        }

        /** @var ScenarioTemplate|null $scenario */
        $scenario = $this->queryBus->ask(new GetDiagnosticScenarioQuery());
        if ($scenario === null) {
            throw new DiagnosticContentNotConfiguredException();
        }

        $existingDiagnostic = $this->diagnosticSessions->findLatestForLearner($learner->id());
        if ($existingDiagnostic !== null) {
            $existingSession = $this->sessions->findByLearnerAndProject($learner->id(), $scenario->projectId());

            if ($existingSession !== null) {
                return new StartDiagnosticResult(
                    sessionId:            $existingSession->id(),
                    scenarioId:           $scenario->id(),
                    diagnosticSessionId:  $existingDiagnostic->id,
                    isComplete:           $existingDiagnostic->status === DiagnosticStatus::Complete,
                    assignedRankTier:     $existingDiagnostic->assignedRankTier,
                    assignedRankLevel:    $existingDiagnostic->assignedRankLevel,
                );
            }
        }

        $project = $this->queryBus->ask(new GetProjectQuery($scenario->projectId()));
        if ($project === null) {
            throw new DiagnosticContentNotConfiguredException();
        }

        /** @var RoleDefinitionSummary[] $roles */
        $roles = $this->queryBus->ask(new ListRoleDefinitionsQuery());
        $role = null;
        foreach ($roles as $candidate) {
            if (array_intersect($project->specializationTags(), $candidate->specializationTags) !== []) {
                $role = $candidate;
                break;
            }
        }
        if ($role === null) {
            throw new DiagnosticContentNotConfiguredException();
        }

        $session = null;
        $diagnosticSessionId = null;

        DB::transaction(function () use ($learner, $project, $role, $scenario, &$session, &$diagnosticSessionId) {
            $roleEnrolmentId = $this->roleEnrolments->create($learner->id(), $role->id, $project->id());

            $session = LearnerSession::begin(
                id:              LearnerSessionId::generate(),
                learnerId:       $learner->id(),
                projectId:       $project->id(),
                roleEnrolmentId: $roleEnrolmentId,
            );
            $session->beginDiagnosticScenario($scenario->id());

            $this->sessions->save($session);

            $diagnosticSessionId = $this->diagnosticSessions->create($learner->id(), DiagnosticPathway::DiagnosticScenario);
        });

        foreach ($session->releaseEvents() as $event) {
            event($event);
        }

        return new StartDiagnosticResult(
            sessionId:            $session->id(),
            scenarioId:           $scenario->id(),
            diagnosticSessionId:  $diagnosticSessionId,
            isComplete:           false,
            assignedRankTier:     null,
            assignedRankLevel:    null,
        );
    }
}
