<?php
namespace Src\Submission\Application\Command\ResumePendingSubmissions;

use Src\Submission\Application\Service\CiFinaliser;
use Src\Submission\Domain\Submission\SubmissionPackageRepository;

final class ResumePendingSubmissionsHandler
{
    public function __construct(
        private readonly SubmissionPackageRepository $packages,
        private readonly CiFinaliser $finaliser,
    ) {}

    public function handle(ResumePendingSubmissionsCommand $command): void
    {
        foreach ($this->packages->pendingCiIds($command->learnerSessionId, $command->commitSha) as $submissionId) {
            $this->finaliser->finalise($submissionId);
        }
    }
}
