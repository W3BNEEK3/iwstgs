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
                name: $m->name,
                shortLabel: $m->short_label,
                coreQuestion: $m->core_question,
                observableIndicators: $m->observable_indicators ?? [],
                sequenceOrder: $m->sequence_order,
            ))
            ->all();
    }

    public function nextSequenceOrder(): int
    {
        return (int) (CompetenceDimensionModel::max('sequence_order') ?? 0) + 1;
    }

    public function create(
        string $id,
        string $name,
        string $shortLabel,
        string $coreQuestion,
        array $observableIndicators,
        int $sequenceOrder,
    ): void {
        CompetenceDimensionModel::create([
            'id' => $id,
            'name' => $name,
            'short_label' => $shortLabel,
            'core_question' => $coreQuestion,
            'observable_indicators' => $observableIndicators,
            'sequence_order' => $sequenceOrder,
        ]);
    }
}
