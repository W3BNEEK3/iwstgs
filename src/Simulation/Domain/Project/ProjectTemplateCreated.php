<?php
namespace Src\Simulation\Domain\Project;

use Src\Shared\Domain\DomainEvent;

final class ProjectTemplateCreated extends DomainEvent
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $title,
    ) {
        parent::__construct();
    }
}
