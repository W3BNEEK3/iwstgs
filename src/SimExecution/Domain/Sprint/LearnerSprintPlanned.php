<?php
namespace Src\SimExecution\Domain\Sprint;

use Src\Shared\Domain\DomainEvent;

/**
 * Fired when a new sprint enters planning (Sprint N begins for a session).
 * No listener consumes this yet — recorded because it's a true domain fact,
 * same reasoning as LearnerSessionStarted.
 */
final class LearnerSprintPlanned extends DomainEvent
{
    public function __construct(
        public readonly string $sprintId,
        public readonly string $learnerSessionId,
        public readonly string $learnerId,
        public readonly int $sprintNumber,
    ) {
        parent::__construct();
    }
}
