<?php
namespace Src\SimExecution\Application\Query\GetLearnerName;

use Src\SimExecution\Domain\Enrollment\LearnerId;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;

final class GetLearnerNameHandler
{
    public function __construct(private readonly LearnerRepository $learners) {}

    public function handle(GetLearnerNameQuery $query): ?string
    {
        return $this->learners->findById(LearnerId::fromString($query->learnerId))?->fullname();
    }
}
