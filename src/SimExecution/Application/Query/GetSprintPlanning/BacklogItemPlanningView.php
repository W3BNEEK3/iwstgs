<?php
namespace Src\SimExecution\Application\Query\GetSprintPlanning;

final class BacklogItemPlanningView
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $priority,
        public readonly bool $isInjected,
    ) {}
}
