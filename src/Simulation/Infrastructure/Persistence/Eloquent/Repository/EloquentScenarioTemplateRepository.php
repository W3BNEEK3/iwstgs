<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper\ScenarioTemplateMapper;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioReferenceMaterialModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;

final class EloquentScenarioTemplateRepository implements ScenarioTemplateRepository
{
    public function __construct(private readonly ScenarioTemplateMapper $mapper) {}

    public function save(ScenarioTemplate $scenario): void
    {
        DB::transaction(function () use ($scenario) {
            ScenarioTemplateModel::updateOrCreate(
                ['id' => $scenario->id()],
                $scenario->toPrimitives(),
            );

            // Reconcile children: keep those present, delete the rest.
            $materials = $scenario->referenceMaterials();
            $keepIds = array_map(fn ($m) => $m->id(), $materials);

            ScenarioReferenceMaterialModel::where('scenario_id', $scenario->id())
                ->whereNotIn('id', $keepIds ?: ['__none__'])
                ->delete();

            foreach ($materials as $m) {
                ScenarioReferenceMaterialModel::updateOrCreate(
                    ['id' => $m->id()],
                    ['scenario_id' => $scenario->id()] + $m->toPrimitives(),
                );
            }
        });
    }

    public function findById(ScenarioTemplateId $id): ?ScenarioTemplate
    {
        $m = ScenarioTemplateModel::with('referenceMaterials')->find((string) $id);
        return $m ? $this->mapper->toEntity($m) : null;
    }

    /** @return ScenarioTemplate[] */
    public function findByProject(string $projectId): array
    {
        return ScenarioTemplateModel::with('referenceMaterials')
            ->where('project_id', $projectId)
            ->orderBy('sequence_order')
            ->get()
            ->map(fn ($m) => $this->mapper->toEntity($m))
            ->all();
    }

    public function nextSequenceOrder(string $projectId): int
    {
        return (int) ScenarioTemplateModel::where('project_id', $projectId)->max('sequence_order') + 1;
    }
}
