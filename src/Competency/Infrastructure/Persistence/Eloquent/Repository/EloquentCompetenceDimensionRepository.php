<?php
namespace Src\Competency\Infrastructure\Persistence\Eloquent\Repository;

use Src\Competency\Domain\Dimension\CompetenceDimensionRepository;
use Src\Competency\Domain\Dimension\CompetenceDimensionSummary;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\CompetenceDimensionModel;

final class EloquentCompetenceDimensionRepository implements CompetenceDimensionRepository
{
    /** @return CompetenceDimensionSummary[] */
    public function all(): array
    {
        return CompetenceDimensionModel::orderBy('sequence_order')
            ->get()
            ->map(fn (CompetenceDimensionModel $m) => new CompetenceDimensionSummary(
                id: $m->id,
                shortLabel: $m->short_label,
                coreQuestion: $m->core_question,
            ))
            ->all();
    }
}
