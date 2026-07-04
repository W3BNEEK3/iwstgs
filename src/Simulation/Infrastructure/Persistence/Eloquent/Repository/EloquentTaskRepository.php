<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use Src\Simulation\Domain\Task\Task;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper\TaskMapper;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskCacVariantModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskDependencyModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskExpectedDeliverableModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskGuidancePromptModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskKnowledgeAnchorModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

final class EloquentTaskRepository implements TaskRepository
{
    public function __construct(private readonly TaskMapper $mapper) {}

    public function save(Task $task): void
    {
        DB::transaction(function () use ($task) {
            TaskModel::updateOrCreate(['id' => $task->id()], $task->toPrimitives());

            $this->sync(TaskExpectedDeliverableModel::class, $task->id(), $task->expectedDeliverables());
            $this->sync(TaskCacVariantModel::class,          $task->id(), $task->cacVariants());
            $this->sync(TaskDependencyModel::class,          $task->id(), $task->dependencies());
            $this->sync(TaskKnowledgeAnchorModel::class,     $task->id(), $task->knowledgeAnchors());
            $this->sync(TaskGuidancePromptModel::class,      $task->id(), $task->guidancePrompts());
        });
    }

    /**
     * Upsert the children present; delete the ones removed.
     * Factored out so the same logic isn't copy-pasted five times.
     */
    private function sync(string $modelClass, string $taskId, array $children): void
    {
        $keepIds = array_map(fn ($c) => $c->id(), $children);

        $modelClass::where('task_id', $taskId)
            ->whereNotIn('id', $keepIds ?: ['__none__'])
            ->delete();

        foreach ($children as $child) {
            $modelClass::updateOrCreate(
                ['id' => $child->id()],
                ['task_id' => $taskId] + $child->toPrimitives(),
            );
        }
    }

    public function findById(TaskId $id): ?Task
    {
        $m = TaskModel::with([
            'expectedDeliverables',
            'cacVariants',
            'dependencies',
            'knowledgeAnchors',
            'guidancePrompts',
        ])->find((string) $id);

        return $m ? $this->mapper->toEntity($m) : null;
    }

    /** @return Task[] */
    public function findByScenario(string $scenarioId): array
    {
        return TaskModel::with([
            'expectedDeliverables',
            'cacVariants',
            'dependencies',
            'knowledgeAnchors',
            'guidancePrompts',
        ])
            ->where('scenario_id', $scenarioId)
            ->orderBy('sequence_order')
            ->get()
            ->map(fn ($m) => $this->mapper->toEntity($m))
            ->all();
    }

    public function nextSequenceOrder(string $scenarioId): int
    {
        return (int) TaskModel::where('scenario_id', $scenarioId)->max('sequence_order') + 1;
    }
}
