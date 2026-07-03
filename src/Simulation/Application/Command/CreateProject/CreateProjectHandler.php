<?php
namespace Src\Simulation\Application\Command\CreateProject;

use Src\Simulation\Domain\Project\ProjectTemplate;
use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;

final class CreateProjectHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    public function handle(CreateProjectCommand $command): void
    {
        $project = ProjectTemplate::create(
            id:                 ProjectTemplateId::generate(),
            title:              $command->title,
            projectType:        $command->projectType,
            businessContext:    $command->businessContext,
            specializationTags: $command->specializationTags,
            difficultyLevel:    $command->difficultyLevel,
            tagline:            $command->tagline,
            businessDomain:     $command->businessDomain,
            organisationId:     $command->organisationId,
        );

        $this->repository->save($project);

        foreach ($project->releaseEvents() as $event) {
            event($event);
        }
    }
}
