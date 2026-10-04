<?php
namespace Src\SimExecution\Application\Query\GetBuildProgress;

final class BuildProgressView
{
    /**
     * @param array<int, array{id: string, title: string, scenario_id: string, scenario_title: string, status: string, attempts: int}> $milestones
     *   every milestone of the project in build order; status: passed | current | locked
     * @param string[] $passedTaskIds milestones passed in this session
     */
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly ?string $currentScenarioId,
        public readonly ?string $currentMilestoneId,
        public readonly array $milestones,
        public readonly array $passedTaskIds,
    ) {}

    public function isCurrent(string $taskId): bool
    {
        return $this->currentMilestoneId === $taskId;
    }

    public function isMilestone(string $taskId): bool
    {
        return in_array($taskId, array_column($this->milestones, 'id'), true);
    }
}
