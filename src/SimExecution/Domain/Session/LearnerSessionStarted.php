<?php
namespace Src\SimExecution\Domain\Session;

use Src\Shared\Domain\DomainEvent;

/**
 * No listener consumes this yet — recorded because it's a true domain fact
 * (a learner's session began, at a known project). Reporting or the future
 * dashboard read model are the natural eventual listeners.
 */
final class LearnerSessionStarted extends DomainEvent
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $learnerId,
        public readonly string $projectId,
    ) {
        parent::__construct();
    }
}
