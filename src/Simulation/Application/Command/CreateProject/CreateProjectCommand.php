<?php
namespace Src\Simulation\Application\Command\CreateProject;

final class CreateProjectCommand
{
    public function __construct(
        public readonly string  $title,
        public readonly string  $projectType,
        public readonly string  $businessContext,
        public readonly array   $specializationTags,
        public readonly string  $difficultyLevel,
        public readonly ?string $tagline = null,
        public readonly ?string $businessDomain = null,
        public readonly ?string $organisationId = null,
    ) {}
}
