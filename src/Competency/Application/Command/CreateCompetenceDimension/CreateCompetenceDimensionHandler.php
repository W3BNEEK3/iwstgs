<?php
namespace Src\Competency\Application\Command\CreateCompetenceDimension;

use Illuminate\Support\Str;
use Src\Competency\Domain\Dimension\CompetenceDimensionRepository;

final class CreateCompetenceDimensionHandler
{
    public function __construct(private readonly CompetenceDimensionRepository $repository) {}

    public function handle(CreateCompetenceDimensionCommand $command): void
    {
        $existingIds = array_map(fn ($d) => $d->id, $this->repository->all());

        // competence_dimensions.id is varchar(20), duplicated as an exact-match
        // FK column type on dimension_scores/dimension_evaluations/gap_flags/
        // rubric_criteria — widening it is a real migration, not something a
        // UI form should force, so the generated id has to fit that limit,
        // suffix included.
        $prefix = 'dim_';
        $suffixReserve = 3; // room for "_99" if a collision runs that far
        $slugSource = $command->shortLabel !== '' ? $command->shortLabel : $command->name;
        $slug = rtrim(substr(Str::slug($slugSource, '_'), 0, 20 - strlen($prefix) - $suffixReserve), '_');
        $baseId = $prefix . $slug;

        $id = $baseId;
        $suffix = 2;
        while (in_array($id, $existingIds, true)) {
            $id = $baseId . '_' . $suffix;
            $suffix++;
        }

        $this->repository->create(
            id: $id,
            name: $command->name,
            shortLabel: $command->shortLabel,
            coreQuestion: $command->coreQuestion,
            observableIndicators: $command->observableIndicators,
            sequenceOrder: $this->repository->nextSequenceOrder(),
        );
    }
}
