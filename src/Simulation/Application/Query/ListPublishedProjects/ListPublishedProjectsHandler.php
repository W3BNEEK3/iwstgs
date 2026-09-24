<?php
namespace Src\Simulation\Application\Query\ListPublishedProjects;

use Src\Simulation\Domain\Project\ProjectTemplateRepository;

/**
 * The learner-facing catalogue — published, active projects only. Kept
 * separate from ListProjectsQuery, which the admin project list uses and
 * deliberately shows everything (including drafts) so authors can find and
 * publish them.
 */
final class ListPublishedProjectsHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    /** @return \Src\Simulation\Domain\Project\ProjectTemplate[] */
    public function handle(ListPublishedProjectsQuery $query): array
    {
        return array_values(array_filter(
            $this->repository->all(),
            static fn ($project) => $project->isPublished() && $project->isActive(),
        ));
    }
}
