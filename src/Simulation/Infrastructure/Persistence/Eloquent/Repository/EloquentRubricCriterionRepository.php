<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use Src\Simulation\Domain\Rubric\RubricCriterion;
use Src\Simulation\Domain\Rubric\RubricCriterionId;
use Src\Simulation\Domain\Rubric\RubricCriterionRepository;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper\RubricCriterionMapper;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricCriterionModel;

final class EloquentRubricCriterionRepository implements RubricCriterionRepository
{
    public function __construct(private readonly RubricCriterionMapper $mapper) {}

    public function save(RubricCriterion $criterion): void
    {
        RubricCriterionModel::updateOrCreate(
            ['id' => $criterion->id()],
            $criterion->toPrimitives()
        );
    }

    public function findById(RubricCriterionId $id): ?RubricCriterion
    {
        $m = RubricCriterionModel::find((string) $id);
        return $m ? $this->mapper->toEntity($m) : null;
    }

    /** @return RubricCriterion[] */
    public function findByTask(string $taskId): array
    {
        return RubricCriterionModel::where('task_id', $taskId)
            ->get()
            ->map(fn ($m) => $this->mapper->toEntity($m))
            ->all();
    }

    public function remove(RubricCriterionId $id): void
    {
        RubricCriterionModel::destroy((string) $id);
    }

    public function rubricSetIdForTask(string $taskId): ?string
    {
        return DB::table('tasks')
            ->join('scenario_templates', 'tasks.scenario_id', '=', 'scenario_templates.id')
            ->join('rubric_sets', 'scenario_templates.project_id', '=', 'rubric_sets.project_id')
            ->where('tasks.id', $taskId)
            ->value('rubric_sets.id');
    }
}
