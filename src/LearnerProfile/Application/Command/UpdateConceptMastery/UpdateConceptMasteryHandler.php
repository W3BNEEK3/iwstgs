<?php
namespace Src\LearnerProfile\Application\Command\UpdateConceptMastery;

use Src\LearnerProfile\Domain\ConceptMastery\ConceptMasteryRepository;

final class UpdateConceptMasteryHandler
{
    public function __construct(private readonly ConceptMasteryRepository $conceptMastery) {}

    public function handle(UpdateConceptMasteryCommand $command): void
    {
        $this->conceptMastery->recordEncounter($command->learnerId, $command->conceptId, $command->met);
    }
}
