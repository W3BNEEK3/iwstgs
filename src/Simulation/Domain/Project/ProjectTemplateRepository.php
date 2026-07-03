<?php
namespace Src\Simulation\Domain\Project;

interface ProjectTemplateRepository
{
    public function save(ProjectTemplate $project): void;

    public function findById(ProjectTemplateId $id): ?ProjectTemplate;

    /** @return ProjectTemplate[] */
    public function all(): array;
}
