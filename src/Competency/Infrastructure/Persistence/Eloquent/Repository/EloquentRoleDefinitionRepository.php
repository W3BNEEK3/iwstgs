<?php
namespace Src\Competency\Infrastructure\Persistence\Eloquent\Repository;

use Src\Competency\Domain\Role\RoleDefinitionRepository;
use Src\Competency\Domain\Role\RoleDefinitionSummary;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\RoleDefinitionModel;

final class EloquentRoleDefinitionRepository implements RoleDefinitionRepository
{
    /** @return RoleDefinitionSummary[] */
    public function all(): array
    {
        return RoleDefinitionModel::all()
            ->map(fn (RoleDefinitionModel $m) => new RoleDefinitionSummary(
                id: $m->id,
                title: $m->title,
                specializationTags: $m->specialization_tags ?? [],
                minYearsExperience: $m->min_years_experience,
                isLeadRole: $m->is_lead_role,
                dimensionWeights: $m->dimension_weights ?? [],
                dimensionThresholds: $m->dimension_thresholds ?? [],
            ))
            ->all();
    }
}
