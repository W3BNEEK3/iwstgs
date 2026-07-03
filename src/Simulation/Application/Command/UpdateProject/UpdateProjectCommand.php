<?php
namespace Src\Simulation\Application\Command\UpdateProject;

final class UpdateProjectCommand
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $title,
        public readonly string $businessContext,
    ) {}
}
