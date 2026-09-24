<?php
namespace Src\Competency\Application\Query\ListCompetenceDimensions;

use Src\Competency\Domain\Dimension\CompetenceDimensionRepository;

final class ListCompetenceDimensionsHandler
{
    public function __construct(private readonly CompetenceDimensionRepository $repository) {}

    /** @return \Src\Competency\Domain\Dimension\CompetenceDimensionSummary[] */
    public function handle(ListCompetenceDimensionsQuery $query): array
    {
        return $this->repository->all();
    }
}
