<?php
namespace Src\LearnerProfile\Application\Command\AdjustCacFromSubmission;

use Src\LearnerProfile\Domain\Profile\LearnerProfileRepository;

final class AdjustCacFromSubmissionHandler
{
    public function __construct(private readonly LearnerProfileRepository $profiles) {}

    public function handle(AdjustCacFromSubmissionCommand $command): void
    {
        $profile = $this->profiles->findByLearnerId($command->learnerId);
        if ($profile === null) {
            return;
        }

        $profile->adjustCacFromSubmission($command->passed);
        $this->profiles->save($profile);

        foreach ($profile->releaseEvents() as $event) {
            event($event);
        }
    }
}
