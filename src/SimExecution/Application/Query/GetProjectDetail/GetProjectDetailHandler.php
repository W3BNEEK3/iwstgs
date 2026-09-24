<?php
namespace Src\SimExecution\Application\Query\GetProjectDetail;

use Src\Competency\Application\Query\ListRoleDefinitions\ListRoleDefinitionsQuery;
use Src\Competency\Domain\Role\RoleDefinitionSummary;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;

final class GetProjectDetailHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSessionRepository $sessions,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(GetProjectDetailQuery $query): ?ProjectDetailView
    {
        $project = $this->queryBus->ask(new GetProjectQuery($query->projectId));
        if ($project === null || ! $project->isPublished() || ! $project->isActive()) {
            return null;
        }

        $learner = $query->userId !== null ? $this->learners->findByUserId($query->userId) : null;
        $yearsExperience = $learner?->yearsExperience() ?? 0;

        /** @var RoleDefinitionSummary[] $allRoles */
        $allRoles = $this->queryBus->ask(new ListRoleDefinitionsQuery());
        $projectTags = $project->specializationTags();

        $roleOptions = [];
        foreach ($allRoles as $role) {
            if (empty(array_intersect($projectTags, $role->specializationTags))) {
                continue; // not offered on this project
            }

            $roleOptions[] = new RoleOption(
                roleId:              $role->id,
                title:               $role->title,
                minYearsExperience:  $role->minYearsExperience,
                isLeadRole:          $role->isLeadRole,
                isEligible:          $yearsExperience >= $role->minYearsExperience,
            );
        }

        $session = $learner !== null
            ? $this->sessions->findByLearnerAndProject($learner->id(), $project->id())
            : null;

        return new ProjectDetailView(
            id:              $project->id(),
            title:           $project->title(),
            tagline:         $project->tagline(),
            businessContext: $project->businessContext(),
            difficultyLevel: $project->difficultyLevel(),
            roleOptions:     $roleOptions,
            isEnrolled:      $session !== null,
            sessionStatus:   $session?->status()->value,
        );
    }
}
