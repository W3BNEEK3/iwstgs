<?php
namespace Src\Simulation\Application\Query\ListBacklogItemTemplatesByProject;

use Src\Simulation\Domain\Backlog\BacklogItemTemplateRepository;

final class ListBacklogItemTemplatesByProjectHandler
{
    public function __construct(private readonly BacklogItemTemplateRepository $repository) {}

    /** @return \Src\Simulation\Domain\Backlog\BacklogItemTemplateSummary[] */
    public function handle(ListBacklogItemTemplatesByProjectQuery $query): array
    {
        return $this->repository->allForProject($query->projectId);
    }
}
