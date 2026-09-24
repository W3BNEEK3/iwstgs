<?php
namespace Src\SimExecution\Application\Command\EnrolAsLearner;

use Src\Identity\Domain\User\UserId;
use Src\Identity\Domain\User\UserRepository;
use Src\SimExecution\Domain\Exceptions\AlreadyEnrolledException;
use Src\SimExecution\Domain\Enrollment\EntryCategory;
use Src\SimExecution\Domain\Enrollment\Learner;
use Src\SimExecution\Domain\Enrollment\LearnerId;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;

final class EnrolAsLearnerHandler
{
    public function __construct(
        private readonly LearnerRepository $repository,
        private readonly UserRepository $users,
    ) {}

    public function handle(EnrolAsLearnerCommand $command)
    {
        if ($this->repository->existsForUser($command->userId)) {
            throw new AlreadyEnrolledException();
        }

        // learners.fullname is sourced from the account rather than re-collected
        // on the enrolment form — the learner already provided it at registration.
        $user = $this->users->findById(UserId::fromString($command->userId));

        $learner = Learner::enrol(
            id:              LearnerId::generate(),
            userId:          $command->userId,
            fullname:        $user->fullname(),
            entryCategory:   EntryCategory::from($command->entryCategory),
            yearsExperience: $command->yearsExperience,
            organisationId:  $command->organisationId
        );

        $this->repository->save($learner);

        foreach ($learner->releaseEvents() as $event) {
            event($event);
        }
    }
}