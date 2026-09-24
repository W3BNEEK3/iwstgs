<?php
namespace Src\SimExecution\Application\Query\GetLearnerIdForUser;

use Src\SimExecution\Domain\Enrollment\LearnerRepository;

final class GetLearnerIdForUserHandler
{
    public function __construct(private readonly LearnerRepository $learners) {}

    public function handle(GetLearnerIdForUserQuery $query): ?string
    {
        return $this->learners->findByUserId($query->userId)?->id();
    }
}
