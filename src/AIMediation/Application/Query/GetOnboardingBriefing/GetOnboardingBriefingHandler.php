<?php
namespace Src\AIMediation\Application\Query\GetOnboardingBriefing;

use Src\AIMediation\Application\Service\GenerateOnboardingBriefingService;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Simulation\Domain\Project\ProjectTemplate;

final class GetOnboardingBriefingHandler
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly GenerateOnboardingBriefingService $briefingService,
    ) {}

    public function handle(GetOnboardingBriefingQuery $query): ?string
    {
        /** @var ProjectTemplate|null $project */
        $project = $this->queryBus->ask(new GetProjectQuery($query->projectId));
        if ($project === null) {
            return null;
        }

        return $this->briefingService->ensureGenerated($project);
    }
}
