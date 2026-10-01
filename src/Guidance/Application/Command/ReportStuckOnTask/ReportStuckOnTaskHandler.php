<?php
namespace Src\Guidance\Application\Command\ReportStuckOnTask;

use Src\Guidance\Application\Service\StuckGuideTrigger;

final class ReportStuckOnTaskHandler
{
    public function __construct(private readonly StuckGuideTrigger $trigger) {}

    public function handle(ReportStuckOnTaskCommand $command): void
    {
        $this->trigger->observe($command->userId, $command->sessionId, $command->taskId, $command->minutes);
    }
}
