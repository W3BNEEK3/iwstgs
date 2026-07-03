<?php
namespace Src\Simulation\Application\Command\PublishProject;

use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;
use Src\Simulation\Domain\Exceptions\ProjectNotFoundException;

final class PublishProjectHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    public function handle(PublishProjectCommand $command): void
    {
        $project = $this->repository->findById(ProjectTemplateId::fromString($command->projectId));
        if ($project === null) {
            throw new ProjectNotFoundException($command->projectId);
        }

        $command->publish ? $project->publish() : $project->unpublish();
        $this->repository->save($project);
    }
}
