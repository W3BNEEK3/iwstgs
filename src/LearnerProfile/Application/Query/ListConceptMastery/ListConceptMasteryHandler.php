<?php
namespace Src\LearnerProfile\Application\Query\ListConceptMastery;

use Src\LearnerProfile\Domain\ConceptMastery\ConceptMasteryRepository;
use Src\LearnerProfile\Domain\ConceptMastery\ConceptMasterySummary;

final class ListConceptMasteryHandler
{
    public function __construct(private readonly ConceptMasteryRepository $conceptMastery) {}

    /** @return ConceptMasterySummary[] */
    public function handle(ListConceptMasteryQuery $query): array
    {
        return $this->conceptMastery->findAllForLearner($query->learnerId);
    }
}
