<?php
namespace Src\Reporting\Application\Query\ListTaskPerformance;

use Src\EvalEngine\Application\Query\ListTaskPerformance\ListTaskPerformanceQuery as EvalEngineListTaskPerformanceQuery;
use Src\EvalEngine\Domain\Evaluation\TaskPerformanceSummary;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;

final class ListTaskPerformanceHandler
{
    public function __construct(private readonly QueryBus $queryBus) {}

    /** @return TaskPerformanceView[] */
    public function handle(ListTaskPerformanceQuery $query): array
    {
        /** @var TaskPerformanceSummary[] $performance */
        $performance = $this->queryBus->ask(new EvalEngineListTaskPerformanceQuery());

        return array_map(function (TaskPerformanceSummary $p) {
            $task = $this->queryBus->ask(new GetTaskQuery($p->taskId));

            return new TaskPerformanceView(
                taskId:        $p->taskId,
                taskTitle:     $task?->toPrimitives()['title'] ?? '(task unavailable)',
                attemptCount:  $p->attemptCount,
                passCount:     $p->passCount,
                passRate:      $p->attemptCount > 0 ? round($p->passCount / $p->attemptCount * 100, 1) : 0.0,
                tierCounts:    $p->tierCounts,
            );
        }, $performance);
    }
}
