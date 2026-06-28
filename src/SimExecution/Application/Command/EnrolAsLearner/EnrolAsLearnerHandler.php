<?php
namespace Src\SimExecution\Application\Command\EnrolAsLearner;

use Src\SimExecution\Domain\Exceptions\AlreadyEnrolledException;
use Src\SimExecution\Domain\Enrollment\EntryCategory;
use Src\SimExecution\Domain\Enrollment\Learner;
use Src\SimExecution\Domain\Enrollment\LearnerId;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;

final class EnrolAsLearnerHandler
{
    public function __construct(
        private readonly LearnerRepository $repository,
        
    ) {}

    public function handle(EnrolAsLearnerCommand $command) 
    {
        if ($this->repository->existsForUser($command->userId)) {
            throw new AlreadyEnrolledException();
        }

        $learner = Learner::enrol(
            id:              LearnerId::generate(),
            userId:          $command->userId,
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