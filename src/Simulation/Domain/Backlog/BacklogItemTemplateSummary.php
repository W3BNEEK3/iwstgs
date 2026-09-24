<?php
namespace Src\Simulation\Domain\Backlog;

final class BacklogItemTemplateSummary
{
    /**
     * @param string[] $roleTags
     * @param string[] $dependencyItemIds
     */
    public function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $defaultPriority,
        public readonly array $roleTags,
        public readonly ?string $taskId,
        public readonly array $dependencyItemIds,
        public readonly int $displayOrder,
    ) {}
}
