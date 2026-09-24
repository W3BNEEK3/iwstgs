<?php
namespace Src\LearnerProfile\Application\Command\EscalateCacComplexity;

use Src\LearnerProfile\Domain\Profile\LearnerProfileRepository;

final class EscalateCacComplexityHandler
{
    public function __construct(private readonly LearnerProfileRepository $profiles) {}

    public function handle(EscalateCacComplexityCommand $command): void
    {
        $profile = $this->profiles->findByLearnerId($command->learnerId);
        if ($profile === null) {
            return;
        }

        $profile->escalateComplexityForScenarioTransition();
        $this->profiles->save($profile);

        foreach ($profile->releaseEvents() as $event) {
            event($event);
        }
    }
}
