<?php
namespace Src\SimExecution\Application\Command\EnrolInProject;

use Illuminate\Support\Facades\DB;
use Src\Competency\Application\Query\ListRoleDefinitions\ListRoleDefinitionsQuery;
use Src\Competency\Domain\Role\RoleDefinitionSummary;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Exceptions\AlreadyEnrolledInProjectException;
use Src\SimExecution\Domain\Exceptions\InsufficientExperienceException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;
use Src\SimExecution\Domain\Exceptions\ProjectNotAvailableException;
use Src\SimExecution\Domain\Exceptions\RoleNotAvailableForProjectException;
use Src\SimExecution\Domain\RoleEnrolment\RoleEnrolmentRepository;
use Src\SimExecution\Domain\Session\LearnerSession;
use Src\SimExecution\Domain\Session\LearnerSessionId;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;

/**
 * Implements the "Project Onboarding" step of the execution flow (Integration
 * Spec §12, step 0): verifies role eligibility against the learner's declared
 * experience and the project's specialization tags, then creates the
 * RoleEnrolment + LearnerSession together. All checks are re-run here even
 * though the catalogue UI already filters ineligible roles — the UI filter
 * is convenience, this is the actual gate (Business Logic §2.2: "A learner
 * cannot manually override role access restrictions").
 */
final class EnrolInProjectHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSessionRepository $sessions,
        private readonly RoleEnrolmentRepository $roleEnrolments,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(EnrolInProjectCommand $command): void
    {
        $learner = $this->learners->findByUserId($command->userId);
        if ($learner === null) {
            throw new LearnerNotFoundException();
        }

        $project = $this->queryBus->ask(new GetProjectQuery($command->projectId));
        if ($project === null || ! $project->isPublished() || ! $project->isActive()) {
            throw new ProjectNotAvailableException();
        }

        /** @var RoleDefinitionSummary[] $roles */
        $roles = $this->queryBus->ask(new ListRoleDefinitionsQuery());
        $role = null;
        foreach ($roles as $candidate) {
            if ($candidate->id === $command->roleId) {
                $role = $candidate;
                break;
            }
        }

        if ($role === null || empty(array_intersect($project->specializationTags(), $role->specializationTags))) {
            throw new RoleNotAvailableForProjectException();
        }

        $yearsExperience = $learner->yearsExperience() ?? 0;
        if ($yearsExperience < $role->minYearsExperience) {
            throw new InsufficientExperienceException();
        }

        if ($this->sessions->findByLearnerAndProject($learner->id(), $project->id()) !== null) {
            throw new AlreadyEnrolledInProjectException();
        }

        $session = null;

        DB::transaction(function () use ($learner, $project, $role, &$session) {
            $roleEnrolmentId = $this->roleEnrolments->create($learner->id(), $role->id, $project->id());

            $session = LearnerSession::begin(
                id:              LearnerSessionId::generate(),
                learnerId:       $learner->id(),
                projectId:       $project->id(),
                roleEnrolmentId: $roleEnrolmentId,
            );

            $this->sessions->save($session);
        });

        foreach ($session->releaseEvents() as $event) {
            event($event);
        }
    }
}
