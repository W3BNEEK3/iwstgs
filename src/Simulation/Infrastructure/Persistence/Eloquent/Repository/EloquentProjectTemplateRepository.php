<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use Src\Shared\Infrastructure\Id\UuidGenerator;
use Src\Simulation\Domain\Project\ProjectTemplate;
use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper\ProjectTemplateMapper;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricSetModel;

final class EloquentProjectTemplateRepository implements ProjectTemplateRepository
{
    public function __construct(private readonly ProjectTemplateMapper $mapper) {}

    public function save(ProjectTemplate $project): void
    {
        DB::transaction(function () use ($project) {
            // Upsert the project row. updateOrCreate handles both create and edit:
            // it matches on the primary key and inserts or updates accordingly.
            $data = $project->toPrimitives();
            $model = ProjectTemplateModel::updateOrCreate(['id' => $data['id']], $data);

            // A project's rubric set is part of the aggregate — created with it,
            // exactly once. firstOrCreate makes this idempotent on edits.
            $rubric = RubricSetModel::firstOrCreate(
                ['project_id' => $model->id],
                ['id' => (string) UuidGenerator::generate(), 'version' => '1.0'],
            );

            // Keep the convenience back-reference in sync.
            if ($model->rubric_set_id !== $rubric->id) {
                $model->rubric_set_id = $rubric->id;
                $model->save();
            }
        });
    }

    public function findById(ProjectTemplateId $id): ?ProjectTemplate
    {
        $model = ProjectTemplateModel::find((string) $id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    /** @return ProjectTemplate[] */
    public function all(): array
    {
        return ProjectTemplateModel::orderByDesc('created_at')
            ->get()
            ->map(fn (ProjectTemplateModel $m) => $this->mapper->toEntity($m))
            ->all();
    }
}
