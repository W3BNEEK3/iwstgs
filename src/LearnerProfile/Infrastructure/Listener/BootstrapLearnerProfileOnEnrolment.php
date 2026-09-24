<?php

namespace Src\LearnerProfile\Infrastructure\Listener;

use Src\LearnerProfile\Application\Command\BootstrapLearnerProfile\BootstrapLearnerProfileCommand;
use Src\Shared\Application\Bus\CommandBus;
use Src\SimExecution\Domain\Enrollment\LearnerEnrolled;

final class BootstrapLearnerProfileOnEnrolment
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function handle(LearnerEnrolled $event): void
    {
        $this->commandBus->dispatch(new BootstrapLearnerProfileCommand(
            learnerId: $event->learnerId,
        ));
    }
}
