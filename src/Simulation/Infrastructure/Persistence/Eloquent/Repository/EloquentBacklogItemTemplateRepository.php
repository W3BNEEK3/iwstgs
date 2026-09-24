<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Repository;

use Src\Simulation\Domain\Backlog\BacklogItemTemplateRepository;
use Src\Simulation\Domain\Backlog\BacklogItemTemplateSummary;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\BacklogItemTemplateModel;

final class EloquentBacklogItemTemplateRepository implements BacklogItemTemplateRepository
{
    public function allForProject(string $projectId): array
    {
        return BacklogItemTemplateModel::where('project_id', $projectId)
            ->orderBy('display_order')
            ->get()
            ->map(fn (BacklogItemTemplateModel $m) => new BacklogItemTemplateSummary(
                id:                $m->id,
                projectId:         $m->project_id,
                title:             $m->title,
                description:       $m->description,
                defaultPriority:   $m->default_priority,
                roleTags:          $m->role_tags ?? [],
                taskId:            $m->task_id,
                dependencyItemIds: $m->dependency_item_ids ?? [],
                displayOrder:      $m->display_order,
            ))
            ->all();
    }

    public function findById(string $id): ?BacklogItemTemplateSummary
    {
        $m = BacklogItemTemplateModel::find($id);
        if ($m === null) {
            return null;
        }

        return new BacklogItemTemplateSummary(
            id:                $m->id,
            projectId:         $m->project_id,
            title:             $m->title,
            description:       $m->description,
            defaultPriority:   $m->default_priority,
            roleTags:          $m->role_tags ?? [],
            taskId:            $m->task_id,
            dependencyItemIds: $m->dependency_item_ids ?? [],
            displayOrder:      $m->display_order,
        );
    }
}
