<?php
namespace Src\Submission\Application\Query\ListSubmissionsForLearner;

use Src\Submission\Domain\Submission\SubmissionPackageRepository;
use Src\Submission\Domain\Submission\SubmissionTrajectoryEntry;

final class ListSubmissionsForLearnerHandler
{
    public function __construct(private readonly SubmissionPackageRepository $packages) {}

    /** @return SubmissionTrajectoryEntry[] */
    public function handle(ListSubmissionsForLearnerQuery $query): array
    {
        return $this->packages->findAllForLearner($query->learnerId);
    }
}
