<?php

namespace Src\LearnerProfile\Application\Command\BootstrapLearnerProfile;

use Src\Competency\Application\Query\ListCompetenceDimensions\ListCompetenceDimensionsQuery;
use Src\Competency\Domain\Dimension\CompetenceDimensionSummary;
use Src\LearnerProfile\Domain\Profile\LearnerProfile;
use Src\LearnerProfile\Domain\Profile\LearnerProfileId;
use Src\LearnerProfile\Domain\Profile\LearnerProfileRepository;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Infrastructure\Id\UuidGenerator;

final class BootstrapLearnerProfileHandler
{
    public function __construct(
        private readonly LearnerProfileRepository $repository,
        private readonly QueryBus $queryBus,
        private readonly UuidGenerator $uuidGenerator,
    ) {}

    public function handle(BootstrapLearnerProfileCommand $command): void
    {
        // Defensive idempotency. EnrolAsLearnerHandler already guards against a person
        // enrolling as a learner twice, so this should never actually be true — but an
        // event listener is a second entry point into this logic, reachable independently
        // of that first guard, and a second entry point earns its own check rather than
        // trusting the first one silently.
        if ($this->repository->existsForLearner($command->learnerId)) {
            return;
        }

        /** @var CompetenceDimensionSummary[] $dimensions */
        $dimensions = $this->queryBus->ask(new ListCompetenceDimensionsQuery());
        $dimensionIds = array_map(
            static fn (CompetenceDimensionSummary $d) => $d->id,
            $dimensions,
        );

        $profile = LearnerProfile::bootstrap(
            id:           LearnerProfileId::fromString($this->uuidGenerator->generate()),
            learnerId:    $command->learnerId,
            dimensionIds: $dimensionIds,
        );

        $this->repository->save($profile);

        foreach ($profile->releaseEvents() as $event) {
            event($event);
        }
    }
}
