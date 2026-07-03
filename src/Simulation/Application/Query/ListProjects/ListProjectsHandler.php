<?php
namespace Src\Simulation\Application\Query\ListProjects;

use Src\Simulation\Domain\Project\ProjectTemplateRepository;

final class ListProjectsHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    /** @return \Src\Simulation\Domain\Project\ProjectTemplate[] */
    public function handle(ListProjectsQuery $query): array
    {
        return $this->repository->all();
    }
}
