<?php
namespace Src\SimExecution\Application\Command\EnrolAsLearner;

use Src\SimExecution\Domain\Exceptions\AlreadyEnrolledException;
use Src\SimExecution\Domain\Enrollment\Entrycategory;
use Src\SimExecution\Domain\Enrollment\Learner;
use Src\SimExecution\Domain\Enrollment\LearnerId;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\Shared\Infrastructure\Id\UuidGenerator;

final class EnrolAsLearnerHandler
{
    public function __construct(
        private readonly LearnerRepository $repository,
        private readonly UuidGenerator $uuidGenerator    
    ){}
            
            public function handle(EnrolAsLearnerCommand $command)
        {
            if ($repositoy->existsForUser($command->userId)){
                throw new AlreadyEnrolledException();
            }
            
            Learner::enrol(
               id:   LearnerId::fromString($this->uuidGenerator),
               userId: $command->userId,
               
            );
        }            
}
