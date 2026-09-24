<?php
namespace Src\Simulation\Application\Query\GetProject;

use Src\Simulation\Domain\Project\ProjectTemplate;
use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;

final class GetProjectHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    public function handle(GetProjectQuery $query): ?ProjectTemplate
    {
        return $this->repository->findById(ProjectTemplateId::fromString($query->projectId));
    }
}
