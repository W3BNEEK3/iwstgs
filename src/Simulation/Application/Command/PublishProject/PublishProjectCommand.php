<?php
namespace Src\Simulation\Application\Command\PublishProject;

final class PublishProjectCommand
{
    public function __construct(
        public readonly string $projectId,
        public readonly bool   $publish,
    ) {}
}
