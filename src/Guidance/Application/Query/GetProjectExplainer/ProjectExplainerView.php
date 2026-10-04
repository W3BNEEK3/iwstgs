<?php
namespace Src\Guidance\Application\Query\GetProjectExplainer;

final class ProjectExplainerView
{
    /**
     * @param array<int, array{title: string, blurb: string}> $scenarios
     * @param string[] $skills skill labels, most-assessed first
     * @param string[] $stack
     * @param array<int, array{title: string, focus: string[]}> $roles
     */
    public function __construct(
        public readonly string $summary,
        public readonly bool $isAiWritten,
        public readonly array $scenarios,
        public readonly array $skills,
        public readonly int $coreTaskCount,
        public readonly int $estimatedHours,
        public readonly array $stack,
        public readonly array $roles,
        /** v2 Build project: chapters and milestones in the learner's own repository, no roles or sprints. */
        public readonly bool $isBuild = false,
    ) {}
}
