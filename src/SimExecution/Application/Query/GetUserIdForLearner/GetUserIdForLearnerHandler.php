<?php
namespace Src\SimExecution\Application\Query\GetUserIdForLearner;

use Src\SimExecution\Domain\Enrollment\LearnerId;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;

/** The reverse of GetLearnerIdForUser: learner-scoped events (evaluations) → the user account. */
final class GetUserIdForLearnerHandler
{
    public function __construct(private readonly LearnerRepository $learners) {}

    public function handle(GetUserIdForLearnerQuery $query): ?string
    {
        return $this->learners->findById(LearnerId::fromString($query->learnerId))?->userId();
    }
}
