<?php
namespace Src\Simulation\Application\Command\UpdateProject;

use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;
use Src\Simulation\Domain\Exceptions\ProjectNotFoundException;

final class UpdateProjectHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    public function handle(UpdateProjectCommand $command): void
    {
        $project = $this->repository->findById(ProjectTemplateId::fromString($command->projectId));
        if ($project === null) {
            throw new ProjectNotFoundException($command->projectId);
        }

        $project->rename($command->title);
        $project->updateBusinessContext($command->businessContext);

        $this->repository->save($project);
    }
}
