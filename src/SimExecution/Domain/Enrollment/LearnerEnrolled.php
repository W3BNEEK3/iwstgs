<?php
namespace Src\SimExecution\Domain\Enrollment;

use Src\Shared\Domain\DomainEvent;

final class LearnerEnrolled extends DomainEvent
{
    public function __construct(
        public readonly string  $learnerId,
        public readonly string  $userId,
        public readonly string  $entryCategory,
        public readonly ?int    $yearsExperience = null
    ){
        parent::__construct();
    }
}