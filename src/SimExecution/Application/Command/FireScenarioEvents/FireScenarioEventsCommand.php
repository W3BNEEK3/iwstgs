<?php
namespace Src\SimExecution\Application\Command\FireScenarioEvents;

final class FireScenarioEventsCommand
{
    /**
     * @param string $trigger scenario_start | after_task
     * @param string|null $taskId for after_task: the task that was just passed
     */
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $trigger,
        public readonly ?string $taskId = null,
    ) {}
}
