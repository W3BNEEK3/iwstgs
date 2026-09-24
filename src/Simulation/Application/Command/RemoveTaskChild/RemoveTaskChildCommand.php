<?php
namespace Src\Simulation\Application\Command\RemoveTaskChild;

final class RemoveTaskChildCommand
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $collection,   // 'deliverables'|'cacVariants'|'dependencies'|'anchors'|'prompts'
        public readonly string $childId,
    ) {}
}
