<?php
namespace Src\Identity\Infrastructure\Listener;

use Src\Identity\Application\Command\GrantRole\GrantRoleCommand;
use Src\Shared\Application\Bus\CommandBus;
use Src\SimExecution\Domain\Enrollment\LearnerEnrolled;

/**
 * Identity reacts to SimExecution's LearnerEnrolled fact by granting the
 * 'learner' RBAC role — the same event LearnerProfile listens to for
 * bootstrapping (Phase 5b). Two independent contexts, two independent
 * reactions to one fact; neither knows about the other.
 */
final class GrantLearnerRoleOnEnrolment
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function handle(LearnerEnrolled $event): void
    {
        $this->commandBus->dispatch(new GrantRoleCommand(
            userId: $event->userId,
            roleName: 'learner',
        ));
    }
}
