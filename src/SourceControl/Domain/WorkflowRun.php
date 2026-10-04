<?php
namespace Src\SourceControl\Domain;

final class WorkflowRun
{
    public function __construct(
        public readonly int $id,
        public readonly string $status,       // queued | in_progress | completed
        public readonly ?string $conclusion,  // success | failure | cancelled | … (when completed)
        public readonly string $htmlUrl,
    ) {}

    public function isFinished(): bool
    {
        return $this->status === 'completed';
    }
}
